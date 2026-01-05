<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Order;
use App\Models\orderProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Dashboard Test
 *
 * Tests for admin dashboard and reporting features
 * including overview, sales reports, and analytics.
 */
class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected Country $country;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
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

    protected function createProduct(array $attrs = []): Product
    {
        return Product::factory()->create(array_merge([
            'category_id' => $this->category->id,
        ], $attrs));
    }

    // =====================
    // DASHBOARD OVERVIEW TESTS
    // =====================

    public function test_can_get_dashboard_overview(): void
    {
        $this->createOrder(['order_status' => 'delivered', 'payment_status' => 'paid', 'order_total' => 10000]);
        $this->createOrder(['order_status' => 'pending', 'payment_status' => 'pending', 'order_total' => 5000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'period_days',
                'date_range' => ['start', 'end'],
                'data' => [
                    'revenue' => [
                        'total',
                        'previous_period',
                        'growth_percent',
                        'average_order_value',
                    ],
                    'orders' => [
                        'total',
                        'by_status',
                        'by_payment_status',
                        'pending_actions',
                    ],
                    'customers',
                    'products',
                ],
            ]);
    }

    public function test_dashboard_accepts_period_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/dashboard?period=7');

        $response->assertStatus(200);
        $this->assertEquals(7, $response->json('period_days'));
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_dashboard_shows_correct_revenue(): void
    {
        $this->createOrder(['payment_status' => 'paid', 'order_total' => 10000]);
        $this->createOrder(['payment_status' => 'paid', 'order_total' => 15000]);
        $this->createOrder(['payment_status' => 'pending', 'order_total' => 5000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $this->assertEquals(25000, $response->json('data.revenue.total'));
    }

    public function test_dashboard_shows_order_counts(): void
    {
        $this->createOrder(['order_status' => 'pending']);
        $this->createOrder(['order_status' => 'pending']);
        $this->createOrder(['order_status' => 'delivered']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('data.orders.total'));
        $this->assertEquals(2, $response->json('data.orders.by_status.pending'));
        $this->assertEquals(1, $response->json('data.orders.by_status.delivered'));
    }

    // =====================
    // SALES REPORT TESTS
    // =====================

    public function test_can_get_sales_report(): void
    {
        $this->createOrder(['payment_status' => 'paid', 'order_total' => 10000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/sales');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'period_days',
                'group_by',
                'data' => [
                    'timeline',
                    'by_payment_method',
                    'by_customer_type',
                    'summary' => [
                        'total_orders',
                        'total_revenue',
                        'avg_order_value',
                    ],
                ],
            ]);
    }

    public function test_sales_report_accepts_period_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/sales?period=7');

        $response->assertStatus(200);
        $this->assertEquals(7, $response->json('period_days'));
    }

    public function test_sales_report_accepts_group_by_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/sales?group_by=month');

        $response->assertStatus(200);
        $this->assertEquals('month', $response->json('group_by'));
    }

    // =====================
    // TOP PRODUCTS TESTS
    // =====================

    public function test_can_get_top_products(): void
    {
        $product = $this->createProduct(['name' => 'Gold Ring']);
        $order = $this->createOrder(['payment_status' => 'paid']);

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create(['quantity' => 5, 'price' => 1000, 'total' => 5000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/top-products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'period_days',
                'sort_by',
                'data',
            ]);
    }

    public function test_top_products_accepts_sort_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/top-products?sort_by=quantity');

        $response->assertStatus(200);
        $this->assertEquals('quantity', $response->json('sort_by'));
    }

    public function test_top_products_respects_limit(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/top-products?limit=5');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(5, count($response->json('data')));
    }

    // =====================
    // TOP CUSTOMERS TESTS
    // =====================

    public function test_can_get_top_customers(): void
    {
        $this->createOrder(['payment_status' => 'paid', 'order_total' => 10000]);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/top-customers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'period_days',
                'customer_type',
                'data',
            ]);
    }

    public function test_top_customers_can_filter_by_type(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/top-customers?customer_type=partner');

        $response->assertStatus(200);
        $this->assertEquals('partner', $response->json('customer_type'));
    }

    // =====================
    // REVENUE TRENDS TESTS
    // =====================

    public function test_can_get_revenue_trends(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/revenue-trends');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'months',
                'data',
                'summary' => [
                    'total_revenue',
                    'total_orders',
                    'avg_monthly_revenue',
                ],
            ]);
    }

    public function test_revenue_trends_accepts_months_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/revenue-trends?months=6');

        $response->assertStatus(200);
        $this->assertEquals(6, $response->json('months'));
        $this->assertCount(6, $response->json('data'));
    }

    public function test_revenue_trends_includes_growth_percent(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/revenue-trends?months=3');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $month) {
            $this->assertArrayHasKey('growth_percent', $month);
        }
    }

    // =====================
    // RECENT ORDERS TESTS
    // =====================

    public function test_can_get_recent_orders(): void
    {
        $this->createOrder();
        $this->createOrder();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/recent-orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_recent_orders_respects_limit(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createOrder();
        }

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/recent-orders?limit=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_recent_orders_includes_customer(): void
    {
        $this->createOrder();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/recent-orders');

        $response->assertStatus(200);
        $this->assertArrayHasKey('customer', $response->json('data.0'));
    }

    // =====================
    // RECENT ACTIVITIES TESTS
    // =====================

    public function test_can_get_recent_activities(): void
    {
        // Create some activity by adjusting stock
        $product = $this->createProduct(['available_stock' => 10]);

        activity('inventory')
            ->performedOn($product)
            ->withProperties(['test' => true])
            ->log('Test activity');

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/recent-activities');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_recent_activities_can_filter_by_log_name(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/recent-activities?log_name=inventory');

        $response->assertStatus(200);
    }

    // =====================
    // EXPORT REPORT TESTS
    // =====================

    public function test_can_export_sales_report(): void
    {
        $this->createOrder(['payment_status' => 'paid']);

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/export?type=sales');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report_type' => 'sales',
            ])
            ->assertJsonStructure([
                'success',
                'report_type',
                'period_days',
                'date_range',
                'record_count',
                'data',
            ]);
    }

    public function test_can_export_orders_report(): void
    {
        $this->createOrder();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/export?type=orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report_type' => 'orders',
            ]);
    }

    public function test_can_export_products_report(): void
    {
        $product = $this->createProduct();
        $order = $this->createOrder();

        orderProduct::factory()
            ->forOrder($order)
            ->forProduct($product)
            ->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/export?type=products');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report_type' => 'products',
            ]);
    }

    public function test_can_export_customers_report(): void
    {
        User::factory()->customer()->create();

        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/export?type=customers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report_type' => 'customers',
            ]);
    }

    public function test_export_accepts_period_parameter(): void
    {
        $response = $this->authenticateAdmin()
            ->getJson('/api/admin/reports/export?period=7');

        $response->assertStatus(200);
        $this->assertEquals(7, $response->json('period_days'));
    }
}
