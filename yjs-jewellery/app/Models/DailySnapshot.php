<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

/**
 * Daily Snapshot Model
 *
 * Stores daily metrics for trend analysis.
 */
class DailySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_orders',
        'total_revenue',
        'new_customers',
        'new_partners',
        'products_sold',
        'average_order_value',
        'returns_initiated',
        'cancellations',
        'refunds_processed',
        'support_tickets',
        'top_products',
        'sales_by_category',
        'additional_metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'refunds_processed' => 'decimal:2',
        'top_products' => 'array',
        'sales_by_category' => 'array',
        'additional_metrics' => 'array',
    ];

    /**
     * Generate snapshot for a date.
     */
    public static function generateForDate(string $date): self
    {
        $snapshot = self::firstOrNew(['date' => $date]);

        // Orders
        $orders = DB::table('orders')
            ->whereDate('created_at', $date)
            ->where('order_status', '!=', 'cancelled')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(order_total), 0) as revenue')
            ->first();

        $snapshot->total_orders = $orders->count ?? 0;
        $snapshot->total_revenue = $orders->revenue ?? 0;
        $snapshot->average_order_value = $snapshot->total_orders > 0
            ? $snapshot->total_revenue / $snapshot->total_orders
            : 0;

        // New users
        $snapshot->new_customers = DB::table('users')
            ->whereDate('created_at', $date)
            ->where('user_type', 'customer')
            ->count();

        $snapshot->new_partners = DB::table('users')
            ->whereDate('created_at', $date)
            ->where('user_type', 'partner')
            ->count();

        // Products sold
        $snapshot->products_sold = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereDate('orders.created_at', $date)
            ->where('orders.order_status', '!=', 'cancelled')
            ->sum('order_items.quantity');

        // Returns
        if (DB::getSchemaBuilder()->hasTable('return_requests')) {
            $snapshot->returns_initiated = DB::table('return_requests')
                ->whereDate('created_at', $date)
                ->count();
        }

        // Cancellations
        if (DB::getSchemaBuilder()->hasTable('cancellation_requests')) {
            $snapshot->cancellations = DB::table('cancellation_requests')
                ->whereDate('created_at', $date)
                ->count();
        }

        // Refunds
        if (DB::getSchemaBuilder()->hasTable('refund_requests')) {
            $snapshot->refunds_processed = DB::table('refund_requests')
                ->whereDate('completed_at', $date)
                ->where('status', 'completed')
                ->sum('refund_amount');
        }

        // Support tickets
        if (DB::getSchemaBuilder()->hasTable('support_tickets')) {
            $snapshot->support_tickets = DB::table('support_tickets')
                ->whereDate('created_at', $date)
                ->count();
        }

        // Top products
        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereDate('orders.created_at', $date)
            ->where('orders.order_status', '!=', 'cancelled')
            ->select('products.id', 'products.product_title', DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy('products.id', 'products.product_title')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $snapshot->top_products = $topProducts->toArray();

        // Sales by category
        $salesByCategory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereDate('orders.created_at', $date)
            ->where('orders.order_status', '!=', 'cancelled')
            ->select('categories.name', DB::raw('COALESCE(SUM(order_items.total), 0) as total'))
            ->groupBy('categories.name')
            ->get();

        $snapshot->sales_by_category = $salesByCategory->pluck('total', 'name')->toArray();

        $snapshot->save();

        return $snapshot;
    }

    /**
     * Get snapshots for date range.
     */
    public static function getRange(string $from, string $to): \Illuminate\Database\Eloquent\Collection
    {
        return self::whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }

    /**
     * Scopes
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }
}
