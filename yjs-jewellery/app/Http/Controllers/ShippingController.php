<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Shipping Controller
 * Handles Shiprocket shipping operations
 * 
 * Uses EXISTING Order fields: shiprocket_order_id, shipment_id, awb_number, etc.
 */
class ShippingController extends Controller
{
    public function __construct(private ShiprocketService $shiprocket) {}

    // =========================================================================
    // CUSTOMER ENDPOINTS
    // =========================================================================

    /**
     * Check serviceability for pincode
     * GET /api/customer/shipping/serviceability
     */
    public function checkServiceability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pincode' => 'required|string|size:6',
            'weight' => 'nullable|integer|min:1',
        ]);

        $result = $this->shiprocket->checkServiceability(
            $validated['pincode'],
            $validated['weight'] ?? 100
        );

        return response()->json($result);
    }

    /**
     * Track order shipment
     * GET /api/customer/orders/{order}/tracking
     */
    public function track(Order $order): JsonResponse
    {
        $userId = auth('customer')->id();
        
        if ($order->customer_id !== $userId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        // Use EXISTING awb_number field
        if (!$order->awb_number) {
            return response()->json([
                'success' => false,
                'error' => 'Shipment not created yet',
                'order_status' => $order->order_status,
            ], 400);
        }

        try {
            $result = $this->shiprocket->trackByAWB($order->awb_number);
            return response()->json(['success' => true, 'tracking' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ADMIN/EMPLOYEE ENDPOINTS
    // =========================================================================

    /**
     * Push order to Shiprocket
     * POST /api/employee/orders/{order}/ship
     */
    public function pushToShiprocket(Order $order): JsonResponse
    {
        // Validate order status using EXISTING enum
        if (!in_array($order->order_status, ['confirmed', 'processing'])) {
            return response()->json([
                'success' => false, 
                'error' => 'Order not ready for shipping. Current status: ' . $order->order_status
            ], 400);
        }

        if ($order->payment_status !== 'paid') {
            return response()->json(['success' => false, 'error' => 'Order not paid'], 400);
        }

        try {
            $result = $this->shiprocket->createOrder($order);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate AWB for order
     * POST /api/employee/orders/{order}/generate-awb
     */
    public function generateAWB(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'courier_id' => 'nullable|integer',
        ]);

        try {
            $result = $this->shiprocket->generateAWB($order, $validated['courier_id'] ?? null);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Schedule pickup
     * POST /api/employee/orders/{order}/schedule-pickup
     */
    public function schedulePickup(Order $order, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_date' => 'nullable|date|after:today',
        ]);

        try {
            $result = $this->shiprocket->schedulePickup($order, $validated['pickup_date'] ?? null);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get shipping label
     * GET /api/employee/orders/{order}/label
     */
    public function getLabel(Order $order): JsonResponse
    {
        if (!$order->shipment_id) {
            return response()->json(['success' => false, 'error' => 'No shipment found'], 400);
        }

        try {
            $labelUrl = $this->shiprocket->generateLabel((int) $order->shipment_id);
            return response()->json(['success' => true, 'label_url' => $labelUrl]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel Shiprocket order
     * POST /api/employee/orders/{order}/cancel-shipment
     */
    public function cancelShipment(Order $order): JsonResponse
    {
        try {
            $result = $this->shiprocket->cancelOrder($order);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sync tracking for order
     * POST /api/employee/orders/{order}/sync-tracking
     */
    public function syncTracking(Order $order): JsonResponse
    {
        if (!$order->awb_number) {
            return response()->json(['success' => false, 'error' => 'No AWB found'], 400);
        }

        try {
            $this->shiprocket->syncOrderTracking($order);
            $order->refresh();
            
            return response()->json([
                'success' => true,
                'order_status' => $order->order_status,
                'shipping_status' => $order->shipping_status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
