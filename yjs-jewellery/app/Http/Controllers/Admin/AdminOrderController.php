<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderRefund;
use App\Services\Order\OrderService;
use App\Services\Payment\RazorpayService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin Order Controller
 *
 * Handles administrative order management including
 * order listing, status updates, refunds, and shipping.
 *
 * @package App\Http\Controllers\Admin
 */
class AdminOrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private RazorpayService $razorpayService,
        private ShiprocketService $shiprocketService
    ) {}

    /**
     * Get paginated order list with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with([
            'customer:id,name,email,phone',
            'billingAddress:id,full_name,city,state',
            'shippingAddress:id,full_name,city,state',
            'orderProducts:id,order_id,product_id,quantity,total',
            'orderProducts.product:id,name,sku,main_image',
        ])->orderBy('created_at', 'desc');

        // Filter by order status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('order_status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Search by order code or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('custom_order_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 20);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get single order details.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'orderProducts.product',
            'payments',
            'shipmentTracking',
            'orderOffer',
            'cancellation',
            'refunds',
        ])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Update order status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $oldStatus = $order->order_status;
        $newStatus = $request->status;

        // Validate status transition
        $validTransitions = $this->getValidStatusTransitions($oldStatus);
        if (!in_array($newStatus, $validTransitions)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot change status from {$oldStatus} to {$newStatus}.",
                'valid_transitions' => $validTransitions,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $order->order_status = $newStatus;

            // Handle cancellation
            if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->handleOrderCancellation($order, $request->notes ?? 'Admin cancelled');
            }

            // Handle delivery confirmation
            if ($newStatus === 'delivered') {
                $order->delivery_date = now();
            }

            if ($request->notes) {
                $order->notes = ($order->notes ? $order->notes . "\n" : '') .
                    "[" . now()->format('Y-m-d H:i') . "] Status changed to {$newStatus}: {$request->notes}";
            }

            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$newStatus}.",
                'data' => [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process full refund for an order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function processRefund(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::with('payments')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Order is not paid. Cannot process refund.',
            ], 400);
        }

        $refundAmount = $request->amount ?? $order->order_total;

        if ($refundAmount > $order->order_total) {
            return response()->json([
                'success' => false,
                'message' => 'Refund amount cannot exceed order total.',
            ], 400);
        }

        try {
            // Get the payment record to verify it exists
            $payment = $order->payments()->where('status', 'success')->latest()->first();

            if (!$payment || !$payment->razorpay_payment_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No successful payment found for refund.',
                ], 400);
            }

            // Process refund through Razorpay service
            // The service methods take Order object and return OrderRefund or null
            $refund = $refundAmount < $order->order_total
                ? $this->razorpayService->refundPartial($order, $refundAmount, $request->reason ?? 'admin_refund')
                : $this->razorpayService->refundFull($order, $request->reason ?? 'admin_refund');

            if (!$refund) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refund failed. No valid payment found or Razorpay error.',
                ], 500);
            }

            // Note: Refunds are tracked in order_refunds table
            // payment_status stays as 'paid' since payment was received
            // Refund info is available through the refunds relationship

            return response()->json([
                'success' => true,
                'message' => 'Refund processed successfully.',
                'data' => [
                    'refund_id' => $refund->razorpay_refund_id,
                    'amount' => $refund->amount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process refund.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add note to order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addNote(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $timestamp = now()->format('Y-m-d H:i');
        $userName = auth()->user()->name ?? 'Admin';
        $newNote = "[{$timestamp}] {$userName}: {$request->note}";

        $order->notes = $order->notes
            ? $order->notes . "\n" . $newNote
            : $newNote;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Note added successfully.',
            'data' => [
                'notes' => $order->notes,
            ],
        ]);
    }

    /**
     * Get order statistics dashboard.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->input('period', 30); // days
        $startDate = now()->subDays($period);

        // Order counts by status
        $statusCounts = Order::select('order_status', DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();

        // Revenue statistics
        $revenue = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->selectRaw('
                SUM(order_total) as total_revenue,
                COUNT(*) as paid_orders,
                AVG(order_total) as avg_order_value
            ')
            ->first();

        // Daily revenue trend
        $driver = DB::getDriverName();
        $dateFormat = $driver === 'sqlite'
            ? "DATE(created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m-%d')";

        $dailyRevenue = Order::where('created_at', '>=', $startDate)
            ->where('payment_status', 'paid')
            ->select(
                DB::raw("{$dateFormat} as date"),
                DB::raw('SUM(order_total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Pending actions
        $pendingActions = [
            'pending_confirmation' => Order::where('order_status', 'pending')->count(),
            'ready_to_ship' => Order::where('order_status', 'processing')
                ->where('payment_status', 'paid')
                ->whereNull('awb_number')
                ->count(),
            'pending_refunds' => OrderCancellation::where('refund_status', 'initiated')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $period,
                'status_counts' => $statusCounts,
                'revenue' => [
                    'total' => round($revenue->total_revenue ?? 0, 2),
                    'orders' => $revenue->paid_orders ?? 0,
                    'average' => round($revenue->avg_order_value ?? 0, 2),
                ],
                'daily_trend' => $dailyRevenue,
                'pending_actions' => $pendingActions,
            ],
        ]);
    }

    /**
     * Get valid status transitions for an order status.
     *
     * @param string $currentStatus
     * @return array
     */
    private function getValidStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [], // No transitions from delivered
            'cancelled' => [], // No transitions from cancelled
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Handle order cancellation.
     *
     * @param Order $order
     * @param string $reason
     * @return void
     */
    private function handleOrderCancellation(Order $order, string $reason): void
    {
        // Create cancellation record
        OrderCancellation::create([
            'order_id' => $order->id,
            'cancelled_by' => 'admin',
            'cancelled_by_user_id' => auth()->id(),
            'reason_code' => 'admin_cancelled',
            'reason_text' => $reason,
            'order_status_at_cancel' => $order->order_status,
            'cancelled_at' => now(),
            'refund_amount' => $order->payment_status === 'paid' ? $order->order_total : 0,
            'refund_status' => $order->payment_status === 'paid' ? 'initiated' : null,
        ]);

        // Restore product stock
        foreach ($order->orderProducts as $item) {
            if ($item->product) {
                $item->product->increment('available_stock', $item->quantity);
            }
        }
    }
}
