<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cart Test
 *
 * Tests for shopping cart operations including
 * adding, updating, removing items, and synchronization.
 */
class CartTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->customer()->create();
    }

    protected function authenticateCustomer()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    // =====================
    // GET CART TESTS
    // =====================

    public function test_can_get_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'summary' => [
                    'subtotal',
                    'total_charges',
                    'total_taxes',
                    'total_discount',
                    'cart_total',
                    'items_count',
                ],
            ]);

        $this->assertEquals(0, $response->json('summary.items_count'));
    }

    public function test_can_get_cart_with_items(): void
    {
        $product = Product::factory()->create(['base_price' => 10000]);
        Cart::factory()->forUser($this->user)->forProduct($product)->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/cart');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('summary.items_count'));
    }

    public function test_unauthenticated_cannot_get_cart(): void
    {
        $response = $this->getJson('/api/customer/cart');
        $response->assertStatus(401);
    }

    // =====================
    // ADD TO CART TESTS
    // =====================

    public function test_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create([
            'base_price' => 5000,
            'available_stock' => 10,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product added to cart successfully.',
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_adding_same_product_increases_quantity(): void
    {
        $product = Product::factory()->create([
            'available_stock' => 10,
        ]);

        // Add first time
        $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 2,
            ]);

        // Add same product again
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 3,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 5, // 2 + 3
        ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = Product::factory()->create([
            'available_stock' => 5,
        ]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 10,
            ]);

        $response->assertStatus(400);
        $this->assertEquals(5, $response->json('available_stock'));
    }

    public function test_cannot_add_nonexistent_product(): void
    {
        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => 99999,
                'quantity' => 1,
            ]);

        $response->assertStatus(422);
    }

    public function test_add_to_cart_validates_quantity(): void
    {
        $product = Product::factory()->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    // =====================
    // UPDATE CART TESTS
    // =====================

    public function test_can_update_cart_quantity(): void
    {
        $product = Product::factory()->create(['available_stock' => 20]);
        $cartItem = Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity(2)
            ->create();

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/cart/{$cartItem->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cart updated successfully.',
            ]);

        $cartItem->refresh();
        $this->assertEquals(5, $cartItem->quantity);
    }

    public function test_cannot_update_to_exceed_stock(): void
    {
        $product = Product::factory()->create(['available_stock' => 5]);
        $cartItem = Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity(2)
            ->create();

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/cart/{$cartItem->id}", [
                'quantity' => 10,
            ]);

        $response->assertStatus(400);
        $this->assertEquals(5, $response->json('available_stock'));
    }

    public function test_cannot_update_other_users_cart(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $cartItem = Cart::factory()
            ->forUser($otherUser)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->putJson("/api/customer/cart/{$cartItem->id}", [
                'quantity' => 5,
            ]);

        $response->assertStatus(404);
    }

    public function test_update_returns_404_for_nonexistent_item(): void
    {
        $response = $this->authenticateCustomer()
            ->putJson('/api/customer/cart/99999', [
                'quantity' => 5,
            ]);

        $response->assertStatus(404);
    }

    // =====================
    // REMOVE FROM CART TESTS
    // =====================

    public function test_can_remove_item_from_cart(): void
    {
        $product = Product::factory()->create();
        $cartItem = Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/cart/{$cartItem->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Item removed from cart.',
            ]);

        $this->assertSoftDeleted('carts', ['id' => $cartItem->id]);
    }

    public function test_cannot_remove_other_users_cart_item(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $cartItem = Cart::factory()
            ->forUser($otherUser)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateCustomer()
            ->deleteJson("/api/customer/cart/{$cartItem->id}");

        $response->assertStatus(404);
    }

    // =====================
    // CLEAR CART TESTS
    // =====================

    public function test_can_clear_cart(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Cart::factory()->forUser($this->user)->forProduct($product1)->create();
        Cart::factory()->forUser($this->user)->forProduct($product2)->create();

        $response = $this->authenticateCustomer()
            ->deleteJson('/api/customer/cart');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(2, $response->json('items_removed'));
    }

    public function test_clear_cart_only_affects_own_cart(): void
    {
        $otherUser = User::factory()->customer()->create();
        $product = Product::factory()->create();

        Cart::factory()->forUser($this->user)->forProduct($product)->create();
        $otherCartItem = Cart::factory()->forUser($otherUser)->forProduct($product)->create();

        $this->authenticateCustomer()
            ->deleteJson('/api/customer/cart');

        // Other user's cart should still exist
        $this->assertDatabaseHas('carts', ['id' => $otherCartItem->id]);
    }

    // =====================
    // CART COUNT TESTS
    // =====================

    public function test_can_get_cart_count(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        Cart::factory()->forUser($this->user)->forProduct($product1)->create();
        Cart::factory()->forUser($this->user)->forProduct($product2)->create();

        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/cart/count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
            ]);
    }

    public function test_cart_count_returns_zero_for_empty_cart(): void
    {
        $response = $this->authenticateCustomer()
            ->getJson('/api/customer/cart/count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 0,
            ]);
    }

    // =====================
    // SYNC CART TESTS
    // =====================

    public function test_can_sync_cart_from_local_storage(): void
    {
        $product1 = Product::factory()->create(['available_stock' => 10]);
        $product2 = Product::factory()->create(['available_stock' => 10]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart/sync', [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'synced' => 2,
            ]);

        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
    }

    public function test_sync_merges_with_existing_cart(): void
    {
        $product = Product::factory()->create(['available_stock' => 20]);
        Cart::factory()
            ->forUser($this->user)
            ->forProduct($product)
            ->withQuantity(2)
            ->create();

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart/sync', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ]);

        $response->assertStatus(200);

        // Should be 2 + 3 = 5
        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    public function test_sync_adjusts_to_available_stock(): void
    {
        $product = Product::factory()->create(['available_stock' => 3]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart/sync', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10],
                ],
            ]);

        $response->assertStatus(200);

        // Should be adjusted to max 3
        $this->assertDatabaseHas('carts', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_sync_returns_errors_for_invalid_products(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);

        $response = $this->authenticateCustomer()
            ->postJson('/api/customer/cart/sync', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                    ['product_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422);
    }
}
