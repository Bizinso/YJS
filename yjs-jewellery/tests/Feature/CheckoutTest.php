<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Checkout Test
 *
 * Tests for checkout flow including summary,
 * validation, and order creation.
 */
class CheckoutTest extends TestCase
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

    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    protected function createCartItem(array $productAttrs = [], int $quantity = 1): Cart
    {
        $product = Product::factory()->create(array_merge([
            'available_stock' => 50,
            'base_price' => 10000,
        ], $productAttrs));

        return Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity($quantity)
            ->create();
    }

    protected function createAddress(array $attrs = []): CustomerAddress
    {
        return CustomerAddress::factory()
            ->forCustomer($this->customer)
            ->create(array_merge(['country_id' => $this->country->id], $attrs));
    }

    // =====================
    // CHECKOUT SUMMARY TESTS
    // =====================

    public function test_can_get_checkout_summary(): void
    {
        $this->createCartItem();
        $this->createAddress(['is_default' => true]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/checkout/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'totals' => [
                        'subtotal',
                        'charges',
                        'taxes',
                        'shipping',
                        'discount',
                        'total',
                    ],
                    'addresses',
                    'default_address_id',
                    'stock_issues',
                    'can_checkout',
                ],
            ]);
    }

    public function test_checkout_summary_returns_error_for_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/checkout/summary');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Your cart is empty.',
            ]);
    }

    public function test_checkout_summary_shows_stock_issues(): void
    {
        $product = Product::factory()->create([
            'available_stock' => 2,
            'base_price' => 5000,
        ]);

        Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity(5) // More than available
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/checkout/summary');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.stock_issues'));
        $this->assertFalse($response->json('data.can_checkout'));
    }

    public function test_checkout_summary_includes_addresses(): void
    {
        $this->createCartItem();
        $address = $this->createAddress(['is_default' => true]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/checkout/summary');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.addresses'));
        $this->assertEquals($address->id, $response->json('data.default_address_id'));
    }

    public function test_unauthenticated_cannot_get_checkout_summary(): void
    {
        $response = $this->getJson('/api/customer/checkout/summary');
        $response->assertStatus(401);
    }

    // =====================
    // VALIDATE CART TESTS
    // =====================

    public function test_can_validate_cart(): void
    {
        $this->createCartItem();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/validate');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_checkout' => true,
            ]);
    }

    public function test_validate_returns_false_for_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/validate');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'can_checkout' => false,
            ]);
    }

    public function test_validate_returns_issues_for_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'available_stock' => 1,
            'status' => 'active',
        ]);

        Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity(5)
            ->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/validate');

        $response->assertStatus(200);
        $this->assertFalse($response->json('success'));
        $this->assertFalse($response->json('can_checkout'));
        $this->assertNotEmpty($response->json('data.issues'));
    }

    public function test_validate_returns_issues_for_inactive_products(): void
    {
        $product = Product::factory()->inactive()->create();

        Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/validate');

        $response->assertStatus(200);
        $this->assertFalse($response->json('can_checkout'));
    }

    // =====================
    // CREATE ORDER TESTS
    // =====================

    public function test_can_create_order_from_cart(): void
    {
        $this->createCartItem();
        $billingAddress = $this->createAddress(['is_default' => true]);
        $shippingAddress = $this->createAddress();

        // Mock the RazorpayService and ShiprocketService
        $this->mock(\App\Services\Payment\RazorpayService::class, function ($mock) {
            $mock->shouldReceive('createOrder')->andReturn([
                'razorpay_order_id' => 'order_test123',
                'amount' => 10000,
                'currency' => 'INR',
            ]);
        });

        $this->mock(\App\Services\Shipping\ShiprocketService::class, function ($mock) {
            $mock->shouldReceive('checkServiceability')->andReturn([
                'serviceable' => true,
                'shipping_charges' => 0,
            ]);
        });

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/order', [
                'billing_address_id' => $billingAddress->id,
                'shipping_address_id' => $shippingAddress->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order_id',
                    'order_code',
                    'order_total',
                ],
            ]);

        // Cart should be cleared
        $this->assertDatabaseMissing('carts', [
            'user_id' => $this->user->id,
            'deleted_at' => null,
        ]);
    }

    public function test_create_order_validates_addresses(): void
    {
        $this->createCartItem();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/order', [
                'billing_address_id' => 99999,
                'shipping_address_id' => 99999,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_order_with_other_customers_address(): void
    {
        $this->createCartItem();
        $ownAddress = $this->createAddress();

        // Create another customer with their address
        $otherCustomer = Customer::factory()->create();
        $otherAddress = CustomerAddress::factory()
            ->forCustomer($otherCustomer)
            ->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/order', [
                'billing_address_id' => $ownAddress->id,
                'shipping_address_id' => $otherAddress->id,
            ]);

        $response->assertStatus(400);
    }

    public function test_create_order_requires_addresses(): void
    {
        $this->createCartItem();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/order', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['billing_address_id', 'shipping_address_id']);
    }

    public function test_create_order_fails_for_empty_cart(): void
    {
        $billingAddress = $this->createAddress();
        $shippingAddress = $this->createAddress();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/order', [
                'billing_address_id' => $billingAddress->id,
                'shipping_address_id' => $shippingAddress->id,
            ]);

        $response->assertStatus(400);
    }

    // =====================
    // COUPON TESTS
    // =====================

    public function test_apply_coupon_requires_coupon_code(): void
    {
        $this->createCartItem();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/coupon', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['coupon_code']);
    }

    public function test_apply_coupon_fails_for_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/coupon', [
                'coupon_code' => 'TESTCODE',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Your cart is empty.',
            ]);
    }

    public function test_can_remove_coupon(): void
    {
        $response = $this->authenticateCustomer()
            ->deleteJson('/api/customer/checkout/coupon');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Coupon removed.',
            ]);
    }

    // =====================
    // SERVICEABILITY TESTS
    // =====================

    public function test_check_serviceability_requires_pincode(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/serviceability', []);

        $response->assertStatus(422);
    }

    public function test_check_serviceability_validates_pincode_length(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/checkout/serviceability', [
                'pincode' => '1234', // Too short
            ]);

        $response->assertStatus(422);
    }
}
