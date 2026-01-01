<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payment\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Order Payment Controller
 * Handles Razorpay payment creation, verification, and retry
 */
class OrderPaymentController extends Controller
{
    public function __construct(private RazorpayService $razorpay) {}

    /**
     * Create Razorpay order for checkout
     * POST /api/customer/orders/{order}/payment
     */
    public function createPayment(Order $order): JsonResponse
    {
        $userId = auth('customer')->id();
        
        // Verify ownership using EXISTING field
        if ($order->customer_id !== $userId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        // Check EXISTING payment_status field
        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'error' => 'Order already paid'], 400);
        }

        try {
            $result = $this->razorpay->createOrder($order);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify payment after Razorpay checkout
     * POST /api/customer/payment/verify
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $result = $this->razorpay->verifyPayment(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'order_id' => $result['order_id'],
                'message' => 'Payment successful',
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Payment verification failed',
        ], 400);
    }

    /**
     * Get payment status
     * GET /api/customer/orders/{order}/payment-status
     */
    public function getStatus(Order $order): JsonResponse
    {
        $userId = auth('customer')->id();
        
        if ($order->customer_id !== $userId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $payment = $order->payments()->latest()->first();

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_code' => $order->custom_order_code,
            'payment_status' => $order->payment_status,
            'payment_method' => $payment->payment_mode ?? null,
            'paid_at' => $order->paid_at?->toIso8601String(),
            'amount' => $order->order_total,
        ]);
    }

    /**
     * Retry failed payment
     * POST /api/customer/orders/{order}/retry-payment
     */
    public function retryPayment(Order $order): JsonResponse
    {
        $userId = auth('customer')->id();
        
        if ($order->customer_id !== $userId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        if ($order->payment_status === 'paid') {
            return response()->json(['success' => false, 'error' => 'Order already paid'], 400);
        }

        // Check if within 24-hour retry window
        if ($order->created_at->diffInHours(now()) > 24) {
            return response()->json(['success' => false, 'error' => 'Payment retry window expired (24 hours)'], 400);
        }

        try {
            $result = $this->razorpay->createOrder($order);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
