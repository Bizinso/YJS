<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Partner;
use App\Models\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Partner Order Controller
 *
 * Handles order management for B2B partners including
 * order listing, details, cancellation, and tracking.
 *
 * @package App\Http\Controllers\Partner
 */
class PartnerOrderController extends Controller
{
    /**
     * Get list of partner's orders.
     *
     * Supports filtering by status, date range, and search.
     * Includes pagination for large order histories.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner || !$partner->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Partner account not approved.',
            ], 403);
        }

        $query = Order::where('customer_id', $user->id)
            ->with(['orderProducts.product:id,name,slug,main_image'])
            ->orderBy('created_at', 'desc');

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

        // Search by order code
        if ($request->has('search')) {
            $query->where('custom_order_code', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        $formattedOrders = $orders->getCollection()->map(function ($order) {
            return [
                'id' => $order->id,
                'order_code' => $order->custom_order_code,
                'order_date' => $order->created_at->format('Y-m-d H:i:s'),
                'order_status' => $order->order_status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->payment_status_label,
                'order_total' => $order->order_total,
                'items_count' => $order->orderProducts->sum('quantity'),
                'first_product_image' => $order->orderProducts->first()?->product?->main_image,
                'can_cancel' => $order->canBeCancelled(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOrders,
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
     * Returns complete order information including products,
     * addresses, shipping, and payment details.
     *
     * @param int $id Order ID
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->with([
                'orderProducts.product:id,name,slug,main_image,sku',
                'billingAddress',
                'shippingAddress',
                'payments',
                'shipmentTracking',
                'orderOffer',
                'cancellation',
            ])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_code' => $order->custom_order_code,
                'order_date' => $order->created_at,
                'order_status' => $order->order_status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->payment_status_label,
                'payment_method' => $order->payment_method,
                'items' => $order->orderProducts->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => $item->product,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'discount' => $item->discount,
                        'tax' => $item->tax,
                        'total' => $item->total,
                    ];
                }),
                'billing_address' => $order->billingAddress,
                'shipping_address' => $order->shippingAddress,
                'shipping' => [
                    'method' => $order->shipping_method,
                    'charges' => $order->shipping_charges,
                    'courier_name' => $order->courier_name,
                    'awb_number' => $order->awb_number,
                    'delivery_date' => $order->delivery_date,
                ],
                'tracking' => $order->shipmentTracking ? [
                    'current_status' => $order->shipmentTracking->current_status,
                    'current_location' => $order->shipmentTracking->current_location,
                    'etd' => $order->shipmentTracking->etd,
                    'is_delivered' => $order->shipmentTracking->is_delivered,
                    'activities' => $order->shipmentTracking->activities,
                ] : null,
                'pricing' => [
                    'subtotal' => $order->order_subtotal,
                    'total_charges' => $order->total_charges,
                    'total_taxes' => $order->total_taxes,
                    'shipping_charges' => $order->shipping_charges,
                    'discount' => $order->orderOffer?->discount_amount ?? 0,
                    'order_total' => $order->order_total,
                ],
                'offer' => $order->orderOffer ? [
                    'title' => $order->orderOffer->offer_title,
                    'discount_amount' => $order->orderOffer->discount_amount,
                    'coupon_code' => $order->orderOffer->coupon_code,
                ] : null,
                'cancellation' => $order->cancellation ? [
                    'cancelled_at' => $order->cancellation->cancelled_at,
                    'reason' => $order->cancellation->reason_text,
                    'refund_status' => $order->cancellation->refund_status,
                    'refund_amount' => $order->cancellation->refund_amount,
                ] : null,
                'notes' => $order->notes,
                'can_cancel' => $order->canBeCancelled(),
            ],
        ]);
    }

    /**
     * Cancel an order.
     *
     * Partners can cancel orders within 24 hours if not yet shipped.
     * Initiates refund process if payment was made.
     *
     * @param Request $request
     * @param int $id Order ID
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This order cannot be cancelled. It may have been shipped or exceeded the cancellation window.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation reason is required.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create cancellation record
        // Note: Partners are business customers, so we use 'customer' as the cancelled_by value
        OrderCancellation::create([
            'order_id' => $order->id,
            'cancelled_by' => 'customer',
            'cancelled_by_user_id' => $user->id,
            'reason_code' => 'partner_requested',
            'reason_text' => $request->reason,
            'order_status_at_cancel' => $order->order_status,
            'cancelled_at' => now(),
            'refund_amount' => $order->payment_status === 'paid' ? $order->order_total : 0,
            'refund_status' => $order->payment_status === 'paid' ? 'initiated' : null,
        ]);

        // Update order status
        $order->update([
            'order_status' => 'cancelled',
        ]);

        // Restore product stock
        foreach ($order->orderProducts as $item) {
            if ($item->product) {
                $item->product->increment('available_stock', $item->quantity);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully.',
            'refund_initiated' => $order->payment_status === 'paid',
        ]);
    }

    /**
     * Get order tracking information.
     *
     * Returns detailed tracking timeline and current status.
     *
     * @param int $id Order ID
     * @return JsonResponse
     */
    public function tracking(int $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->with('shipmentTracking')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // Build status timeline
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $currentStatusIndex = array_search($order->order_status, $statuses);

        $timeline = collect($statuses)->map(function ($status, $index) use ($currentStatusIndex, $order) {
            return [
                'status' => $status,
                'label' => ucfirst($status),
                'completed' => $index <= $currentStatusIndex && $order->order_status !== 'cancelled',
                'current' => $index === $currentStatusIndex,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'order_code' => $order->custom_order_code,
                'order_status' => $order->order_status,
                'status_label' => $order->status_label,
                'timeline' => $timeline,
                'shipping' => [
                    'courier_name' => $order->courier_name,
                    'awb_number' => $order->awb_number,
                    'estimated_delivery' => $order->shipmentTracking?->etd,
                ],
                'tracking_details' => $order->shipmentTracking ? [
                    'current_status' => $order->shipmentTracking->current_status,
                    'current_location' => $order->shipmentTracking->current_location,
                    'is_delivered' => $order->shipmentTracking->is_delivered,
                    'activities' => $order->shipmentTracking->activities ?? [],
                    'last_updated' => $order->shipmentTracking->last_synced_at,
                ] : null,
            ],
        ]);
    }

    /**
     * Get order statistics for partner dashboard.
     *
     * Returns summary of orders by status and total spent.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
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

        $stats = [
            'total_orders' => (clone $orders)->count(),
            'pending_orders' => (clone $orders)->where('order_status', 'pending')->count(),
            'confirmed_orders' => (clone $orders)->where('order_status', 'confirmed')->count(),
            'processing_orders' => (clone $orders)->where('order_status', 'processing')->count(),
            'shipped_orders' => (clone $orders)->where('order_status', 'shipped')->count(),
            'delivered_orders' => (clone $orders)->where('order_status', 'delivered')->count(),
            'cancelled_orders' => (clone $orders)->where('order_status', 'cancelled')->count(),
            'total_spent' => (clone $orders)->where('payment_status', 'paid')->sum('order_total'),
            'this_month_orders' => (clone $orders)->whereMonth('created_at', now()->month)->count(),
            'this_month_spent' => (clone $orders)
                ->whereMonth('created_at', now()->month)
                ->where('payment_status', 'paid')
                ->sum('order_total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Reorder - create a new order based on a previous order.
     *
     * Copies products from a previous order to the cart.
     *
     * @param int $id Order ID
     * @return JsonResponse
     */
    public function reorder(int $id): JsonResponse
    {
        $user = Auth::user();

        $order = Order::where('id', $id)
            ->where('customer_id', $user->id)
            ->with('orderProducts.product')
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $reorderItems = [];
        $unavailableItems = [];

        foreach ($order->orderProducts as $item) {
            if (!$item->product) {
                $unavailableItems[] = [
                    'product_id' => $item->product_id,
                    'reason' => 'Product no longer available',
                ];
                continue;
            }

            if ($item->product->available_stock < $item->quantity) {
                if ($item->product->available_stock > 0) {
                    $reorderItems[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'requested_quantity' => $item->quantity,
                        'available_quantity' => $item->product->available_stock,
                        'partial' => true,
                    ];
                } else {
                    $unavailableItems[] = [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'reason' => 'Out of stock',
                    ];
                }
            } else {
                $reorderItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'partial' => false,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'available_items' => $reorderItems,
                'unavailable_items' => $unavailableItems,
                'original_order_code' => $order->custom_order_code,
            ],
            'message' => count($unavailableItems) > 0
                ? 'Some items are no longer available.'
                : 'All items can be reordered.',
        ]);
    }
}
