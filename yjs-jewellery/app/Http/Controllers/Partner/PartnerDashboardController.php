<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Partner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Partner Dashboard Controller
 *
 * Provides dashboard data and analytics for B2B partners
 * including order statistics, spending trends, and product insights.
 *
 * @package App\Http\Controllers\Partner
 */
class PartnerDashboardController extends Controller
{
    /**
     * Get comprehensive dashboard data.
     *
     * Returns all key metrics for the partner dashboard including
     * order statistics, recent orders, and spending trends.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner profile not found.',
            ], 404);
        }

        $orders = Order::where('customer_id', $user->id);

        // Overview statistics
        $overview = [
            'total_orders' => (clone $orders)->count(),
            'total_spent' => (clone $orders)->where('payment_status', 'paid')->sum('order_total'),
            'pending_orders' => (clone $orders)->whereIn('order_status', ['pending', 'confirmed', 'processing'])->count(),
            'delivered_orders' => (clone $orders)->where('order_status', 'delivered')->count(),
        ];

        // Recent orders
        $recentOrders = (clone $orders)
            ->with('orderProducts.product:id,name,main_image')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'order_date' => $order->created_at->format('Y-m-d'),
                    'status' => $order->order_status,
                    'status_label' => $order->status_label,
                    'total' => $order->order_total,
                    'items_count' => $order->orderProducts->count(),
                ];
            });

        // Monthly spending trend (last 6 months)
        $spendingTrend = $this->getMonthlySpendingTrend($user->id);

        // Order status distribution
        $statusDistribution = (clone $orders)
            ->select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'partner' => [
                    'business_name' => $partner->business_name,
                    'status' => $partner->status,
                    'member_since' => $partner->created_at->format('F Y'),
                ],
                'overview' => $overview,
                'recent_orders' => $recentOrders,
                'spending_trend' => $spendingTrend,
                'status_distribution' => $statusDistribution,
            ],
        ]);
    }

    /**
     * Get detailed order analytics.
     *
     * Provides in-depth order analytics with various time periods.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function orderAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $period = $request->input('period', '30'); // days

        $startDate = Carbon::now()->subDays((int) $period);
        $orders = Order::where('customer_id', $user->id)
            ->where('created_at', '>=', $startDate);

        // Daily order counts
        $dailyOrders = (clone $orders)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as orders'),
                DB::raw('SUM(CASE WHEN payment_status = "paid" THEN order_total ELSE 0 END) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top ordered products
        $topProducts = DB::table('order_products')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->where('orders.customer_id', $user->id)
            ->where('orders.created_at', '>=', $startDate)
            ->select(
                'products.id',
                'products.name',
                'products.main_image',
                DB::raw('SUM(order_products.quantity) as total_quantity'),
                DB::raw('SUM(order_products.total) as total_value')
            )
            ->groupBy('products.id', 'products.name', 'products.main_image')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Average order value
        $avgOrderValue = (clone $orders)
            ->where('payment_status', 'paid')
            ->avg('order_total');

        // Order frequency
        $totalOrders = (clone $orders)->count();
        $orderFrequency = $period > 0 ? round($totalOrders / ($period / 30), 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $period,
                'daily_orders' => $dailyOrders,
                'top_products' => $topProducts,
                'average_order_value' => round($avgOrderValue ?? 0, 2),
                'orders_per_month' => $orderFrequency,
                'total_orders_in_period' => $totalOrders,
            ],
        ]);
    }

    /**
     * Get spending analytics.
     *
     * Provides detailed spending breakdown and trends.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function spendingAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();
        $year = $request->input('year', Carbon::now()->year);

        // Monthly spending for the year
        // Use strftime for SQLite compatibility, MONTH for MySQL
        $driver = DB::getDriverName();
        $monthExpression = $driver === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $monthlySpending = Order::where('customer_id', $user->id)
            ->whereYear('created_at', $year)
            ->where('payment_status', 'paid')
            ->select(
                DB::raw("$monthExpression as month"),
                DB::raw('SUM(order_total) as total'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Fill in missing months
        $fullYearSpending = collect(range(1, 12))->map(function ($month) use ($monthlySpending) {
            $data = $monthlySpending->get($month);
            return [
                'month' => $month,
                'month_name' => Carbon::create()->month($month)->format('M'),
                'total' => $data ? round($data->total, 2) : 0,
                'orders' => $data ? $data->orders : 0,
            ];
        });

        // Category-wise spending
        $categorySpending = DB::table('order_products')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.customer_id', $user->id)
            ->whereYear('orders.created_at', $year)
            ->where('orders.payment_status', 'paid')
            ->select(
                'categories.name as category',
                DB::raw('SUM(order_products.total) as total')
            )
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();

        // Year comparison
        $thisYearTotal = Order::where('customer_id', $user->id)
            ->whereYear('created_at', $year)
            ->where('payment_status', 'paid')
            ->sum('order_total');

        $lastYearTotal = Order::where('customer_id', $user->id)
            ->whereYear('created_at', $year - 1)
            ->where('payment_status', 'paid')
            ->sum('order_total');

        $yearOverYearGrowth = $lastYearTotal > 0
            ? round((($thisYearTotal - $lastYearTotal) / $lastYearTotal) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'monthly_spending' => $fullYearSpending,
                'category_spending' => $categorySpending,
                'year_total' => round($thisYearTotal, 2),
                'last_year_total' => round($lastYearTotal, 2),
                'year_over_year_growth' => $yearOverYearGrowth,
            ],
        ]);
    }

    /**
     * Get frequently ordered products.
     *
     * Returns products that the partner orders regularly.
     *
     * @return JsonResponse
     */
    public function frequentProducts(): JsonResponse
    {
        $user = Auth::user();

        $frequentProducts = DB::table('order_products')
            ->join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->where('orders.customer_id', $user->id)
            ->select(
                'products.id',
                'products.name',
                'products.slug',
                'products.main_image',
                'products.base_price',
                'products.available_stock',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_products.quantity) as total_quantity'),
                DB::raw('MAX(orders.created_at) as last_ordered')
            )
            ->groupBy(
                'products.id',
                'products.name',
                'products.slug',
                'products.main_image',
                'products.base_price',
                'products.available_stock'
            )
            ->orderByDesc('order_count')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $frequentProducts,
        ]);
    }

    /**
     * Get monthly spending trend for the last 6 months.
     *
     * @param int $userId
     * @return array
     */
    private function getMonthlySpendingTrend(int $userId): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $total = Order::where('customer_id', $userId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->where('payment_status', 'paid')
                ->sum('order_total');

            $trend[] = [
                'month' => $date->format('M Y'),
                'total' => round($total, 2),
            ];
        }
        return $trend;
    }
}
