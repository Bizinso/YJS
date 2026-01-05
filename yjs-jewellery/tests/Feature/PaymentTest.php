<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use App\Services\Payment\RazorpayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Test
 *
 * Tests for payment creation, verification, and status endpoints.
 */
class PaymentTest extends TestCase
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
                'payment_status' => 'pending',
            ], $attrs));
    }

    // =====================
    // CREATE PAYMENT TESTS
    // =====================

    public function test_can_create_payment_for_order(): void
    {
        $order = $this->createOrder();

        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'success' => true,
                    'razorpay_order_id' => 'order_test123',
                    'amount' => 10000,
                    'currency' => 'INR',
                ]);
        });

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/payment");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'razorpay_order_id' => 'order_test123',
            ]);
    }

    public function test_cannot_create_payment_for_paid_order(): void
    {
        $order = $this->createOrder(['payment_status' => 'paid']);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/payment");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Order already paid',
            ]);
    }

    public function test_cannot_create_payment_for_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherUser)->create([
            'country_id' => $this->country->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/payment");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_payment(): void
    {
        $order = $this->createOrder();

        $response = $this->postJson("/api/customer/orders/{$order->id}/payment");
        $response->assertStatus(401);
    }

    // =====================
    // VERIFY PAYMENT TESTS
    // =====================

    public function test_can_verify_payment(): void
    {
        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')
                ->once()
                ->with('order_123', 'pay_456', 'sig_789')
                ->andReturn([
                    'success' => true,
                    'order_id' => 1,
                ]);
        });

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/payment/verify', [
                'razorpay_order_id' => 'order_123',
                'razorpay_payment_id' => 'pay_456',
                'razorpay_signature' => 'sig_789',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment successful',
            ]);
    }

    public function test_verify_payment_fails_for_invalid_signature(): void
    {
        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Invalid signature',
                ]);
        });

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/payment/verify', [
                'razorpay_order_id' => 'order_123',
                'razorpay_payment_id' => 'pay_456',
                'razorpay_signature' => 'invalid_sig',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_verify_payment_requires_all_fields(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/payment/verify', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'razorpay_order_id',
                'razorpay_payment_id',
                'razorpay_signature',
            ]);
    }

    // =====================
    // PAYMENT STATUS TESTS
    // =====================

    public function test_can_get_payment_status(): void
    {
        $order = $this->createOrder(['payment_status' => 'pending']);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/payment-status");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'order_id' => $order->id,
                'payment_status' => 'pending',
            ]);
    }

    public function test_cannot_get_payment_status_for_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherUser)->create([
            'country_id' => $this->country->id,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/orders/{$order->id}/payment-status");

        $response->assertStatus(403);
    }

    // =====================
    // RETRY PAYMENT TESTS
    // =====================

    public function test_can_retry_payment_within_window(): void
    {
        $order = $this->createOrder([
            'payment_status' => 'pending',
            'created_at' => now()->subHours(12),
        ]);

        $this->mock(RazorpayService::class, function ($mock) {
            $mock->shouldReceive('createOrder')
                ->once()
                ->andReturn([
                    'success' => true,
                    'razorpay_order_id' => 'order_retry123',
                    'amount' => 10000,
                    'currency' => 'INR',
                ]);
        });

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/retry-payment");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_cannot_retry_payment_after_window_expires(): void
    {
        $order = $this->createOrder([
            'payment_status' => 'pending',
            'created_at' => now()->subHours(25),
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/retry-payment");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Payment retry window expired (24 hours)',
            ]);
    }

    public function test_cannot_retry_payment_for_paid_order(): void
    {
        $order = $this->createOrder(['payment_status' => 'paid']);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/retry-payment");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Order already paid',
            ]);
    }

    public function test_cannot_retry_other_users_order(): void
    {
        $otherUser = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherUser)->create([
            'country_id' => $this->country->id,
            'payment_status' => 'pending',
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/orders/{$order->id}/retry-payment");

        $response->assertStatus(403);
    }
}
