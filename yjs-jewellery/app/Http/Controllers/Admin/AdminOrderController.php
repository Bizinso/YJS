<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderRefund;
use App\Models\OrderTimeline;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderFulfillment;
use App\Models\OrderFulfillmentItem;
use App\Models\OrderHold;
use App\Models\OrderOverride;
use App\Models\OrderSlaConfig;
use App\Services\Order\OrderService;
use App\Services\Payment\RazorpayService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            'customer:id,first_name,last_name,email,phone',
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
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
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

    /**
     * Get order timeline/activity history.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function timeline(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $timeline = OrderTimeline::forOrder($id)
            ->with('performedByUser:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Create partial fulfillment for an order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function fulfillPartial(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'create_shipment' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::with('orderProducts', 'fulfillments.items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->is_on_hold) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot fulfill order while on hold.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate already fulfilled quantities
            $fulfilledQuantities = [];
            foreach ($order->fulfillments->where('status', '!=', OrderFulfillment::STATUS_CANCELLED) as $fulfillment) {
                foreach ($fulfillment->items as $item) {
                    $fulfilledQuantities[$item->order_item_id] = ($fulfilledQuantities[$item->order_item_id] ?? 0) + $item->quantity;
                }
            }

            // Validate requested quantities
            foreach ($request->items as $item) {
                $orderItem = $order->orderProducts->find($item['order_item_id']);
                if (!$orderItem) {
                    throw new \Exception("Invalid order item: {$item['order_item_id']}");
                }

                $alreadyFulfilled = $fulfilledQuantities[$item['order_item_id']] ?? 0;
                $available = $orderItem->quantity - $alreadyFulfilled;

                if ($item['quantity'] > $available) {
                    $productName = $orderItem->product->name ?? 'Unknown';
                    throw new \Exception("Cannot fulfill {$item['quantity']} units of {$productName}. Only {$available} available.");
                }
            }

            // Create fulfillment
            $fulfillment = OrderFulfillment::create([
                'order_id' => $id,
                'status' => OrderFulfillment::STATUS_PENDING,
                'notes' => $request->notes,
                'fulfilled_by' => auth()->id(),
            ]);

            // Create fulfillment items
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $orderItem = $order->orderProducts->find($item['order_item_id']);
                $itemTotal = ($orderItem->price ?? 0) * $item['quantity'];
                $totalAmount += $itemTotal;

                OrderFulfillmentItem::create([
                    'fulfillment_id' => $fulfillment->id,
                    'order_item_id' => $item['order_item_id'],
                    'product_id' => $orderItem->product_id,
                    'quantity' => $item['quantity'],
                    'item_total' => $itemTotal,
                ]);
            }

            $fulfillment->update(['total_amount' => $totalAmount]);

            // Create shipment if requested
            if ($request->create_shipment) {
                $shipment = OrderShipment::create([
                    'order_id' => $id,
                    'shipping_address_id' => $order->shipping_address_id,
                    'created_by' => auth()->id(),
                ]);

                foreach ($fulfillment->items as $item) {
                    OrderShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'order_item_id' => $item->order_item_id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ]);
                }

                $fulfillment->update(['shipment_id' => $shipment->id]);
                $order->increment('total_shipments');
                $order->update(['is_split_shipment' => $order->total_shipments > 1]);
            }

            // Log timeline
            OrderTimeline::logEvent(
                $id,
                OrderTimeline::TYPE_FULFILLMENT_CREATED,
                "Partial fulfillment created: {$fulfillment->fulfillment_code}",
                "Fulfilled " . $fulfillment->items->sum('quantity') . " items",
                ['fulfillment_id' => $fulfillment->id, 'items_count' => $fulfillment->items->count()]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial fulfillment created successfully.',
                'data' => $fulfillment->load('items.product:id,name,sku'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Split order into multiple shipments.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function splitShipment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shipments' => 'required|array|min:2',
            'shipments.*.items' => 'required|array|min:1',
            'shipments.*.items.*.order_item_id' => 'required|exists:order_products,id',
            'shipments.*.items.*.quantity' => 'required|integer|min:1',
            'shipments.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::with('orderProducts')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if ($order->is_on_hold) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot split shipment while order is on hold.',
            ], 422);
        }

        // Validate total quantities match order
        $requestedQuantities = [];
        foreach ($request->shipments as $shipmentData) {
            foreach ($shipmentData['items'] as $item) {
                $requestedQuantities[$item['order_item_id']] = ($requestedQuantities[$item['order_item_id']] ?? 0) + $item['quantity'];
            }
        }

        foreach ($order->orderProducts as $orderItem) {
            $requested = $requestedQuantities[$orderItem->id] ?? 0;
            if ($requested !== $orderItem->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Quantity mismatch for item {$orderItem->id}. Expected {$orderItem->quantity}, got {$requested}.",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $createdShipments = [];

            foreach ($request->shipments as $shipmentData) {
                $shipment = OrderShipment::create([
                    'order_id' => $id,
                    'shipping_address_id' => $order->shipping_address_id,
                    'notes' => $shipmentData['notes'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                foreach ($shipmentData['items'] as $item) {
                    $orderItem = $order->orderProducts->find($item['order_item_id']);
                    OrderShipmentItem::create([
                        'shipment_id' => $shipment->id,
                        'order_item_id' => $item['order_item_id'],
                        'product_id' => $orderItem->product_id,
                        'quantity' => $item['quantity'],
                    ]);
                }

                $createdShipments[] = $shipment->load('items');
            }

            $order->update([
                'is_split_shipment' => true,
                'total_shipments' => count($createdShipments),
            ]);

            // Log timeline
            OrderTimeline::logEvent(
                $id,
                OrderTimeline::TYPE_SHIPMENT_CREATED,
                "Order split into " . count($createdShipments) . " shipments",
                null,
                ['shipment_count' => count($createdShipments)]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order split into multiple shipments successfully.',
                'data' => [
                    'shipments' => $createdShipments,
                    'total_shipments' => count($createdShipments),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to split shipment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Place order on hold.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function hold(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason_code' => 'required|string|max:50',
            'reason' => 'nullable|string|max:1000',
            'hold_until' => 'nullable|date|after:now',
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

        if ($order->is_on_hold) {
            return response()->json([
                'success' => false,
                'message' => 'Order is already on hold.',
            ], 422);
        }

        if (in_array($order->order_status, ['delivered', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot hold delivered or cancelled orders.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Create hold record
            $hold = OrderHold::create([
                'order_id' => $id,
                'hold_reason_code' => $request->reason_code,
                'hold_reason' => $request->reason,
                'hold_until' => $request->hold_until,
                'held_by' => auth()->id(),
            ]);

            // Update order
            $order->update([
                'is_on_hold' => true,
                'hold_reason_code' => $request->reason_code,
                'hold_since' => now(),
            ]);

            // Log timeline
            OrderTimeline::logEvent(
                $id,
                OrderTimeline::TYPE_HOLD_PLACED,
                'Order placed on hold',
                $request->reason,
                ['hold_id' => $hold->id, 'reason_code' => $request->reason_code],
                null,
                'on_hold'
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed on hold successfully.',
                'data' => $hold,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to hold order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Release order from hold.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function release(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::with('activeHold')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (!$order->is_on_hold) {
            return response()->json([
                'success' => false,
                'message' => 'Order is not on hold.',
            ], 422);
        }

        try {
            if ($order->activeHold) {
                $order->activeHold->release($request->notes, auth()->id());
            } else {
                $order->update([
                    'is_on_hold' => false,
                    'hold_reason_code' => null,
                    'hold_since' => null,
                ]);

                OrderTimeline::logEvent(
                    $id,
                    OrderTimeline::TYPE_HOLD_RELEASED,
                    'Order released from hold',
                    $request->notes
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Order released from hold successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to release order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply manual override to order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function override(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'override_type' => 'required|in:status_override,price_override,shipping_override,discount_override,tax_override,address_override,payment_override,other',
            'field_name' => 'nullable|string|max:100',
            'new_value' => 'required|string|max:1000',
            'reason' => 'required|string|max:1000',
            'requires_approval' => 'nullable|boolean',
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

        $oldValue = $request->field_name ? ($order->{$request->field_name} ?? null) : null;

        try {
            $override = OrderOverride::create([
                'order_id' => $id,
                'override_type' => $request->override_type,
                'field_name' => $request->field_name,
                'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                'new_value' => $request->new_value,
                'reason' => $request->reason,
                'requires_approval' => $request->requires_approval ?? false,
                'approval_status' => $request->requires_approval ? OrderOverride::APPROVAL_PENDING : null,
                'overridden_by' => auth()->id(),
            ]);

            // If no approval required, apply immediately
            if (!$request->requires_approval && $request->field_name) {
                $order->{$request->field_name} = $request->new_value;
                $order->save();

                OrderTimeline::logEvent(
                    $id,
                    OrderTimeline::TYPE_OVERRIDE_APPLIED,
                    "Manual override applied: {$request->override_type}",
                    $request->reason,
                    ['override_id' => $override->id],
                    $oldValue,
                    $request->new_value
                );
            }

            return response()->json([
                'success' => true,
                'message' => $request->requires_approval
                    ? 'Override submitted for approval.'
                    : 'Override applied successfully.',
                'data' => $override,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply override: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update order statuses.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array|min:1|max:100',
            'order_ids.*' => 'exists:orders,id',
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

        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($request->order_ids as $orderId) {
            $order = Order::find($orderId);

            if (!$order) {
                $results['failed'][] = [
                    'order_id' => $orderId,
                    'reason' => 'Order not found',
                ];
                continue;
            }

            $validTransitions = $this->getValidStatusTransitions($order->order_status);
            if (!in_array($request->status, $validTransitions)) {
                $results['failed'][] = [
                    'order_id' => $orderId,
                    'reason' => "Cannot change from {$order->order_status} to {$request->status}",
                ];
                continue;
            }

            $oldStatus = $order->order_status;
            $order->order_status = $request->status;

            // Update timestamps based on status
            switch ($request->status) {
                case 'confirmed':
                    $order->confirmed_at = now();
                    break;
                case 'processing':
                    $order->processing_started_at = now();
                    break;
                case 'shipped':
                    $order->shipped_at = now();
                    break;
                case 'delivered':
                    $order->delivered_at = now();
                    $order->delivery_date = now();
                    break;
            }

            $order->save();

            OrderTimeline::logEvent(
                $orderId,
                OrderTimeline::TYPE_STATUS_CHANGE,
                "Status changed to {$request->status}",
                $request->notes,
                null,
                $oldStatus,
                $request->status
            );

            $results['success'][] = $orderId;
        }

        return response()->json([
            'success' => true,
            'message' => count($results['success']) . ' orders updated successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Export orders to CSV/Excel.
     *
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'nullable|in:csv,json',
            'status' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'payment_status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Order::with([
            'customer:id,first_name,last_name,email,phone',
            'orderProducts:id,order_id,product_id,quantity,total',
            'orderProducts.product:id,name,sku',
            'shippingAddress:id,full_name,city,state,pincode',
        ]);

        if ($request->status && $request->status !== 'all') {
            $query->where('order_status', $request->status);
        }
        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $orders = $query->orderBy('created_at', 'desc')->limit(5000)->get();

        $format = $request->format ?? 'csv';

        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'data' => $orders,
                'count' => $orders->count(),
            ]);
        }

        // CSV export
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Order ID', 'Order Code', 'Date', 'Customer', 'Email', 'Phone',
                'Status', 'Payment Status', 'Subtotal', 'Shipping', 'Tax', 'Total',
                'Items Count', 'Shipping City', 'Shipping State', 'Priority', 'On Hold'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->custom_order_code,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->customer->name ?? '',
                    $order->customer->email ?? '',
                    $order->customer->phone ?? '',
                    $order->order_status,
                    $order->payment_status,
                    $order->order_subtotal,
                    $order->shipping_charges,
                    $order->total_taxes,
                    $order->order_total,
                    $order->orderProducts->sum('quantity'),
                    $order->shippingAddress->city ?? '',
                    $order->shippingAddress->state ?? '',
                    $order->priority,
                    $order->is_on_hold ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get orders with SLA breaches or at risk.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function slaBreaches(Request $request): JsonResponse
    {
        try {
            $result = OrderSlaConfig::getBreachingOrders();

            // Get SLA configs for reference
            $slaConfigs = OrderSlaConfig::active()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'breached' => $result['breached'],
                    'at_risk' => $result['at_risk'],
                    'breached_count' => count($result['breached']),
                    'at_risk_count' => count($result['at_risk']),
                    'sla_configs' => $slaConfigs,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SLA breaches: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hold reason options.
     *
     * @return JsonResponse
     */
    public function holdReasons(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => OrderHold::getReasonLabels(),
        ]);
    }

    /**
     * Get order shipments.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function shipments(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $shipments = OrderShipment::forOrder($id)
            ->with(['items.product:id,name,sku,main_image', 'createdByUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $shipments,
        ]);
    }

    /**
     * Update shipment status.
     *
     * @param Request $request
     * @param int $orderId
     * @param int $shipmentId
     * @return JsonResponse
     */
    public function updateShipmentStatus(Request $request, int $orderId, int $shipmentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:created,picked_up,in_transit,out_for_delivery,delivered,returned,cancelled',
            'awb_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100',
            'tracking_url' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $shipment = OrderShipment::where('order_id', $orderId)->find($shipmentId);

        if (!$shipment) {
            return response()->json([
                'success' => false,
                'message' => 'Shipment not found.',
            ], 404);
        }

        $shipment->update([
            'status' => $request->status,
            'awb_number' => $request->awb_number ?? $shipment->awb_number,
            'courier_name' => $request->courier_name ?? $shipment->courier_name,
            'tracking_url' => $request->tracking_url ?? $shipment->tracking_url,
        ]);

        if ($request->status === OrderShipment::STATUS_DELIVERED) {
            $shipment->update(['delivered_at' => now()]);
        }

        $shipment->updateStatus($request->status, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Shipment status updated.',
            'data' => $shipment->fresh(),
        ]);
    }

    /**
     * Get order fulfillments.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function fulfillments(int $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $fulfillments = OrderFulfillment::forOrder($id)
            ->with([
                'items.product:id,name,sku,main_image',
                'shipment:id,shipment_code,status,awb_number',
                'fulfilledByUser:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $fulfillments,
        ]);
    }

    /**
     * Get SLA configurations.
     *
     * @return JsonResponse
     */
    public function slaConfig(): JsonResponse
    {
        $configs = OrderSlaConfig::all();

        if ($configs->isEmpty()) {
            // Return defaults if none configured
            return response()->json([
                'success' => true,
                'data' => OrderSlaConfig::getDefaults(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $configs,
        ]);
    }

    /**
     * Update SLA configuration.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSlaConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'configs' => 'required|array',
            'configs.*.sla_type' => 'required|string',
            'configs.*.hours_limit' => 'required|integer|min:1',
            'configs.*.is_active' => 'required|boolean',
            'configs.*.send_alerts' => 'nullable|boolean',
            'configs.*.alert_before_hours' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->configs as $config) {
            OrderSlaConfig::updateOrCreate(
                ['sla_type' => $config['sla_type']],
                [
                    'description' => $config['description'] ?? '',
                    'hours_limit' => $config['hours_limit'],
                    'is_active' => $config['is_active'],
                    'send_alerts' => $config['send_alerts'] ?? true,
                    'alert_before_hours' => $config['alert_before_hours'] ?? 2,
                    'applicable_statuses' => $config['applicable_statuses'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'SLA configuration updated.',
            'data' => OrderSlaConfig::all(),
        ]);
    }
}
