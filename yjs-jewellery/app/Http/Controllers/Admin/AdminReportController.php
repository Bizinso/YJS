<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Models\ReportExport;
use App\Models\DailySnapshot;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Report Controller
 *
 * Comprehensive reporting and analytics for admin panel.
 */
class AdminReportController extends Controller
{
    // ============ REPORT DASHBOARD ============

    /**
     * Get reports overview.
     */
    public function dashboard(): JsonResponse
    {
        $data = [
            'saved_reports' => SavedReport::forUser(auth()->id())->count(),
            'scheduled_reports' => ScheduledReport::where('created_by', auth()->id())->count(),
            'recent_exports' => ReportExport::forUser(auth()->id())->latest()->limit(5)->get(),
            'available_report_types' => [
                ['type' => 'sales', 'label' => 'Sales Report'],
                ['type' => 'orders', 'label' => 'Orders Report'],
                ['type' => 'inventory', 'label' => 'Inventory Report'],
                ['type' => 'customers', 'label' => 'Customers Report'],
                ['type' => 'products', 'label' => 'Products Performance'],
                ['type' => 'finance', 'label' => 'Financial Report'],
                ['type' => 'returns', 'label' => 'Returns Report'],
                ['type' => 'partners', 'label' => 'Partners Report'],
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ============ SALES REPORTS ============

    /**
     * Get sales report.
     */
    public function salesReport(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->subDays(30)->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        // Summary
        $summary = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('order_status', '!=', 'cancelled')
            ->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(order_total), 0) as total_revenue,
                COALESCE(AVG(order_total), 0) as avg_order_value,
                COALESCE(SUM(CASE WHEN payment_status = "paid" THEN order_total ELSE 0 END), 0) as paid_revenue
            ')
            ->first();

        // Daily breakdown
        $dailySales = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('order_status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Sales by payment method
        $byPaymentMethod = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('order_status', '!=', 'cancelled')
            ->selectRaw('payment_method, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
            ->groupBy('payment_method')
            ->get();

        // Sales by status
        $byStatus = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->selectRaw('order_status, COUNT(*) as count')
            ->groupBy('order_status')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'daily' => $dailySales,
                'by_payment_method' => $byPaymentMethod,
                'by_status' => $byStatus,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    /**
     * Get revenue trends.
     */
    public function revenueTrends(Request $request): JsonResponse
    {
        $period = $request->period ?? 'daily'; // daily, weekly, monthly
        $days = $request->days ?? 30;

        $query = DB::table('orders')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('order_status', '!=', 'cancelled');

        $data = match ($period) {
            'weekly' => $query->selectRaw('YEARWEEK(created_at) as period, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
            'monthly' => $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
            default => $query->selectRaw('DATE(created_at) as period, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
                ->groupBy('period')
                ->orderBy('period')
                ->get(),
        };

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ============ PRODUCT REPORTS ============

    /**
     * Get products performance report.
     */
    public function productsReport(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->subDays(30)->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        // Top selling products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('orders.order_status', '!=', 'cancelled')
            ->select(
                'products.id',
                'products.product_title',
                'products.sku',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.product_title', 'products.sku')
            ->orderByDesc('revenue')
            ->limit($request->limit ?? 20)
            ->get();

        // Sales by category
        $byCategory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('orders.created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('orders.order_status', '!=', 'cancelled')
            ->select(
                'categories.id',
                'categories.name',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as revenue')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->get();

        // Low performers
        $lowPerformers = DB::table('products')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) use ($fromDate, $toDate) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereBetween('orders.created_at', [$fromDate, $toDate . ' 23:59:59'])
                    ->where('orders.order_status', '!=', 'cancelled');
            })
            ->where('products.status', 'A')
            ->select(
                'products.id',
                'products.product_title',
                'products.sku',
                DB::raw('COALESCE(SUM(order_items.quantity), 0) as qty_sold'),
                DB::raw('COALESCE(SUM(order_items.total), 0) as revenue')
            )
            ->groupBy('products.id', 'products.product_title', 'products.sku')
            ->orderBy('qty_sold')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_products' => $topProducts,
                'by_category' => $byCategory,
                'low_performers' => $lowPerformers,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
        ]);
    }

    // ============ CUSTOMER REPORTS ============

    /**
     * Get customers report.
     */
    public function customersReport(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->subDays(30)->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        // New customers
        $newCustomers = User::where('user_type', 'customer')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->count();

        // Top customers by spending
        $topCustomers = DB::table('orders')
            ->join('users', 'orders.customer_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('orders.order_status', '!=', 'cancelled')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.email',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(orders.order_total), 0) as total_spent'),
                DB::raw('COALESCE(AVG(orders.order_total), 0) as avg_order')
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->orderByDesc('total_spent')
            ->limit($request->limit ?? 20)
            ->get();

        // Customer acquisition trend
        $acquisitionTrend = User::where('user_type', 'customer')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Repeat vs new customers
        $repeatCustomers = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('order_status', '!=', 'cancelled')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        $totalOrderingCustomers = DB::table('orders')
            ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
            ->where('order_status', '!=', 'cancelled')
            ->distinct('customer_id')
            ->count('customer_id');

        return response()->json([
            'success' => true,
            'data' => [
                'new_customers' => $newCustomers,
                'top_customers' => $topCustomers,
                'acquisition_trend' => $acquisitionTrend,
                'repeat_customers' => $repeatCustomers,
                'total_ordering' => $totalOrderingCustomers,
                'repeat_rate' => $totalOrderingCustomers > 0
                    ? round(($repeatCustomers / $totalOrderingCustomers) * 100, 1)
                    : 0,
            ],
        ]);
    }

    // ============ INVENTORY REPORTS ============

    /**
     * Get inventory report.
     */
    public function inventoryReport(Request $request): JsonResponse
    {
        // Stock summary
        $stockSummary = DB::table('products')
            ->where('status', 'A')
            ->selectRaw('
                COUNT(*) as total_products,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= low_stock_threshold THEN 1 ELSE 0 END) as low_stock,
                SUM(CASE WHEN stock_quantity > low_stock_threshold THEN 1 ELSE 0 END) as in_stock
            ')
            ->first();

        // Low stock items
        $lowStockItems = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'A')
            ->whereRaw('products.stock_quantity <= products.low_stock_threshold')
            ->where('products.stock_quantity', '>', 0)
            ->select(
                'products.id',
                'products.product_title',
                'products.sku',
                'products.stock_quantity',
                'products.low_stock_threshold',
                'categories.name as category'
            )
            ->orderBy('products.stock_quantity')
            ->limit(50)
            ->get();

        // Out of stock items
        $outOfStock = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'A')
            ->where('products.stock_quantity', 0)
            ->select(
                'products.id',
                'products.product_title',
                'products.sku',
                'categories.name as category',
                'products.updated_at'
            )
            ->orderBy('products.updated_at', 'desc')
            ->limit(50)
            ->get();

        // Stock value by category
        $stockValue = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('products.status', 'A')
            ->where('products.stock_quantity', '>', 0)
            ->select(
                'categories.name',
                DB::raw('SUM(products.stock_quantity) as total_units'),
                DB::raw('COALESCE(SUM(products.stock_quantity * products.base_price), 0) as stock_value')
            )
            ->groupBy('categories.name')
            ->orderByDesc('stock_value')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $stockSummary,
                'low_stock' => $lowStockItems,
                'out_of_stock' => $outOfStock,
                'stock_value_by_category' => $stockValue,
            ],
        ]);
    }

    // ============ RETURNS REPORT ============

    /**
     * Get returns report.
     */
    public function returnsReport(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->subDays(30)->format('Y-m-d');
        $toDate = $request->to_date ?? now()->format('Y-m-d');

        $data = ['from_date' => $fromDate, 'to_date' => $toDate];

        if (DB::getSchemaBuilder()->hasTable('return_requests')) {
            $summary = DB::table('return_requests')
                ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status NOT IN ("completed", "rejected", "cancelled") THEN 1 ELSE 0 END) as pending
                ')
                ->first();

            $byReason = DB::table('return_requests')
                ->whereBetween('created_at', [$fromDate, $toDate . ' 23:59:59'])
                ->selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->orderByDesc('count')
                ->get();

            $data['summary'] = $summary;
            $data['by_reason'] = $byReason;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ============ SAVED REPORTS ============

    /**
     * List saved reports.
     */
    public function savedReports(): JsonResponse
    {
        $reports = SavedReport::forUser(auth()->id())
            ->with('createdByUser:id,first_name,last_name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Save a report.
     */
    public function saveReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'format' => 'nullable|in:table,chart,pivot',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $report = SavedReport::create([
            'name' => $request->name,
            'type' => $request->type,
            'filters' => $request->filters,
            'columns' => $request->columns,
            'format' => $request->format ?? 'table',
            'is_public' => $request->is_public ?? false,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report saved',
            'data' => $report,
        ], 201);
    }

    /**
     * Delete saved report.
     */
    public function deleteSavedReport(int $id): JsonResponse
    {
        $report = SavedReport::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted',
        ]);
    }

    // ============ SCHEDULED REPORTS ============

    /**
     * List scheduled reports.
     */
    public function scheduledReports(): JsonResponse
    {
        $reports = ScheduledReport::where('created_by', auth()->id())
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    /**
     * Create scheduled report.
     */
    public function createScheduledReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'frequency' => 'required|in:daily,weekly,monthly',
            'day_of_week' => 'required_if:frequency,weekly|nullable|string',
            'day_of_month' => 'required_if:frequency,monthly|nullable|integer|min:1|max:28',
            'time_of_day' => 'nullable|date_format:H:i',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'export_format' => 'nullable|in:xlsx,csv,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $report = ScheduledReport::create([
            'name' => $request->name,
            'type' => $request->type,
            'filters' => $request->filters,
            'columns' => $request->columns,
            'frequency' => $request->frequency,
            'day_of_week' => $request->day_of_week,
            'day_of_month' => $request->day_of_month,
            'time_of_day' => $request->time_of_day ?? '08:00',
            'recipients' => $request->recipients,
            'export_format' => $request->export_format ?? 'xlsx',
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        $report->calculateNextRun();

        return response()->json([
            'success' => true,
            'message' => 'Scheduled report created',
            'data' => $report,
        ], 201);
    }

    /**
     * Update scheduled report.
     */
    public function updateScheduledReport(Request $request, int $id): JsonResponse
    {
        $report = ScheduledReport::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'frequency' => 'nullable|in:daily,weekly,monthly',
            'day_of_week' => 'nullable|string',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'time_of_day' => 'nullable|date_format:H:i',
            'recipients' => 'nullable|array|min:1',
            'recipients.*' => 'email',
            'export_format' => 'nullable|in:xlsx,csv,pdf',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $report->update($request->only([
            'name', 'filters', 'columns', 'frequency', 'day_of_week',
            'day_of_month', 'time_of_day', 'recipients', 'export_format', 'is_active',
        ]));

        if ($request->has('frequency')) {
            $report->calculateNextRun();
        }

        return response()->json([
            'success' => true,
            'message' => 'Report updated',
            'data' => $report->fresh(),
        ]);
    }

    /**
     * Delete scheduled report.
     */
    public function deleteScheduledReport(int $id): JsonResponse
    {
        $report = ScheduledReport::where('id', $id)
            ->where('created_by', auth()->id())
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted',
        ]);
    }

    // ============ EXPORT HISTORY ============

    /**
     * Get export history.
     */
    public function exportHistory(Request $request): JsonResponse
    {
        $exports = ReportExport::forUser(auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    // ============ DAILY SNAPSHOTS ============

    /**
     * Get daily snapshots.
     */
    public function snapshots(Request $request): JsonResponse
    {
        $days = $request->days ?? 30;
        $snapshots = DailySnapshot::recent($days)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $snapshots,
        ]);
    }

    /**
     * Generate snapshot for date.
     */
    public function generateSnapshot(Request $request): JsonResponse
    {
        $date = $request->date ?? now()->subDay()->format('Y-m-d');

        $snapshot = DailySnapshot::generateForDate($date);

        return response()->json([
            'success' => true,
            'message' => 'Snapshot generated',
            'data' => $snapshot,
        ]);
    }

    // ============ AUDIT LOGS ============

    /**
     * Get audit logs.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,first_name,last_name,email');

        if ($request->event) {
            $query->forEvent($request->event);
        }
        if ($request->user_id) {
            $query->forUser($request->user_id);
        }
        if ($request->model_type) {
            $query->forModel($request->model_type, $request->model_id);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->ip_address) {
            $query->fromIp($request->ip_address);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get audit log detail.
     */
    public function auditLogDetail(int $id): JsonResponse
    {
        $log = AuditLog::with('user:id,first_name,last_name,email')
            ->find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get audit summary.
     */
    public function auditSummary(Request $request): JsonResponse
    {
        $days = $request->days ?? 7;

        $summary = [
            'total_events' => AuditLog::recent($days)->count(),
            'by_event' => AuditLog::recent($days)
                ->select('event', DB::raw('count(*) as count'))
                ->groupBy('event')
                ->pluck('count', 'event'),
            'by_user' => AuditLog::recent($days)
                ->join('users', 'audit_logs.user_id', '=', 'users.id')
                ->select(
                    'users.id',
                    DB::raw('CONCAT(users.first_name, " ", users.last_name) as name'),
                    DB::raw('count(*) as count')
                )
                ->groupBy('users.id', 'name')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'recent' => AuditLog::with('user:id,first_name,last_name')
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}
