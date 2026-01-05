<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShipmentTracking;
use App\Models\User;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * Admin Shipping Test
 *
 * Tests for admin shipping management including
 * pending shipments, bulk operations, and dashboard.
 */
class AdminShippingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['user_type' => 'employee']);
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    // =====================
    // DASHBOARD TESTS
    // =====================

    public function test_can_get_shipping_dashboard(): void
    {
        // Create various orders
        Order::factory()->paid()->confirmed()->count(3)->create();
        Order::factory()->shipped()->count(2)->create();
        Order::factory()->delivered()->count(1)->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'pending_to_ship',
                    'awb_pending',
                    'ready_for_pickup',
                    'in_transit',
                    'out_for_delivery',
                    'delivered_today',
                    'delivered_this_week',
                    'rto_count',
                    'avg_delivery_days',
                    'courier_breakdown',
                ],
            ]);
    }

    // =====================
    // PENDING SHIPMENTS TESTS
    // =====================

    public function test_can_get_pending_shipments(): void
    {
        Order::factory()->paid()->confirmed()->count(3)->create();
        Order::factory()->shipped()->count(2)->create(); // Should not appear

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/pending');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_can_filter_pending_shipments_by_date(): void
    {
        Order::factory()->paid()->confirmed()->create([
            'order_date' => now()->subDays(10),
        ]);
        Order::factory()->paid()->confirmed()->create([
            'order_date' => now(),
        ]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/pending?date_from=' . now()->subDays(5)->toDateString());

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_search_pending_shipments(): void
    {
        $order = Order::factory()->paid()->confirmed()->create([
            'custom_order_code' => 'UNIQUE-SEARCH-123',
        ]);
        Order::factory()->paid()->confirmed()->count(2)->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/pending?search=UNIQUE-SEARCH');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    // =====================
    // SHIPPED ORDERS TESTS
    // =====================

    public function test_can_get_shipped_orders(): void
    {
        Order::factory()->shipped()->count(3)->create();
        Order::factory()->paid()->confirmed()->count(2)->create(); // Should not appear

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/shipped');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ]);

        $this->assertEquals(3, $response->json('pagination.total'));
    }

    public function test_can_filter_shipped_orders_by_status(): void
    {
        Order::factory()->shipped()->count(2)->create();
        // Delivered orders need AWB for the shipped orders query
        Order::factory()->shipped()->delivered()->count(1)->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/shipped?status=delivered');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_search_shipped_by_awb(): void
    {
        Order::factory()->shipped()->create([
            'awb_number' => 'AWB123456789',
        ]);
        Order::factory()->shipped()->count(2)->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/shipped?search=AWB123456');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    // =====================
    // ORDER DETAILS TESTS
    // =====================

    public function test_can_get_order_shipping_details(): void
    {
        $order = Order::factory()->shipped()->create();

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/shipping/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'order' => [
                    'id',
                    'order_code',
                    'order_status',
                    'awb_number',
                    'courier_name',
                ],
            ]);
    }

    public function test_order_details_returns_404_for_nonexistent(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/shipping/orders/99999');

        $response->assertStatus(404);
    }

    // =====================
    // BULK OPERATIONS TESTS
    // =====================

    public function test_bulk_push_validates_order_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/bulk-push', [
                'order_ids' => [99999],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_awb_validates_order_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/bulk-awb', [
                'order_ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_pickup_validates_order_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/bulk-pickup', [
                'order_ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_labels_validates_order_ids(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/bulk-labels', [
                'order_ids' => [],
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // SERVICEABILITY CHECK
    // =====================

    public function test_can_check_serviceability(): void
    {
        // Mock the Shiprocket service for serviceability check
        $mock = Mockery::mock(ShiprocketService::class);
        $mock->shouldReceive('checkServiceability')
            ->with('400001', 100)
            ->andReturn([
                'serviceable' => true,
                'estimated_days' => '3-5',
                'shipping_charge' => 50.00,
                'courier_name' => 'Bluedart',
            ]);

        $this->app->instance(ShiprocketService::class, $mock);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/serviceability', [
                'pincode' => '400001',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'serviceable' => true,
                ],
            ]);
    }

    public function test_serviceability_validates_pincode(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/shipping/serviceability', [
                'pincode' => '123', // Invalid pincode
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pincode']);
    }

    // =====================
    // AUTH TESTS
    // =====================

    public function test_unauthenticated_cannot_access_shipping_dashboard(): void
    {
        $response = $this->getJson('/api/admin/shipping/dashboard');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_pending_shipments(): void
    {
        $response = $this->getJson('/api/admin/shipping/pending');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_perform_bulk_operations(): void
    {
        $response = $this->postJson('/api/admin/shipping/bulk-push', ['order_ids' => [1]]);
        $response->assertStatus(401);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
