<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->customer()->create();
        $this->customer = Customer::factory()->forUser($this->user)->create();
    }

    /**
     * Helper to authenticate the customer.
     */
    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =====================
    // ORDER LISTING TESTS
    // =====================

    public function test_can_get_order_list(): void
    {
        Order::factory()
            ->forUser($this->user)
            ->count(5)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(5, $response->json('meta.total'));
    }

    public function test_order_list_only_shows_own_orders(): void
    {
        // Create orders for current user
        Order::factory()
            ->forUser($this->user)
            ->count(3)
            ->create(['country_id' => $this->country->id]);

        // Create orders for another user
        $otherUser = User::factory()->customer()->create();
        Order::factory()
            ->forUser($otherUser)
            ->count(2)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_filter_orders_by_status(): void
    {
        Order::factory()
            ->forUser($this->user)
            ->create(['country_id' => $this->country->id, 'order_status' => 'pending']);

        Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->count(2)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders?status=delivered');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_can_search_orders_by_code(): void
    {
        Order::factory()
            ->forUser($this->user)
            ->create([
                'country_id' => $this->country->id,
                'custom_order_code' => 'YJS-ABC123',
            ]);

        Order::factory()
            ->forUser($this->user)
            ->create([
                'country_id' => $this->country->id,
                'custom_order_code' => 'YJS-XYZ789',
            ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders?search=ABC');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_unauthenticated_cannot_get_orders(): void
    {
        $response = $this->getJson('/api/customer/orders');

        $response->assertStatus(401);
    }

    // =====================
    // ORDER DETAILS TESTS
    // =====================

    public function test_can_get_order_details(): void
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'order_code' => $order->custom_order_code,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_code',
                    'status',
                    'status_label',
                    'payment_status',
                    'subtotal',
                    'total',
                    'items',
                    'can_cancel',
                ],
            ]);
    }

    public function test_cannot_get_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()
            ->forUser($otherUser)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders/99999');

        $response->assertStatus(404);
    }

    // =====================
    // ORDER CANCELLATION TESTS
    // =====================

    public function test_can_cancel_pending_order(): void
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->create([
                'country_id' => $this->country->id,
                'order_status' => 'pending',
            ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $order->refresh();
        $this->assertEquals('cancelled', $order->order_status);

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'cancelled_by' => 'customer',
            'reason_text' => 'Changed my mind',
        ]);
    }

    public function test_cannot_cancel_shipped_order(): void
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->shipped()
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(422);

        $order->refresh();
        $this->assertEquals('shipped', $order->order_status);
    }

    public function test_cancel_requires_reason(): void
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->create([
                'country_id' => $this->country->id,
                'order_status' => 'pending',
            ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_cannot_cancel_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()
            ->forUser($otherUser)
            ->create([
                'country_id' => $this->country->id,
                'order_status' => 'pending',
            ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(404);
    }

    // =====================
    // ORDER TRACKING TESTS
    // =====================

    public function test_can_get_order_tracking(): void
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->shipped()
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/tracking");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_code',
                    'current_status',
                    'status_label',
                    'awb_number',
                    'courier_name',
                    'status_timeline',
                ],
            ]);
    }

    public function test_cannot_get_tracking_for_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()
            ->forUser($otherUser)
            ->shipped()
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/tracking");

        $response->assertStatus(404);
    }

    // =====================
    // ORDER STATISTICS TESTS
    // =====================

    public function test_can_get_order_statistics(): void
    {
        // Create orders with different statuses
        Order::factory()
            ->forUser($this->user)
            ->count(2)
            ->create(['country_id' => $this->country->id, 'order_status' => 'pending']);

        Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->paid()
            ->count(3)
            ->create(['country_id' => $this->country->id, 'order_total' => 1000]);

        Order::factory()
            ->forUser($this->user)
            ->cancelled()
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_orders',
                    'pending_orders',
                    'shipped_orders',
                    'delivered_orders',
                    'cancelled_orders',
                    'total_spent',
                ],
            ]);

        $this->assertEquals(6, $response->json('data.total_orders'));
        $this->assertEquals(2, $response->json('data.pending_orders'));
        $this->assertEquals(3, $response->json('data.delivered_orders'));
        $this->assertEquals(1, $response->json('data.cancelled_orders'));
    }

    public function test_statistics_only_include_own_orders(): void
    {
        Order::factory()
            ->forUser($this->user)
            ->count(2)
            ->create(['country_id' => $this->country->id]);

        $otherUser = User::factory()->customer()->create();
        Order::factory()
            ->forUser($otherUser)
            ->count(5)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/orders/statistics');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.total_orders'));
    }
}
