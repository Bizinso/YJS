<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Dashboard Controller
 *
 * Provides dashboard overview, reports, and analytics
 * for admin users to monitor business performance.
 */
class AdminDashboardController extends Controller
{
    /**
     * Get dashboard overview.
     *
     * Provides key metrics including:
     * - Total revenue
     * - Order counts by status
     * - Customer statistics
     * - Recent activity
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $period = $request->input('period', 30);

        $startDate = now()->subDays($period)->startOfDay();
        $endDate = now()->endOfDay();

        // Revenue metrics
        $revenueMetrics = $this->getRevenueMetrics($startDate, $endDate);

        // Order metrics
        $orderMetrics = $this->getOrderMetrics($startDate, $endDate);

        // Customer metrics
        $customerMetrics = $this->getCustomerMetrics($startDate, $endDate);

        // Product metrics
        $productMetrics = $this->getProductMetrics();

        return response()->json([
            'success' => true,
            'period_days' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'data' => [
                'revenue' => $revenueMetrics,
                'orders' => $orderMetrics,
                'customers' => $customerMetrics,
                'products' => $productMetrics,
            ],
        ]);
    }

    /**
     * Get revenue metrics.
     */
    private function getRevenueMetrics($startDate, $endDate): array
    {
        // Total revenue from delivered/paid orders
        $totalRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('order_total');

        // Previous period for comparison
        $previousStart = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousEnd = $startDate->copy()->subDay();

        $previousRevenue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('order_total');

        $growthPercent = $previousRevenue > 0
            ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
            : 0;

        // Average order value
        $avgOrderValue = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->avg('order_total') ?? 0;

        return [
            'total' => round($totalRevenue, 2),
            'previous_period' => round($previousRevenue, 2),
            'growth_percent' => $growthPercent,
            'average_order_value' => round($avgOrderValue, 2),
        ];
    }

    /**
     * Get order metrics.
     */
    private function getOrderMetrics($startDate, $endDate): array
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();

        $statusCounts = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();

        $paymentStatusCounts = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        // Pending actions
        $pendingActions = [
            'pending_orders' => Order::where('order_status', 'pending')->count(),
            'pending_payments' => Order::where('payment_status', 'pending')
                ->whereIn('order_status', ['confirmed', 'processing'])
                ->count(),
            'ready_to_ship' => Order::where('order_status', 'processing')
                ->where('payment_status', 'paid')
                ->count(),
        ];

        return [
            'total' => $totalOrders,
            'by_status' => $statusCounts,
            'by_payment_status' => $paymentStatusCounts,
            'pending_actions' => $pendingActions,
        ];
    }

    /**
     * Get customer metrics.
     */
    private function getCustomerMetrics($startDate, $endDate): array
    {
        $newCustomers = User::where('user_type', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalCustomers = User::where('user_type', 'customer')
            ->where('status', 'active')
            ->count();

        $newPartners = User::where('user_type', 'partner')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalPartners = User::where('user_type', 'partner')
            ->where('status', 'active')
            ->count();

        $pendingPartners = User::where('user_type', 'partner')
            ->where('partner_status', 'pending')
            ->count();

        return [
            'new_customers' => $newCustomers,
            'total_customers' => $totalCustomers,
            'new_partners' => $newPartners,
            'total_partners' => $totalPartners,
            'pending_partner_approvals' => $pendingPartners,
        ];
    }

    /**
     * Get product metrics.
     */
    private function getProductMetrics(): array
    {
        return [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'out_of_stock' => Product::where('available_stock', 0)->count(),
            'low_stock' => Product::whereBetween('available_stock', [1, 10])
                ->where('status', 'active')
                ->count(),
        ];
    }

    /**
     * Get sales report.
     *
     * Detailed sales breakdown with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function salesReport(Request $request): JsonResponse
    {
        $period = $request->input('period', 30);
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        $startDate = now()->subDays($period)->startOfDay();
        $endDate = now()->endOfDay();

        // Daily/Weekly/Monthly sales breakdown
        $dateFormat = match ($groupBy) {
            'week' => '%Y-%W',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        // For SQLite compatibility, use date() function
        $salesByPeriod = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw("date(created_at) as date"),
                DB::raw('count(*) as order_count'),
                DB::raw('sum(order_total) as revenue'),
                DB::raw('avg(order_total) as avg_order_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Sales by payment method
        $salesByPaymentMethod = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('count(*) as order_count'),
                DB::raw('sum(order_total) as revenue')
            )
            ->groupBy('payment_method')
            ->get();

        // Sales by customer type
        $salesByCustomerType = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'customer_type',
                DB::raw('count(*) as order_count'),
                DB::raw('sum(order_total) as revenue')
            )
            ->groupBy('customer_type')
            ->get();

        return response()->json([
            'success' => true,
            'period_days' => $period,
            'group_by' => $groupBy,
            'data' => [
                'timeline' => $salesByPeriod,
                'by_payment_method' => $salesByPaymentMethod,
                'by_customer_type' => $salesByCustomerType,
                'summary' => [
                    'total_orders' => $salesByPeriod->sum('order_count'),
                    'total_revenue' => round($salesByPeriod->sum('revenue'), 2),
                    'avg_order_value' => $salesByPeriod->count() > 0
                        ? round($salesByPeriod->sum('revenue') / max($salesByPeriod->sum('order_count'), 1), 2)
                        : 0,
                ],
            ],
        ]);
    }

    /**
     * Get top products report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topProducts(Request $request): JsonResponse
    {
        $period = $request->input('period', 30);
        $limit = min($request->input('limit', 10), 50);
        $sortBy = $request->input('sort_by', 'revenue'); // revenue, quantity, orders

        $startDate = now()->subDays($period)->startOfDay();
        $endDate = now()->endOfDay();

        $orderBy = match ($sortBy) {
            'quantity' => 'total_quantity',
            'orders' => 'order_count',
            default => 'total_revenue',
        };

        $topProducts = DB::table('order_products')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNull('orders.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.main_image',
                DB::raw('count(distinct orders.id) as order_count'),
                DB::raw('sum(order_products.quantity) as total_quantity'),
                DB::raw('sum(order_products.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.main_image')
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'period_days' => $period,
            'sort_by' => $sortBy,
            'data' => $topProducts,
        ]);
    }

    /**
     * Get top customers report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $period = $request->input('period', 30);
        $limit = min($request->input('limit', 10), 50);
        $customerType = $request->input('customer_type'); // customer, partner

        $startDate = now()->subDays($period)->startOfDay();
        $endDate = now()->endOfDay();

        $query = Order::join('users', 'orders.customer_id', '=', 'users.id')
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNull('orders.deleted_at');

        if ($customerType) {
            $query->where('users.user_type', $customerType);
        }

        $topCustomers = $query->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                'users.user_type',
                DB::raw('count(orders.id) as order_count'),
                DB::raw('sum(orders.order_total) as total_spent'),
                DB::raw('avg(orders.order_total) as avg_order_value')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.user_type')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'period_days' => $period,
            'customer_type' => $customerType ?? 'all',
            'data' => $topCustomers,
        ]);
    }

    /**
     * Get revenue trends.
     *
     * Month-over-month revenue comparison.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revenueTrends(Request $request): JsonResponse
    {
        $months = min($request->input('months', 12), 24);

        $trends = [];
        $now = now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $revenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('order_total');

            $orderCount = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $trends[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->format('M Y'),
                'revenue' => round($revenue, 2),
                'order_count' => $orderCount,
                'avg_order_value' => $orderCount > 0 ? round($revenue / $orderCount, 2) : 0,
            ];
        }

        // Calculate growth rates
        for ($i = 1; $i < count($trends); $i++) {
            $previousRevenue = $trends[$i - 1]['revenue'];
            $currentRevenue = $trends[$i]['revenue'];

            $trends[$i]['growth_percent'] = $previousRevenue > 0
                ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2)
                : 0;
        }

        if (count($trends) > 0) {
            $trends[0]['growth_percent'] = 0;
        }

        return response()->json([
            'success' => true,
            'months' => $months,
            'data' => $trends,
            'summary' => [
                'total_revenue' => round(array_sum(array_column($trends, 'revenue')), 2),
                'total_orders' => array_sum(array_column($trends, 'order_count')),
                'avg_monthly_revenue' => count($trends) > 0
                    ? round(array_sum(array_column($trends, 'revenue')) / count($trends), 2)
                    : 0,
            ],
        ]);
    }

    /**
     * Get recent orders for dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentOrders(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 10), 50);

        $orders = Order::with(['customer:id,first_name,last_name,email,user_type'])
            ->select([
                'id', 'custom_order_code', 'customer_id', 'order_status',
                'payment_status', 'order_total', 'created_at'
            ])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Get recent activities.
     *
     * Uses Spatie Activity Log to show recent system activities.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recentActivities(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 20), 100);
        $logName = $request->input('log_name'); // order, inventory, user, etc.

        $query = \Spatie\Activitylog\Models\Activity::with('causer:id,first_name,last_name,email')
            ->orderByDesc('created_at');

        if ($logName) {
            $query->where('log_name', $logName);
        }

        $activities = $query->limit($limit)->get(['id', 'log_name', 'description', 'causer_type', 'causer_id', 'subject_type', 'subject_id', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
    }

    /**
     * Export report data.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exportReport(Request $request): JsonResponse
    {
        $reportType = $request->input('type', 'sales');
        $period = $request->input('period', 30);
        $format = $request->input('format', 'json');

        $startDate = now()->subDays($period)->startOfDay();
        $endDate = now()->endOfDay();

        $data = match ($reportType) {
            'orders' => $this->getOrdersExportData($startDate, $endDate),
            'products' => $this->getProductsExportData($startDate, $endDate),
            'customers' => $this->getCustomersExportData($startDate, $endDate),
            default => $this->getSalesExportData($startDate, $endDate),
        };

        return response()->json([
            'success' => true,
            'report_type' => $reportType,
            'period_days' => $period,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'record_count' => count($data),
            'data' => $data,
        ]);
    }

    /**
     * Get sales export data.
     */
    private function getSalesExportData($startDate, $endDate): array
    {
        return Order::with(['customer:id,first_name,last_name,email'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select([
                'id', 'custom_order_code', 'customer_id', 'customer_type',
                'order_status', 'payment_status', 'payment_method',
                'order_subtotal', 'shipping_charges', 'total_taxes', 'order_total',
                'created_at'
            ])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get orders export data.
     */
    private function getOrdersExportData($startDate, $endDate): array
    {
        return Order::with([
                'customer:id,first_name,last_name,email,phone',
                'orderProducts.product:id,name,sku'
            ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get products export data.
     */
    private function getProductsExportData($startDate, $endDate): array
    {
        return DB::table('order_products')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNull('orders.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('count(distinct orders.id) as order_count'),
                DB::raw('sum(order_products.quantity) as total_quantity'),
                DB::raw('sum(order_products.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->get()
            ->toArray();
    }

    /**
     * Get customers export data.
     */
    private function getCustomersExportData($startDate, $endDate): array
    {
        return User::whereIn('user_type', ['customer', 'partner'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select([
                'id', 'first_name', 'last_name', 'email', 'phone',
                'user_type', 'status', 'created_at'
            ])
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }
}
