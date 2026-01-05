<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShipmentTracking;
use App\Services\Shipping\ShiprocketService;
use App\Services\Shipping\ShiprocketException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Shipping Controller
 *
 * Handles admin shipping management including
 * pending shipments, bulk operations, and analytics.
 */
class AdminShippingController extends Controller
{
    public function __construct(private ShiprocketService $shiprocket) {}

    /**
     * Get pending shipments list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pendingShipments(Request $request): JsonResponse
    {
        $query = Order::query()
            ->where('payment_status', 'paid')
            ->whereIn('order_status', ['confirmed', 'processing'])
            ->whereNull('awb_number')
            ->with(['customer:id,first_name,last_name,email', 'shippingAddress']);

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        // Search by order code or customer name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('custom_order_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $orders = $query->orderBy('order_date', 'desc')->paginate($perPage);

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
     * Get shipped orders list.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function shippedOrders(Request $request): JsonResponse
    {
        $query = Order::query()
            ->whereNotNull('awb_number')
            ->with(['customer:id,first_name,last_name,email', 'shipmentTracking']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('order_status', $request->status);
        }

        // Filter by courier
        if ($request->has('courier')) {
            $query->where('courier_name', 'like', "%{$request->courier}%");
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('order_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('order_date', '<=', $request->date_to);
        }

        // Search by order code or AWB
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('custom_order_code', 'like', "%{$search}%")
                  ->orWhere('awb_number', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->input('per_page', 20), 100);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

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
     * Bulk push orders to Shiprocket.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkPushToShiprocket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:50',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'successes' => [],
            'errors' => [],
        ];

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->where('payment_status', 'paid')
            ->whereIn('order_status', ['confirmed', 'processing'])
            ->get();

        foreach ($orders as $order) {
            try {
                $result = $this->shiprocket->createOrder($order);
                $results['success_count']++;
                $results['successes'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'shiprocket_order_id' => $result['shiprocket_order_id'],
                ];
            } catch (\Exception $e) {
                $results['failed_count']++;
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Bulk generate AWBs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkGenerateAWB(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:50',
            'order_ids.*' => 'integer|exists:orders,id',
            'courier_id' => 'nullable|integer',
        ]);

        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'successes' => [],
            'errors' => [],
        ];

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->whereNotNull('shipment_id')
            ->whereNull('awb_number')
            ->get();

        foreach ($orders as $order) {
            try {
                $result = $this->shiprocket->generateAWB($order, $validated['courier_id'] ?? null);
                $results['success_count']++;
                $results['successes'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'awb' => $result['awb'],
                    'courier' => $result['courier_name'],
                ];
            } catch (\Exception $e) {
                $results['failed_count']++;
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Bulk schedule pickups.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkSchedulePickup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:50',
            'order_ids.*' => 'integer|exists:orders,id',
            'pickup_date' => 'nullable|date|after:today',
        ]);

        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'successes' => [],
            'errors' => [],
        ];

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->whereNotNull('awb_number')
            ->get();

        foreach ($orders as $order) {
            try {
                $result = $this->shiprocket->schedulePickup($order, $validated['pickup_date'] ?? null);
                $results['success_count']++;
                $results['successes'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'pickup_date' => $result['pickup_date'],
                ];
            } catch (\Exception $e) {
                $results['failed_count']++;
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Bulk sync tracking for all shipped orders.
     *
     * @return JsonResponse
     */
    public function bulkSyncTracking(): JsonResponse
    {
        $orders = Order::whereNotNull('awb_number')
            ->whereNotIn('order_status', ['delivered', 'cancelled', 'returned'])
            ->get();

        $results = [
            'synced_count' => 0,
            'failed_count' => 0,
            'status_changed' => [],
        ];

        foreach ($orders as $order) {
            $oldStatus = $order->order_status;

            try {
                $this->shiprocket->syncOrderTracking($order);
                $order->refresh();
                $results['synced_count']++;

                if ($oldStatus !== $order->order_status) {
                    $results['status_changed'][] = [
                        'order_id' => $order->id,
                        'order_code' => $order->custom_order_code,
                        'old_status' => $oldStatus,
                        'new_status' => $order->order_status,
                    ];
                }
            } catch (\Exception $e) {
                $results['failed_count']++;
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Get shipping dashboard summary.
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        // Pending to ship
        $pendingToShip = Order::where('payment_status', 'paid')
            ->whereIn('order_status', ['confirmed', 'processing'])
            ->whereNull('awb_number')
            ->count();

        // AWB pending (Shiprocket order created but no AWB)
        $awbPending = Order::whereNotNull('shiprocket_order_id')
            ->whereNull('awb_number')
            ->count();

        // Ready for pickup
        $readyForPickup = Order::whereNotNull('awb_number')
            ->whereNull('pickup_scheduled_date')
            ->count();

        // In transit
        $inTransit = Order::whereNotNull('awb_number')
            ->where('order_status', 'shipped')
            ->count();

        // Out for delivery
        $outForDelivery = ShipmentTracking::where('current_status', 'OUT FOR DELIVERY')
            ->count();

        // Delivered today
        $deliveredToday = Order::where('order_status', 'delivered')
            ->whereDate('delivery_date', today())
            ->count();

        // Delivered this week
        $deliveredThisWeek = Order::where('order_status', 'delivered')
            ->whereDate('delivery_date', '>=', now()->startOfWeek())
            ->count();

        // RTO (Return to Origin)
        $rtoCount = Order::where('order_status', 'returned')
            ->whereNotNull('awb_number')
            ->count();

        // Average delivery time (last 30 days)
        $avgDeliveryDays = Order::where('order_status', 'delivered')
            ->whereDate('delivery_date', '>=', now()->subDays(30))
            ->whereNotNull('delivery_date')
            ->whereNotNull('order_date')
            ->selectRaw('AVG(JULIANDAY(delivery_date) - JULIANDAY(order_date)) as avg_days')
            ->value('avg_days');

        // Courier breakdown
        $courierBreakdown = Order::whereNotNull('courier_name')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->select('courier_name', DB::raw('COUNT(*) as count'))
            ->groupBy('courier_name')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_to_ship' => $pendingToShip,
                'awb_pending' => $awbPending,
                'ready_for_pickup' => $readyForPickup,
                'in_transit' => $inTransit,
                'out_for_delivery' => $outForDelivery,
                'delivered_today' => $deliveredToday,
                'delivered_this_week' => $deliveredThisWeek,
                'rto_count' => $rtoCount,
                'avg_delivery_days' => round($avgDeliveryDays ?? 0, 1),
                'courier_breakdown' => $courierBreakdown,
            ],
        ]);
    }

    /**
     * Get order shipping details.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function orderDetails(Order $order): JsonResponse
    {
        $order->load(['customer:id,first_name,last_name,email,phone', 'shippingAddress', 'shipmentTracking']);

        $tracking = null;
        if ($order->awb_number) {
            try {
                $tracking = $this->shiprocket->trackByAWB($order->awb_number);
            } catch (\Exception $e) {
                $tracking = ['error' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_code' => $order->custom_order_code,
                'order_status' => $order->order_status,
                'shipping_status' => $order->shipping_status,
                'shiprocket_order_id' => $order->shiprocket_order_id,
                'shipment_id' => $order->shipment_id,
                'awb_number' => $order->awb_number,
                'courier_name' => $order->courier_name,
                'pickup_scheduled_date' => $order->pickup_scheduled_date,
                'delivery_date' => $order->delivery_date,
                'customer' => $order->customer,
                'shipping_address' => $order->shippingAddress,
            ],
            'tracking' => $tracking,
            'shipment_tracking' => $order->shipmentTracking,
        ]);
    }

    /**
     * Get bulk labels for multiple orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkGetLabels(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1|max:50',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])
            ->whereNotNull('shipment_id')
            ->get();

        $labels = [];

        foreach ($orders as $order) {
            try {
                $labelUrl = $this->shiprocket->generateLabel((int) $order->shipment_id);
                $labels[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'label_url' => $labelUrl,
                ];
            } catch (\Exception $e) {
                $labels[] = [
                    'order_id' => $order->id,
                    'order_code' => $order->custom_order_code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'labels' => $labels,
        ]);
    }

    /**
     * Check pincode serviceability (admin can check any pincode).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkServiceability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => 'required|string|size:6',
            'weight' => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->shiprocket->checkServiceability(
                $validated['pincode'],
                $validated['weight'] ?? 100
            );
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
