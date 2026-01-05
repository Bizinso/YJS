<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Shipping Test
 *
 * Tests for shipping serviceability and tracking endpoints.
 */
class ShippingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->customer()->create();
    }

    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    protected function createOrder(array $attrs = []): Order
    {
        return Order::factory()
            ->forUser($this->user)
            ->create(array_merge([
                'country_id' => $this->country->id,
            ], $attrs));
    }

    // =====================
    // SERVICEABILITY TESTS
    // =====================

    public function test_can_check_serviceability(): void
    {
        $this->mock(ShiprocketService::class, function ($mock) {
            $mock->shouldReceive('checkServiceability')
                ->once()
                ->with('400001', 100)
                ->andReturn([
                    'success' => true,
                    'serviceable' => true,
                    'cod_available' => true,
                    'etd' => '3-5 days',
                    'courier_name' => 'BlueDart',
                ]);
        });

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/shipping/serviceability?pincode=400001');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'serviceable' => true,
            ]);
    }

    public function test_serviceability_requires_pincode(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/shipping/serviceability');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);
    }

    public function test_serviceability_validates_pincode_length(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/shipping/serviceability?pincode=1234');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);
    }

    public function test_serviceability_returns_not_serviceable(): void
    {
        $this->mock(ShiprocketService::class, function ($mock) {
            $mock->shouldReceive('checkServiceability')
                ->once()
                ->andReturn([
                    'success' => true,
                    'serviceable' => false,
                    'cod_available' => false,
                    'message' => 'Delivery not available for this pincode',
                ]);
        });

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/shipping/serviceability?pincode=999999');

        $response->assertStatus(200)
            ->assertJson([
                'serviceable' => false,
            ]);
    }

    public function test_can_check_serviceability_with_weight(): void
    {
        $this->mock(ShiprocketService::class, function ($mock) {
            $mock->shouldReceive('checkServiceability')
                ->once()
                ->with('400001', 500)
                ->andReturn([
                    'success' => true,
                    'serviceable' => true,
                ]);
        });

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/shipping/serviceability?pincode=400001&weight=500');

        $response->assertStatus(200);
    }

    // =====================
    // TRACKING TESTS
    // =====================

    public function test_can_track_shipped_order(): void
    {
        $order = $this->createOrder([
            'order_status' => 'shipped',
            'awb_number' => 'AWB123456789',
        ]);

        $this->mock(ShiprocketService::class, function ($mock) {
            $mock->shouldReceive('trackByAWB')
                ->once()
                ->with('AWB123456789')
                ->andReturn([
                    'current_status' => 'In Transit',
                    'current_location' => 'Mumbai Hub',
                    'etd' => '2024-01-15',
                    'activities' => [
                        ['status' => 'Shipped', 'date' => '2024-01-10', 'location' => 'Origin'],
                        ['status' => 'In Transit', 'date' => '2024-01-12', 'location' => 'Mumbai Hub'],
                    ],
                ]);
        });

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/ship-tracking");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'tracking',
            ]);
    }

    public function test_cannot_track_order_without_awb(): void
    {
        $order = $this->createOrder([
            'order_status' => 'confirmed',
            'awb_number' => null,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/ship-tracking");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Shipment not created yet',
            ]);
    }

    public function test_cannot_track_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherUser)->create([
            'country_id' => $this->country->id,
            'awb_number' => 'AWB123456789',
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/ship-tracking");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_track_order(): void
    {
        $order = $this->createOrder(['awb_number' => 'AWB123456789']);

        $response = $this->getJson("/api/customer/orders/{$order->id}/ship-tracking");
        $response->assertStatus(401);
    }

    public function test_tracking_returns_order_status_when_no_awb(): void
    {
        $order = $this->createOrder([
            'order_status' => 'processing',
            'awb_number' => null,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/ship-tracking");

        $response->assertStatus(400)
            ->assertJson([
                'order_status' => 'processing',
            ]);
    }
}
