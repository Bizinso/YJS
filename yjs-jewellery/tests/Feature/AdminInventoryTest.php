<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Inventory Test
 *
 * Tests for inventory management including
 * stock listing, adjustments, and alerts.
 */
class AdminInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'employee']);
        $this->category = Category::factory()->create();
    }

    protected function authenticateAdmin()
    {
        return $this->actingAs($this->admin, 'sanctum');
    }

    protected function createProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => $this->category->id,
        ], $attrs));
    }

    // =====================
    // INVENTORY LIST TESTS
    // =====================

    public function test_can_get_inventory_list(): void
    {
        $this->createProduct(['available_stock' => 50]);
        $this->createProduct(['available_stock' => 10]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory');

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

    public function test_can_filter_by_stock_status(): void
    {
        $this->createProduct(['available_stock' => 0]);
        $this->createProduct(['available_stock' => 5]);
        $this->createProduct(['available_stock' => 50]);

        // Out of stock
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory?stock_status=out_of_stock');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));

        // Low stock (1-10)
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory?stock_status=low_stock');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_search_inventory(): void
    {
        $this->createProduct(['name' => 'Gold Ring']);
        $this->createProduct(['name' => 'Silver Necklace']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory?search=Gold');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_unauthenticated_cannot_access_inventory(): void
    {
        $response = $this->getJson('/api/admin/inventory');
        $response->assertStatus(401);
    }

    // =====================
    // LOW STOCK TESTS
    // =====================

    public function test_can_get_low_stock_products(): void
    {
        $this->createProduct(['available_stock' => 5, 'status' => 'active']);
        $this->createProduct(['available_stock' => 8, 'status' => 'active']);
        $this->createProduct(['available_stock' => 50, 'status' => 'active']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory/low-stock?threshold=10');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'threshold' => 10,
                'count' => 2,
            ]);
    }

    public function test_low_stock_uses_default_threshold(): void
    {
        $this->createProduct(['available_stock' => 5, 'status' => 'active']);
        $this->createProduct(['available_stock' => 15, 'status' => 'active']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory/low-stock');

        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('threshold'));
        $this->assertEquals(1, $response->json('count'));
    }

    // =====================
    // OUT OF STOCK TESTS
    // =====================

    public function test_can_get_out_of_stock_products(): void
    {
        $this->createProduct(['available_stock' => 0]);
        $this->createProduct(['available_stock' => 0]);
        $this->createProduct(['available_stock' => 10]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory/out-of-stock');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 2,
            ]);
    }

    // =====================
    // STOCK ADJUSTMENT TESTS
    // =====================

    public function test_can_adjust_stock_positive(): void
    {
        $product = $this->createProduct(['available_stock' => 10]);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => 5,
                'reason' => 'Stock received from supplier',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'old_stock' => 10,
                    'adjustment' => 5,
                    'new_stock' => 15,
                ],
            ]);

        $product->refresh();
        $this->assertEquals(15, $product->available_stock);
    }

    public function test_can_adjust_stock_negative(): void
    {
        $product = $this->createProduct(['available_stock' => 10]);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => -3,
                'reason' => 'Damaged stock removed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'old_stock' => 10,
                    'adjustment' => -3,
                    'new_stock' => 7,
                ],
            ]);
    }

    public function test_cannot_reduce_stock_below_zero(): void
    {
        $product = $this->createProduct(['available_stock' => 5]);

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => -10,
                'reason' => 'Test adjustment',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_stock_adjustment_requires_reason(): void
    {
        $product = $this->createProduct();

        $response = $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // =====================
    // SET STOCK TESTS
    // =====================

    public function test_can_set_stock_directly(): void
    {
        $product = $this->createProduct(['available_stock' => 10]);

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/inventory/{$product->id}/stock", [
                'stock' => 25,
                'reason' => 'Physical count adjustment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'old_stock' => 10,
                    'new_stock' => 25,
                ],
            ]);

        $product->refresh();
        $this->assertEquals(25, $product->available_stock);
    }

    public function test_cannot_set_negative_stock(): void
    {
        $product = $this->createProduct();

        $response = $this->authenticateAdmin()
            ->putJson("/api/admin/inventory/{$product->id}/stock", [
                'stock' => -5,
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // BULK UPDATE TESTS
    // =====================

    public function test_can_bulk_update_stock(): void
    {
        $product1 = $this->createProduct(['available_stock' => 10]);
        $product2 = $this->createProduct(['available_stock' => 20]);

        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/inventory/bulk-update', [
                'updates' => [
                    ['product_id' => $product1->id, 'stock' => 15],
                    ['product_id' => $product2->id, 'stock' => 25],
                ],
                'reason' => 'Quarterly inventory adjustment',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(15, $product1->available_stock);
        $this->assertEquals(25, $product2->available_stock);
    }

    public function test_bulk_update_validates_products(): void
    {
        $response = $this->authenticateAdmin()
            ->postJson('/api/admin/inventory/bulk-update', [
                'updates' => [
                    ['product_id' => 99999, 'stock' => 10],
                ],
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    // =====================
    // STOCK HISTORY TESTS
    // =====================

    public function test_can_get_stock_history(): void
    {
        $product = $this->createProduct(['available_stock' => 10]);

        // Make some adjustments
        $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => 5,
                'reason' => 'First adjustment',
            ]);

        $this->authenticateAdmin()
            ->postJson("/api/admin/inventory/{$product->id}/adjust", [
                'adjustment' => -2,
                'reason' => 'Second adjustment',
            ]);

        $response = $this->authenticateAdmin()
            ->getJson("/api/admin/inventory/{$product->id}/history");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'product' => ['id', 'name', 'sku', 'current_stock'],
                'history',
            ]);
    }

    public function test_returns_404_for_nonexistent_product_history(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory/99999/history');

        $response->assertStatus(404);
    }

    // =====================
    // SUMMARY TESTS
    // =====================

    public function test_can_get_inventory_summary(): void
    {
        $this->createProduct(['available_stock' => 0, 'status' => 'active']);
        $this->createProduct(['available_stock' => 5, 'status' => 'active']);
        $this->createProduct(['available_stock' => 50, 'status' => 'active']);
        $this->createProduct(['available_stock' => 100, 'status' => 'inactive']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/inventory/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overview' => [
                        'total_products',
                        'active_products',
                        'out_of_stock',
                        'low_stock',
                        'total_stock_value',
                    ],
                    'by_category',
                ],
            ]);

        $this->assertEquals(4, $response->json('data.overview.total_products'));
        $this->assertEquals(3, $response->json('data.overview.active_products'));
        $this->assertEquals(1, $response->json('data.overview.out_of_stock'));
    }
}
