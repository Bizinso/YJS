<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
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
    // WISHLIST LIST TESTS
    // =====================

    public function test_can_get_wishlist(): void
    {
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            Wishlist::factory()->forUser($this->user)->create([
                'product_id' => $product->id,
            ]);
        }

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/wishlists');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'count',
            ]);

        $this->assertEquals(3, $response->json('count'));
    }

    public function test_wishlist_only_shows_own_items(): void
    {
        $product = Product::factory()->create();

        // Current user's wishlist
        Wishlist::factory()->forUser($this->user)->count(2)->create([
            'product_id' => $product->id,
        ]);

        // Other user's wishlist
        $otherUser = User::factory()->customer()->create();
        Wishlist::factory()->forUser($otherUser)->create([
            'product_id' => Product::factory()->create()->id,
        ]);

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/wishlists');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('count'));
    }

    public function test_unauthenticated_cannot_get_wishlist(): void
    {
        $response = $this->getJson('/api/customer/wishlists');

        $response->assertStatus(401);
    }

    // =====================
    // ADD TO WISHLIST TESTS
    // =====================

    public function test_can_add_product_to_wishlist(): void
    {
        $product = Product::factory()->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists', [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_adding_existing_product_returns_success(): void
    {
        $product = Product::factory()->create();

        Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists', [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_exists' => true,
            ]);
    }

    public function test_cannot_add_nonexistent_product_to_wishlist(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists', [
                'product_id' => 99999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    // =====================
    // REMOVE FROM WISHLIST TESTS
    // =====================

    public function test_can_remove_from_wishlist(): void
    {
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/wishlists/{$wishlist->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    public function test_cannot_remove_other_users_wishlist_item(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->forUser($otherUser)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/wishlists/{$wishlist->id}");

        $response->assertStatus(404);
    }

    // =====================
    // TOGGLE WISHLIST TESTS
    // =====================

    public function test_toggle_adds_to_wishlist(): void
    {
        $product = Product::factory()->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists/toggle', [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'added',
                'is_wishlisted' => true,
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_toggle_removes_from_wishlist(): void
    {
        $product = Product::factory()->create();
        Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists/toggle', [
                'product_id' => $product->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'removed',
                'is_wishlisted' => false,
            ]);
    }

    // =====================
    // SYNC WISHLIST TESTS
    // =====================

    public function test_can_sync_wishlist(): void
    {
        $products = Product::factory()->count(3)->create();
        $productIds = $products->pluck('id')->toArray();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists/sync', [
                'product_ids' => $productIds,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.added_count', 3)
            ->assertJsonPath('data.total_count', 3);
    }

    public function test_sync_skips_already_wishlisted(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Pre-add product1 to wishlist
        Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product1->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists/sync', [
                'product_ids' => [$product1->id, $product2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.added_count', 1)
            ->assertJsonPath('data.total_count', 2);
    }

    // =====================
    // CHECK WISHLIST TESTS
    // =====================

    public function test_can_check_wishlisted_products(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product1->id,
        ]);
        Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product3->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/wishlists/check', [
                'product_ids' => [$product1->id, $product2->id, $product3->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $wishlistedIds = $response->json('data');
        $this->assertContains($product1->id, $wishlistedIds);
        $this->assertNotContains($product2->id, $wishlistedIds);
        $this->assertContains($product3->id, $wishlistedIds);
    }

    // =====================
    // WISHLIST COUNT TESTS
    // =====================

    public function test_can_get_wishlist_count(): void
    {
        $products = Product::factory()->count(5)->create();

        foreach ($products as $product) {
            Wishlist::factory()->forUser($this->user)->create([
                'product_id' => $product->id,
            ]);
        }

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/wishlists/count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 5,
            ]);
    }

    // =====================
    // CLEAR WISHLIST TESTS
    // =====================

    public function test_can_clear_wishlist(): void
    {
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            Wishlist::factory()->forUser($this->user)->create([
                'product_id' => $product->id,
            ]);
        }

        $response = $this->authenticateCustomer()
            ->deleteJson('/api/customer/wishlists');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // All items should be soft deleted
        $this->assertEquals(0, Wishlist::where('user_id', $this->user->id)->count());
    }

    // =====================
    // MOVE TO CART TESTS
    // =====================

    public function test_can_get_product_info_for_move_to_cart(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $wishlist = Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/wishlists/{$wishlist->id}/move-to-cart");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'product_id',
                    'product',
                ],
            ]);
    }

    public function test_cannot_move_out_of_stock_product_to_cart(): void
    {
        $product = Product::factory()->outOfStock()->create();
        $wishlist = Wishlist::factory()->forUser($this->user)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/wishlists/{$wishlist->id}/move-to-cart");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_cannot_move_other_users_wishlist_to_cart(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $wishlist = Wishlist::factory()->forUser($otherUser)->create([
            'product_id' => $product->id,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson("/api/customer/wishlists/{$wishlist->id}/move-to-cart");

        $response->assertStatus(404);
    }
}
