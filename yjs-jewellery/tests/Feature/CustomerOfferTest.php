<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\offers;
use App\Models\OfferUsage;
use App\Models\offerTypeMaster;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer Offer Test
 *
 * Tests for customer offer functionality including
 * applying offers, validating coupons, and offer eligibility.
 */
class CustomerOfferTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected offerTypeMaster $offerType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->customer()->create();
        $this->offerType = offerTypeMaster::factory()->create();
    }

    protected function authenticateCustomer()
    {
        return $this->actingAs($this->customer, 'sanctum');
    }

    protected function createProductWithCart(float $price = 1000, int $quantity = 1): array
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'base_price' => $price,
            'status' => 'active',
        ]);

        Cart::create([
            'user_id' => $this->customer->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'cart_total' => $price * $quantity,
        ]);

        return ['product' => $product, 'category' => $category];
    }

    // =====================
    // GET APPLICABLE OFFERS
    // =====================

    public function test_returns_empty_offers_for_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'applicable' => [],
                'unavailable' => [],
            ]);
    }

    public function test_can_get_applicable_offers(): void
    {
        $this->createProductWithCart(2000);

        offers::factory()->create([
            'title' => 'Flat 100 Off',
            'discount_type' => 'flat',
            'discount_amount' => 100,
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'applicable' => [['id', 'title', 'discount_type', 'calculated_discount']],
                'cart_total',
            ]);
    }

    public function test_excludes_offers_with_min_cart_not_met(): void
    {
        $this->createProductWithCart(500); // Low cart value

        offers::factory()->create([
            'title' => 'High Value Offer',
            'discount_type' => 'percent',
            'discount_percent' => 10,
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'details' => ['min_cart_value' => 1000],
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('applicable'));
        $this->assertNotEmpty($response->json('unavailable'));
        $this->assertEquals('MIN_CART_NOT_MET', $response->json('unavailable.0.reason_code'));
    }

    public function test_excludes_expired_offers(): void
    {
        $this->createProductWithCart(2000);

        offers::factory()->expired()->create([
            'title' => 'Expired Offer',
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('applicable'));
    }

    public function test_excludes_inactive_offers(): void
    {
        $this->createProductWithCart(2000);

        offers::factory()->inactive()->create([
            'title' => 'Inactive Offer',
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('applicable'));
    }

    public function test_first_order_offer_available_for_new_customer(): void
    {
        $this->createProductWithCart(2000);

        offers::factory()->create([
            'title' => 'First Order Discount',
            'discount_type' => 'percent',
            'discount_percent' => 20,
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'details' => ['first_order_only' => true],
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('applicable'));
    }

    public function test_first_order_offer_excluded_for_existing_customer(): void
    {
        $this->createProductWithCart(2000);

        // Create a paid order for this customer
        Order::factory()->forUser($this->customer)->paid()->create();

        offers::factory()->create([
            'title' => 'First Order Discount',
            'discount_type' => 'percent',
            'discount_percent' => 20,
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'details' => ['first_order_only' => true],
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/offers/applicable');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('applicable'));
        $this->assertEquals('NOT_NEW_CUSTOMER', $response->json('unavailable.0.reason_code'));
    }

    // =====================
    // APPLY OFFER TESTS
    // =====================

    public function test_can_apply_flat_discount_offer(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()->flatDiscount(100)->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'discount_amount' => 100,
                'new_total' => 1900,
            ]);
    }

    public function test_can_apply_percent_discount_offer(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()->percentDiscount(10)->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'discount_amount' => 200, // 10% of 2000
            ]);
    }

    public function test_percent_discount_respects_max_cap(): void
    {
        $this->createProductWithCart(10000);

        $offer = offers::factory()->percentDiscount(20, 500)->create([ // 20% max 500
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200);
        // 20% of 10000 = 2000, but capped at 500
        $this->assertEquals(500, $response->json('discount_amount'));
    }

    public function test_cannot_apply_offer_to_empty_cart(): void
    {
        $offer = offers::factory()->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_cannot_apply_nonexistent_offer(): void
    {
        $this->createProductWithCart(2000);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => 99999,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_apply_inactive_offer(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()->inactive()->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'OFFER_INACTIVE',
            ]);
    }

    public function test_cannot_apply_expired_offer(): void
    {
        $this->createProductWithCart(2000);

        // Create an active offer with expired dates
        $offer = offers::factory()->create([
            'status' => 'active',
            'valid_from' => now()->subDays(60),
            'valid_to' => now()->subDays(30),
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'OFFER_EXPIRED',
            ]);
    }

    // =====================
    // COUPON CODE TESTS
    // =====================

    public function test_can_apply_offer_with_coupon(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->withCoupon('SAVE100')
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
                'coupon_code' => 'SAVE100',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'discount_amount' => 100,
            ]);
    }

    public function test_cannot_apply_coupon_offer_without_code(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->withCoupon('REQUIRED123')
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'COUPON_REQUIRED',
            ]);
    }

    public function test_cannot_apply_offer_with_wrong_coupon(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->withCoupon('CORRECT123')
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
                'coupon_code' => 'WRONG123',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'INVALID_COUPON',
            ]);
    }

    public function test_can_validate_coupon_code(): void
    {
        $this->createProductWithCart(2000);

        offers::factory()
            ->withCoupon('VALID100')
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/validate-coupon', [
                'coupon_code' => 'VALID100',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'discount' => 100,
            ]);
    }

    public function test_validates_invalid_coupon(): void
    {
        $this->createProductWithCart(2000);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/validate-coupon', [
                'coupon_code' => 'INVALID',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'valid' => false,
                'error_code' => 'INVALID_COUPON',
            ]);
    }

    public function test_coupon_validation_requires_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/validate-coupon', [
                'coupon_code' => 'ANYCODE',
            ]);

        $response->assertStatus(400)
            ->assertJson(['valid' => false]);
    }

    // =====================
    // REMOVE OFFER TESTS
    // =====================

    public function test_can_remove_applied_offer(): void
    {
        $this->createProductWithCart(2000);

        // Apply an offer first
        $offer = offers::factory()->flatDiscount(100)->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
        ]);

        $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', ['offer_id' => $offer->id]);

        // Remove offer
        $response = $this->authenticateCustomer()
            ->deleteJson('/api/customer/offers/remove');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Offer removed',
            ]);

        // Verify offer is removed from cart
        $cart = Cart::where('user_id', $this->customer->id)->first();
        $this->assertNull($cart->applied_offers);
    }

    // =====================
    // USAGE LIMIT TESTS
    // =====================

    public function test_respects_per_user_limit(): void
    {
        $this->createProductWithCart(2000);
        $order = Order::factory()->forUser($this->customer)->paid()->create();

        $offer = offers::factory()->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'details' => ['max_usage_per_user' => 1],
        ]);

        // Record previous usage
        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'discount_amount' => 100,
            'used_at' => now(),
            'reversed' => false,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'USER_LIMIT_REACHED',
            ]);
    }

    public function test_respects_global_usage_limit(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()->create([
            'status' => 'active',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'details' => ['max_usage_global' => 1],
        ]);

        // Create usage by another user
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->forUser($otherCustomer)->paid()->create();

        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'customer_id' => $otherCustomer->id,
            'discount_amount' => 100,
            'used_at' => now(),
            'reversed' => false,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'GLOBAL_LIMIT_REACHED',
            ]);
    }

    // =====================
    // PRODUCT/CATEGORY FILTER TESTS
    // =====================

    public function test_offer_applies_to_specific_products(): void
    {
        $data = $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->forProducts([$data['product']->id])
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_offer_excluded_for_wrong_products(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->forProducts([99999]) // Non-existent product
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_APPLICABLE',
            ]);
    }

    public function test_offer_applies_to_specific_categories(): void
    {
        $data = $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->forCategories([$data['category']->id])
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_offer_excluded_for_wrong_categories(): void
    {
        $this->createProductWithCart(2000);

        $offer = offers::factory()
            ->forCategories([99999]) // Non-existent category
            ->flatDiscount(100)
            ->create([
                'status' => 'active',
                'valid_from' => now()->subDay(),
                'valid_to' => now()->addDay(),
            ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/offers/apply', [
                'offer_id' => $offer->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'CATEGORY_NOT_APPLICABLE',
            ]);
    }

    // =====================
    // AUTH TESTS
    // =====================

    public function test_unauthenticated_cannot_get_offers(): void
    {
        $response = $this->getJson('/api/customer/offers/applicable');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_cannot_apply_offers(): void
    {
        $response = $this->postJson('/api/customer/offers/apply', ['offer_id' => 1]);
        $response->assertStatus(401);
    }
}
