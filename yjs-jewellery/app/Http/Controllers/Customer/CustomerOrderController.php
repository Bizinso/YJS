<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerOrderController extends Controller
{

    /**
     * Get list of orders for the authenticated customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = Order::where('customer_id', $user->id)
                ->with([
                    'orderProducts.product:id,name,slug',
                    'orderProducts.product.media',
                ])
                ->select([
                    'id',
                    'custom_order_code',
                    'order_date',
                    'order_status',
                    'payment_status',
                    'order_total',
                    'created_at',
                ]);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('order_status', $request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('order_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('order_date', '<=', $request->to_date);
            }

            // Search by order code
            if ($request->has('search')) {
                $query->where('custom_order_code', 'like', '%' . $request->search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $orders = $query->paginate($perPage);

            // Transform the data
            $transformedOrders = $orders->getCollection()->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'order_date' => $order->order_date?->format('Y-m-d'),
                    'status' => $order->order_status,
                    'status_label' => $order->status_label,
                    'payment_status' => $order->payment_status,
                    'payment_status_label' => $order->payment_status_label,
                    'total' => $order->order_total,
                    'items_count' => $order->orderProducts->count(),
                    'first_product' => $order->orderProducts->first() ? [
                        'name' => $order->orderProducts->first()->product?->name,
                        'image' => $order->orderProducts->first()->product?->media->first()?->getUrl('thumb'),
                    ] : null,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully.',
                'data' => $transformedOrders,
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders. Please try again.',
            ], 500);
        }
    }

    /**
     * Get details of a specific order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::where('customer_id', $user->id)
                ->where('id', $id)
                ->with([
                    'orderProducts.product:id,name,slug,sku',
                    'orderProducts.product.media',
                    'billingAddress.country',
                    'shippingAddress.country',
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

            $orderData = [
                'id' => $order->id,
                'order_code' => $order->custom_order_code,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'status' => $order->order_status,
                'status_label' => $order->status_label,
                'payment_status' => $order->payment_status,
                'payment_status_label' => $order->payment_status_label,
                'payment_method' => $order->payment_method,
                'can_cancel' => $order->canBeCancelled(),

                // Financial Summary
                'subtotal' => $order->order_subtotal,
                'shipping_charges' => $order->shipping_charges,
                'total_taxes' => $order->total_taxes,
                'total_charges' => $order->total_charges,
                'coupon_code' => $order->coupon_code,
                'total' => $order->order_total,

                // Order Items
                'items' => $order->orderProducts->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_slug' => $item->product?->slug,
                        'product_sku' => $item->product?->sku,
                        'product_image' => $item->product?->media->first()?->getUrl('thumb'),
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'discount' => $item->discount,
                        'tax' => $item->tax,
                        'total' => $item->total,
                    ];
                }),

                // Addresses
                'billing_address' => $order->billingAddress ? [
                    'full_name' => $order->billingAddress->full_name,
                    'phone' => $order->billingAddress->phone,
                    'address_line1' => $order->billingAddress->address_line1,
                    'address_line2' => $order->billingAddress->address_line2,
                    'landmark' => $order->billingAddress->landmark,
                    'city' => $order->billingAddress->city,
                    'state' => $order->billingAddress->state,
                    'postal_code' => $order->billingAddress->postal_code,
                    'country' => $order->billingAddress->country?->name,
                ] : null,

                'shipping_address' => $order->shippingAddress ? [
                    'full_name' => $order->shippingAddress->full_name,
                    'phone' => $order->shippingAddress->phone,
                    'address_line1' => $order->shippingAddress->address_line1,
                    'address_line2' => $order->shippingAddress->address_line2,
                    'landmark' => $order->shippingAddress->landmark,
                    'city' => $order->shippingAddress->city,
                    'state' => $order->shippingAddress->state,
                    'postal_code' => $order->shippingAddress->postal_code,
                    'country' => $order->shippingAddress->country?->name,
                ] : null,

                // Shipping Info
                'shipping' => [
                    'method' => $order->shipping_method,
                    'courier_name' => $order->courier_name,
                    'awb_number' => $order->awb_number,
                    'estimated_delivery' => $order->delivery_date?->format('Y-m-d'),
                ],

                // Tracking Info
                'tracking' => $order->shipmentTracking ? [
                    'current_status' => $order->shipmentTracking->current_status,
                    'current_location' => $order->shipmentTracking->current_location,
                    'is_delivered' => $order->shipmentTracking->is_delivered,
                    'etd' => $order->shipmentTracking->etd,
                    'activities' => $order->shipmentTracking->formatted_activities ?? [],
                ] : null,

                // Payment History
                'payments' => $order->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_mode' => $payment->payment_mode,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'transaction_id' => $payment->transaction_id,
                        'razorpay_payment_id' => $payment->razorpay_payment_id,
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    ];
                }),

                // Offer Applied
                'offer' => $order->orderOffer ? [
                    'code' => $order->orderOffer->offer_code,
                    'title' => $order->orderOffer->offer_title,
                    'discount_applied' => $order->orderOffer->applied_discount,
                ] : null,

                // Cancellation Info
                'cancellation' => $order->cancellation ? [
                    'cancelled_by' => $order->cancellation->cancelled_by,
                    'reason' => $order->cancellation->reason_text,
                    'cancelled_at' => $order->cancellation->cancelled_at?->format('Y-m-d H:i:s'),
                    'refund_status' => $order->cancellation->refund_status,
                    'refund_amount' => $order->cancellation->refund_amount,
                ] : null,

                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Order retrieved successfully.',
                'data' => $orderData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'order_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order. Please try again.',
            ], 500);
        }
    }

    /**
     * Cancel an order (customer-initiated).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Please provide a reason for cancellation.',
            'reason.max' => 'Reason cannot exceed 500 characters.',
        ]);

        try {
            $user = $request->user();

            $order = Order::where('customer_id', $user->id)
                ->where('id', $id)
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
                    'message' => 'This order cannot be cancelled. Orders can only be cancelled within 24 hours of placement and before being processed.',
                ], 422);
            }

            DB::beginTransaction();

            $previousStatus = $order->order_status;

            // Update order status
            $order->update([
                'order_status' => 'cancelled',
            ]);

            // Create cancellation record
            $cancellation = OrderCancellation::create([
                'order_id' => $order->id,
                'cancelled_by' => 'customer',
                'cancelled_by_user_id' => $user->id,
                'reason_code' => 'customer_request',
                'reason_text' => $request->reason,
                'order_status_at_cancel' => $previousStatus,
                'refund_status' => $order->payment_status === 'paid' ? 'pending' : null,
                'refund_amount' => $order->payment_status === 'paid' ? $order->order_total : null,
                'cancelled_at' => now(),
            ]);

            // Create refund request if payment was made
            if ($order->payment_status === 'paid') {
                RefundRequest::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'refund_type' => RefundRequest::TYPE_FULL,
                    'original_amount' => $order->order_total,
                    'refund_amount' => $order->order_total,
                    'deductions' => 0,
                    'status' => RefundRequest::STATUS_PENDING,
                    'source' => RefundRequest::SOURCE_CANCELLATION,
                    'source_id' => $cancellation->id,
                    'source_type' => OrderCancellation::class,
                    'refund_method' => RefundRequest::METHOD_ORIGINAL_PAYMENT,
                    'reason_code' => 'customer_cancellation',
                    'reason_description' => $request->reason,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'status' => 'cancelled',
                    'refund_status' => $order->payment_status === 'paid' ? 'Refund will be processed within 5-7 business days.' : null,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error cancelling order: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'order_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order. Please try again.',
            ], 500);
        }
    }

    /**
     * Get order tracking details.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function tracking(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            $order = Order::where('customer_id', $user->id)
                ->where('id', $id)
                ->with(['shipmentTracking'])
                ->select(['id', 'custom_order_code', 'order_status', 'awb_number', 'courier_name', 'delivery_date'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found.',
                ], 404);
            }

            // Define status timeline
            $statusTimeline = [
                ['status' => 'pending', 'label' => 'Order Placed', 'completed' => true],
                ['status' => 'confirmed', 'label' => 'Order Confirmed', 'completed' => in_array($order->order_status, ['confirmed', 'processing', 'shipped', 'pickup_generated', 'picked_up', 'delivered'])],
                ['status' => 'processing', 'label' => 'Processing', 'completed' => in_array($order->order_status, ['processing', 'shipped', 'pickup_generated', 'picked_up', 'delivered'])],
                ['status' => 'shipped', 'label' => 'Shipped', 'completed' => in_array($order->order_status, ['shipped', 'pickup_generated', 'picked_up', 'delivered'])],
                ['status' => 'delivered', 'label' => 'Delivered', 'completed' => $order->order_status === 'delivered'],
            ];

            // Mark current status
            foreach ($statusTimeline as &$step) {
                $step['is_current'] = $step['status'] === $order->order_status ||
                    ($order->order_status === 'pickup_generated' && $step['status'] === 'shipped') ||
                    ($order->order_status === 'picked_up' && $step['status'] === 'shipped');
            }

            $trackingData = [
                'order_code' => $order->custom_order_code,
                'current_status' => $order->order_status,
                'status_label' => $order->status_label,
                'awb_number' => $order->awb_number,
                'courier_name' => $order->courier_name,
                'estimated_delivery' => $order->delivery_date?->format('Y-m-d'),
                'status_timeline' => $statusTimeline,
                'tracking_details' => $order->shipmentTracking ? [
                    'current_location' => $order->shipmentTracking->current_location,
                    'is_delivered' => $order->shipmentTracking->is_delivered,
                    'is_rto' => $order->shipmentTracking->is_rto,
                    'etd' => $order->shipmentTracking->etd,
                    'last_updated' => $order->shipmentTracking->last_synced_at?->format('Y-m-d H:i:s'),
                    'activities' => $order->shipmentTracking->formatted_activities ?? [],
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Tracking details retrieved successfully.',
                'data' => $trackingData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching tracking: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'order_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking details. Please try again.',
            ], 500);
        }
    }

    /**
     * Get order statistics for the customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $stats = [
                'total_orders' => Order::where('customer_id', $user->id)->count(),
                'pending_orders' => Order::where('customer_id', $user->id)
                    ->whereIn('order_status', ['pending', 'confirmed', 'processing'])
                    ->count(),
                'shipped_orders' => Order::where('customer_id', $user->id)
                    ->whereIn('order_status', ['shipped', 'pickup_generated', 'picked_up'])
                    ->count(),
                'delivered_orders' => Order::where('customer_id', $user->id)
                    ->where('order_status', 'delivered')
                    ->count(),
                'cancelled_orders' => Order::where('customer_id', $user->id)
                    ->where('order_status', 'cancelled')
                    ->count(),
                'total_spent' => Order::where('customer_id', $user->id)
                    ->where('payment_status', 'paid')
                    ->sum('order_total'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching order statistics: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
            ], 500);
        }
    }
}
