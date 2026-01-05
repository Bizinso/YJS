<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Order;
use App\Models\orderProduct;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Partner Order Test
 *
 * Tests for partner order management including
 * listing, viewing, cancellation, and tracking.
 */
class PartnerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Partner $partner;
    protected Country $country;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->user = User::factory()->partner()->create();
        $this->partner = Partner::factory()
            ->forUser($this->user)
            ->approved()
            ->create();
    }

    protected function authenticatePartner()
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    protected function createOrderWithProducts(array $orderAttributes = [], int $productCount = 2): Order
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->create(array_merge(['country_id' => $this->country->id], $orderAttributes));

        for ($i = 0; $i < $productCount; $i++) {
            $product = Product::factory()->create();
            orderProduct::factory()
                ->forOrder($order)
                ->forProduct($product)
                ->create();
        }

        return $order;
    }

    // =====================
    // ORDER LIST TESTS
    // =====================

    public function test_can_get_order_list(): void
    {
        $this->createOrderWithProducts();
        $this->createOrderWithProducts();

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders');

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

    public function test_order_list_only_shows_own_orders(): void
    {
        $this->createOrderWithProducts();

        // Another partner's order
        $otherUser = User::factory()->partner()->create();
        Order::factory()->forUser($otherUser)->create(['country_id' => $this->country->id]);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_can_filter_orders_by_status(): void
    {
        $this->createOrderWithProducts(['order_status' => 'pending']);
        $this->createOrderWithProducts(['order_status' => 'delivered']);
        $this->createOrderWithProducts(['order_status' => 'delivered']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders?status=delivered');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('pagination.total'));
    }

    public function test_can_search_orders_by_code(): void
    {
        $order = $this->createOrderWithProducts(['custom_order_code' => 'YJS-SEARCH123']);
        $this->createOrderWithProducts(['custom_order_code' => 'YJS-OTHER456']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders?search=SEARCH123');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('pagination.total'));
    }

    public function test_unapproved_partner_cannot_get_orders(): void
    {
        $this->partner->update(['status' => 'pending']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders');

        $response->assertStatus(403);
    }

    // =====================
    // ORDER DETAILS TESTS
    // =====================

    public function test_can_get_order_details(): void
    {
        $order = $this->createOrderWithProducts();

        $response = $this->authenticatePartner()
            ->getJson("/api/partner/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_code',
                    'order_status',
                    'items',
                    'pricing',
                ],
            ]);
    }

    public function test_cannot_get_other_partners_order(): void
    {
        $otherUser = User::factory()->partner()->create();
        $order = Order::factory()->forUser($otherUser)->create(['country_id' => $this->country->id]);

        $response = $this->authenticatePartner()
            ->getJson("/api/partner/orders/{$order->id}");

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders/99999');

        $response->assertStatus(404);
    }

    // =====================
    // CANCEL ORDER TESTS
    // =====================

    public function test_can_cancel_pending_order(): void
    {
        $order = $this->createOrderWithProducts(['order_status' => 'pending']);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/cancel", [
                'reason' => 'Changed my mind about the order',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $order->refresh();
        $this->assertEquals('cancelled', $order->order_status);
    }

    public function test_cannot_cancel_shipped_order(): void
    {
        $order = $this->createOrderWithProducts(['order_status' => 'shipped']);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/cancel", [
                'reason' => 'Want to cancel',
            ]);

        $response->assertStatus(422);
    }

    public function test_cancel_requires_reason(): void
    {
        $order = $this->createOrderWithProducts(['order_status' => 'pending']);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/cancel", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_cannot_cancel_other_partners_order(): void
    {
        $otherUser = User::factory()->partner()->create();
        $order = Order::factory()->forUser($otherUser)->create([
            'country_id' => $this->country->id,
            'order_status' => 'pending',
        ]);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/cancel", [
                'reason' => 'Want to cancel',
            ]);

        $response->assertStatus(404);
    }

    public function test_cancel_restores_product_stock(): void
    {
        $product = Product::factory()->create(['available_stock' => 10]);
        $order = Order::factory()
            ->forUser($this->user)
            ->create([
                'country_id' => $this->country->id,
                'order_status' => 'pending',
            ]);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create(['quantity' => 3]);

        $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/cancel", [
                'reason' => 'Cancelling order',
            ]);

        $product->refresh();
        $this->assertEquals(13, $product->available_stock);
    }

    // =====================
    // ORDER TRACKING TESTS
    // =====================

    public function test_can_get_order_tracking(): void
    {
        $order = $this->createOrderWithProducts(['order_status' => 'shipped']);

        $response = $this->authenticatePartner()
            ->getJson("/api/partner/orders/{$order->id}/tracking");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'order_code',
                    'order_status',
                    'timeline',
                ],
            ]);
    }

    public function test_cannot_get_tracking_for_other_partners_order(): void
    {
        $otherUser = User::factory()->partner()->create();
        $order = Order::factory()->forUser($otherUser)->create(['country_id' => $this->country->id]);

        $response = $this->authenticatePartner()
            ->getJson("/api/partner/orders/{$order->id}/tracking");

        $response->assertStatus(404);
    }

    // =====================
    // ORDER STATISTICS TESTS
    // =====================

    public function test_can_get_order_statistics(): void
    {
        $this->createOrderWithProducts(['order_status' => 'pending']);
        $this->createOrderWithProducts(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 10000]);
        $this->createOrderWithProducts(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 15000]);
        $this->createOrderWithProducts(['order_status' => 'cancelled']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_orders' => 4,
                    'pending_orders' => 1,
                    'delivered_orders' => 2,
                    'cancelled_orders' => 1,
                ],
            ]);

        $this->assertEquals(25000, $response->json('data.total_spent'));
    }

    public function test_statistics_only_include_own_orders(): void
    {
        $this->createOrderWithProducts();

        // Another partner's orders
        $otherUser = User::factory()->partner()->create();
        Order::factory()->forUser($otherUser)->count(3)->create(['country_id' => $this->country->id]);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/orders/statistics');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total_orders'));
    }

    // =====================
    // REORDER TESTS
    // =====================

    public function test_can_get_reorder_details(): void
    {
        $product = Product::factory()->create(['available_stock' => 100]);
        $order = Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create(['quantity' => 5]);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/reorder");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'available_items',
                    'unavailable_items',
                    'original_order_code',
                ],
            ]);
    }

    public function test_reorder_shows_out_of_stock_items(): void
    {
        $product = Product::factory()->outOfStock()->create();
        $order = Order::factory()
            ->forUser($this->user)
            ->delivered()
            ->create(['country_id' => $this->country->id]);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create(['quantity' => 5]);

        $response = $this->authenticatePartner()
            ->postJson("/api/partner/orders/{$order->id}/reorder");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.unavailable_items'));
    }
}
