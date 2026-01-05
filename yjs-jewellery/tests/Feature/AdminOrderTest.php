<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderPayment;
use App\Models\orderProduct;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Order Test
 *
 * Tests for admin order management including
 * listing, status updates, and refunds.
 */
class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->admin = User::factory()->create(['user_type' => 'employee']);
        $this->customer = User::factory()->customer()->create();
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    protected function createOrder(array $attrs = []): Order
    {
        return Order::factory()
            ->forUser($this->customer)
            ->create(array_merge([
                'country_id' => $this->country->id,
            ], $attrs));
    }

    // =====================
    // ORDER LIST TESTS
    // =====================

    public function test_can_get_order_list(): void
    {
        $this->createOrder();
        $this->createOrder();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_can_filter_orders_by_status(): void
    {
        $this->createOrder(['order_status' => 'pending']);
        $this->createOrder(['order_status' => 'delivered']);
        $this->createOrder(['order_status' => 'delivered']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders?status=delivered');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_can_filter_orders_by_payment_status(): void
    {
        $this->createOrder(['payment_status' => 'pending']);
        $this->createOrder(['payment_status' => 'paid']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders?payment_status=paid');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_search_orders(): void
    {
        $this->createOrder(['custom_order_code' => 'YJS-SEARCH001']);
        $this->createOrder(['custom_order_code' => 'YJS-OTHER002']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders?search=SEARCH001');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_unauthenticated_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/admin/orders');
        $response->assertStatus(401);
    }

    // =====================
    // ORDER DETAILS TESTS
    // =====================

    public function test_can_get_order_details(): void
    {
        $order = $this->createOrder();

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders/99999');

        $response->assertStatus(404);
    }

    // =====================
    // STATUS UPDATE TESTS
    // =====================

    public function test_can_update_order_status(): void
    {
        $order = $this->createOrder(['order_status' => 'pending']);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'confirmed',
                'notes' => 'Order confirmed by admin',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'old_status' => 'pending',
                    'new_status' => 'confirmed',
                ],
            ]);

        $order->refresh();
        $this->assertEquals('confirmed', $order->order_status);
    }

    public function test_cannot_make_invalid_status_transition(): void
    {
        $order = $this->createOrder(['order_status' => 'delivered']);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'pending',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'valid_transitions',
            ]);
    }

    public function test_cancelling_order_restores_stock(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $order = $this->createOrder(['order_status' => 'pending']);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create(['quantity' => 3]);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'cancelled',
                'notes' => 'Cancelled by admin',
            ]);

        $response->assertStatus(200);

        $product->refresh();
        $this->assertEquals(13, $product->available_stock);
    }

    public function test_status_update_validates_input(): void
    {
        $order = $this->createOrder();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/orders/{$order->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // REFUND TESTS
    // =====================

    public function test_can_process_refund(): void
    {
        $order = $this->createOrder([
            'order_status' => 'cancelled',
            'payment_status' => 'paid',
            'order_total' => 10000,
        ]);

        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'razorpay_payment_id' => 'pay_test123',
            'amount' => 10000,
            'status' => 'success',
        ]);

        // Create mock refund object to be returned by the service
        $mockRefund = new OrderRefund([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'razorpay_refund_id' => 'rfnd_test123',
            'amount' => 10000,
            'status' => 'processed',
        ]);
        $mockRefund->id = 1;

        $this->mock(RazorpayService::class, function ($mock) use ($mockRefund) {
            $mock->shouldReceive('refundFull')
                ->once()
                ->andReturn($mockRefund);
        });

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/orders/{$order->id}/refund", [
                'reason' => 'Customer requested refund',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'refund_id' => 'rfnd_test123',
                ],
            ]);
    }

    public function test_cannot_refund_unpaid_order(): void
    {
        $order = $this->createOrder(['payment_status' => 'pending']);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/orders/{$order->id}/refund", [
                'reason' => 'Refund requested',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Order is not paid. Cannot process refund.',
            ]);
    }

    public function test_refund_requires_reason(): void
    {
        $order = $this->createOrder(['payment_status' => 'paid']);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/orders/{$order->id}/refund", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // =====================
    // ADD NOTE TESTS
    // =====================

    public function test_can_add_note_to_order(): void
    {
        $order = $this->createOrder();

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/orders/{$order->id}/note", [
                'note' => 'Customer called about shipping update',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Note added successfully.',
            ]);

        $order->refresh();
        $this->assertStringContainsString('Customer called about shipping update', $order->notes);
    }

    public function test_add_note_validates_input(): void
    {
        $order = $this->createOrder();

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/orders/{$order->id}/note", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['note']);
    }

    // =====================
    // STATISTICS TESTS
    // =====================

    public function test_can_get_order_statistics(): void
    {
        $this->createOrder(['order_status' => 'pending']);
        $this->createOrder(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 10000]);
        $this->createOrder(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 15000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period_days',
                    'status_counts',
                    'revenue',
                    'daily_trend',
                    'pending_actions',
                ],
            ]);
    }

    public function test_statistics_accepts_period_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/orders/statistics?period=7');

        $response->assertStatus(200);
        $this->assertEquals(7, $response->json('data.period_days'));
    }
}
