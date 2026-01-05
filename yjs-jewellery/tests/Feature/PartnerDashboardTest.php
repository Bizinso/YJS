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
 * Partner Dashboard Test
 *
 * Tests for partner dashboard and analytics endpoints.
 */
class PartnerDashboardTest extends TestCase
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

    protected function createOrderWithProducts(array $orderAttributes = []): Order
    {
        $order = Order::factory()
            ->forUser($this->user)
            ->create(array_merge(['country_id' => $this->country->id], $orderAttributes));

        $product = Product::factory()->create();
        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create();

        return $order;
    }

    // =====================
    // DASHBOARD TESTS
    // =====================

    public function test_can_get_dashboard_data(): void
    {
        $this->createOrderWithProducts(['order_status' => 'pending']);
        $this->createOrderWithProducts(['order_status' => 'delivered', 'payment_status' => 'paid']);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'partner' => ['business_name', 'status'],
                    'overview' => ['total_orders', 'total_spent', 'pending_orders', 'delivered_orders'],
                    'recent_orders',
                    'spending_trend',
                    'status_distribution',
                ],
            ]);
    }

    public function test_dashboard_shows_correct_overview(): void
    {
        $this->createOrderWithProducts(['order_status' => 'pending']);
        $this->createOrderWithProducts(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 10000]);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('data.overview.total_orders'));
        $this->assertEquals(10000, $response->json('data.overview.total_spent'));
        $this->assertEquals(1, $response->json('data.overview.pending_orders'));
        $this->assertEquals(1, $response->json('data.overview.delivered_orders'));
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/partner/dashboard');

        $response->assertStatus(401);
    }

    // =====================
    // ORDER ANALYTICS TESTS
    // =====================

    public function test_can_get_order_analytics(): void
    {
        $this->createOrderWithProducts();

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/order-analytics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period_days',
                    'daily_orders',
                    'top_products',
                    'average_order_value',
                    'orders_per_month',
                ],
            ]);
    }

    public function test_order_analytics_accepts_period_parameter(): void
    {
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/order-analytics?period=90');

        $response->assertStatus(200);
        $this->assertEquals(90, $response->json('data.period_days'));
    }

    // =====================
    // SPENDING ANALYTICS TESTS
    // =====================

    public function test_can_get_spending_analytics(): void
    {
        $this->createOrderWithProducts(['payment_status' => 'paid', 'order_total' => 15000]);

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/spending-analytics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'year',
                    'monthly_spending',
                    'category_spending',
                    'year_total',
                    'year_over_year_growth',
                ],
            ]);
    }

    public function test_spending_analytics_accepts_year_parameter(): void
    {
        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/spending-analytics?year=2025');

        $response->assertStatus(200);
        $this->assertEquals(2025, $response->json('data.year'));
    }

    // =====================
    // FREQUENT PRODUCTS TESTS
    // =====================

    public function test_can_get_frequent_products(): void
    {
        $product = Product::factory()->create(['name' => 'Gold Ring']);

        // Create multiple orders with the same product
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()
                ->forUser($this->user)
                ->create(['country_id' => $this->country->id]);

            orderProduct::factory()
                ->forOrder($order)
                ->forProduct($product)
                ->create(['quantity' => 2]);
        }

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/frequent-products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $frequentProducts = $response->json('data');
        $this->assertNotEmpty($frequentProducts);
        $this->assertEquals('Gold Ring', $frequentProducts[0]->name ?? $frequentProducts[0]['name']);
    }

    public function test_frequent_products_ordered_by_frequency(): void
    {
        $product1 = Product::factory()->create(['name' => 'Product A']);
        $product2 = Product::factory()->create(['name' => 'Product B']);

        // Product B ordered more times
        for ($i = 0; $i < 2; $i++) {
            $order = Order::factory()->forUser($this->user)->create(['country_id' => $this->country->id]);
            orderProduct::factory()->forOrder($order)->forProduct($product1)->create();
        }

        for ($i = 0; $i < 5; $i++) {
            $order = Order::factory()->forUser($this->user)->create(['country_id' => $this->country->id]);
            orderProduct::factory()->forOrder($order)->forProduct($product2)->create();
        }

        $response = $this->authenticatePartner()
            ->getJson('/api/partner/dashboard/frequent-products');

        $response->assertStatus(200);
        $products = $response->json('data');

        // Product B should be first (ordered 5 times vs 2 times)
        $this->assertEquals('Product B', $products[0]->name ?? $products[0]['name']);
    }
}
