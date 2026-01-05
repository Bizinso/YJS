<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Order;
use App\Models\orderProduct;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    /**
     * Helper to create a delivered order with a product.
     */
    protected function createDeliveredOrderWithProduct(Product $product): Order
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create();

        return $order;
    }

    // =====================
    // PUBLIC PRODUCT REVIEWS TESTS
    // =====================

    public function test_can_get_product_reviews_public(): void
    {
        $product = Product::factory()->create();

        // Create approved reviews
        Review::factory()
            ->forProduct($product)
            ->approved()
            ->count(3)
            ->create();

        // Create pending review (should not appear)
        Review::factory()
            ->forProduct($product)
            ->pending()
            ->create();

        $response = $this->getJson("/api/customer/products/{$product->id}/reviews");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'reviews',
                    'pagination',
                    'summary' => [
                        'average_rating',
                        'review_count',
                        'rating_distribution',
                    ],
                ],
            ]);

        // Only approved reviews should be returned
        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_product_reviews_can_filter_by_rating(): void
    {
        $product = Product::factory()->create();

        Review::factory()
            ->forProduct($product)
            ->approved()
            ->withRating(5)
            ->count(3)
            ->create();

        Review::factory()
            ->forProduct($product)
            ->approved()
            ->withRating(3)
            ->count(2)
            ->create();

        $response = $this->getJson("/api/customer/products/{$product->id}/reviews?rating=5");

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.pagination.total'));
    }

    public function test_product_reviews_shows_rating_summary(): void
    {
        $product = Product::factory()->create();

        // Create reviews with different ratings
        Review::factory()->forProduct($product)->approved()->withRating(5)->count(3)->create();
        Review::factory()->forProduct($product)->approved()->withRating(4)->count(2)->create();
        Review::factory()->forProduct($product)->approved()->withRating(3)->create();

        $response = $this->getJson("/api/customer/products/{$product->id}/reviews");

        $response->assertStatus(200);

        $summary = $response->json('data.summary');
        $this->assertEquals(6, $summary['review_count']);
        $this->assertArrayHasKey('rating_distribution', $summary);
    }

    // =====================
    // MY REVIEWS TESTS
    // =====================

    public function test_can_get_my_reviews(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->count(2)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/reviews');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ]);

        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_my_reviews_only_shows_own_reviews(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        // Current user's review
        Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        // Other user's review
        $otherUser = User::factory()->customer()->create();
        Review::factory()
            ->forUser($otherUser)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/reviews');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_my_reviews_can_filter_by_status(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->approved()
            ->count(2)
            ->create();

        Review::factory()
            ->forUser($this->user)
            ->forProduct(Product::factory()->create())
            ->forOrder($order)
            ->pending()
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/reviews?status=approved');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_unauthenticated_cannot_get_my_reviews(): void
    {
        $response = $this->getJson('/api/customer/reviews');

        $response->assertStatus(401);
    }

    // =====================
    // CAN REVIEW TESTS
    // =====================

    public function test_can_review_returns_true_for_delivered_product(): void
    {
        $product = Product::factory()->create();
        $this->createDeliveredOrderWithProduct($product);

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/products/{$product->id}/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_review' => true,
            ]);
    }

    public function test_can_review_returns_false_for_not_purchased_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/products/{$product->id}/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_review' => false,
            ]);
    }

    public function test_can_review_returns_false_for_already_reviewed_product(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/products/{$product->id}/can-review");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_review' => false,
            ]);
    }

    // =====================
    // STORE REVIEW TESTS
    // =====================

    public function test_can_create_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/reviews', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 5,
                'comment' => 'Excellent product!',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_create_review_without_purchase(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->delivered()->create(['country_id' => $this->country->id]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/reviews', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 5,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_review_for_undelivered_order(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()
            ->forUser($this->user)
            ->processing()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/reviews', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 4,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_create_duplicate_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/reviews', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 3,
            ]);

        $response->assertStatus(422);
    }

    public function test_create_review_validates_rating(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/reviews', [
                'product_id' => $product->id,
                'order_id' => $order->id,
                'rating' => 6, // Invalid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    // =====================
    // UPDATE REVIEW TESTS
    // =====================

    public function test_can_update_pending_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $review = Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->pending()
            ->create(['rating' => 3]);

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => 'Updated comment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Updated comment',
        ]);
    }

    public function test_cannot_update_approved_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $review = Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->approved()
            ->create();

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/reviews/{$review->id}", [
                'rating' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_update_other_users_review(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->forUser($otherUser)->delivered()->create(['country_id' => $this->country->id]);

        $review = Review::factory()
            ->forUser($otherUser)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/reviews/{$review->id}", [
                'rating' => 1,
            ]);

        $response->assertStatus(404);
    }

    // =====================
    // DELETE REVIEW TESTS
    // =====================

    public function test_can_delete_own_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $review = Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_cannot_delete_other_users_review(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $review = Review::factory()
            ->forUser($otherUser)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/reviews/{$review->id}");

        $response->assertStatus(404);
    }

    // =====================
    // PENDING REVIEWS TESTS
    // =====================

    public function test_can_get_products_pending_review(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $order = Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->create(['country_id' => $this->country->id]);

        // Add products to order
        orderProduct::factory()->forOrder($order)->forProduct($product1)->create();
        orderProduct::factory()->forOrder($order)->forProduct($product2)->create();
        orderProduct::factory()->forOrder($order)->forProduct($product3)->create();

        // Review product1 only
        Review::factory()
            ->forUser($this->user)
            ->forProduct($product1)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/reviews/pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
            ]);
    }

    public function test_pending_reviews_only_shows_delivered_orders(): void
    {
        $product = Product::factory()->create();

        // Processing order (should not appear)
        $processingOrder = Order::factory()
            ->forUser($this->user)
            ->processing()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()->forOrder($processingOrder)->forProduct($product)->create();

        // Delivered order (should appear)
        $deliveredOrder = Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()
            ->forOrder($deliveredOrder)
            ->forProduct(Product::factory()->create())
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/reviews/pending');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('count'));
    }

    // =====================
    // SHOW REVIEW TESTS
    // =====================

    public function test_can_get_single_review(): void
    {
        $product = Product::factory()->create();
        $order = $this->createDeliveredOrderWithProduct($product);

        $review = Review::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->forOrder($order)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'rating',
                    'comment',
                    'status',
                    'product',
                    'order',
                ],
            ]);
    }

    public function test_cannot_get_other_users_review(): void
    {
        $otherUser = User::factory()->customer()->create();
        $review = Review::factory()
            ->forUser($otherUser)
            ->create();

        $response = $this->authenticateCustomer()
            ->getJson("/api/customer/reviews/{$review->id}");

        $response->assertStatus(404);
    }
}
