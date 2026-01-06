<?php

namespace App\Services\Refund;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use App\Services\Payment\RazorpayService;
use Illuminate\Support\Facades\Log;

/**
 * Refund Service
 *
 * Handles refund processing for returns and cancellations.
 * Acts as a wrapper around payment gateway refund operations.
 */
class RefundService
{
    public function __construct(
        private RazorpayService $razorpayService
    ) {}

    /**
     * Process a refund for an order
     *
     * @param Order $order The order to refund
     * @param float $amount Amount to refund in rupees
     * @param string $reason Reason for the refund
     * @return array{success: bool, refund_id: string|null, message: string|null, refund: OrderRefund|null}
     */
    public function processRefund(Order $order, float $amount, string $reason): array
    {
        try {
            // Validate order has a successful payment
            $payment = OrderPayment::where('order_id', $order->id)
                ->where('status', 'success')
                ->whereNotNull('razorpay_payment_id')
                ->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'refund_id' => null,
                    'message' => 'No successful payment found for this order',
                    'refund' => null,
                ];
            }

            // Check if refund amount is valid
            $totalPaid = $payment->amount;
            $existingRefunds = OrderRefund::where('order_id', $order->id)
                ->whereIn('status', ['initiated', 'processing', 'completed'])
                ->sum('amount');

            $availableForRefund = $totalPaid - $existingRefunds;

            if ($amount > $availableForRefund) {
                return [
                    'success' => false,
                    'refund_id' => null,
                    'message' => "Refund amount ({$amount}) exceeds available refundable amount ({$availableForRefund})",
                    'refund' => null,
                ];
            }

            // Determine refund type
            $isFullRefund = abs($amount - $availableForRefund) < 0.01; // Allow small tolerance

            // Process refund through Razorpay
            if ($isFullRefund) {
                $refund = $this->razorpayService->refundFull($order, $reason);
            } else {
                $refund = $this->razorpayService->refundPartial($order, $amount, $reason);
            }

            if (!$refund) {
                return [
                    'success' => false,
                    'refund_id' => null,
                    'message' => 'Refund creation failed',
                    'refund' => null,
                ];
            }

            Log::info("Refund processed successfully", [
                'order_id' => $order->id,
                'refund_id' => $refund->id,
                'refund_code' => $refund->refund_code,
                'amount' => $amount,
                'type' => $isFullRefund ? 'full' : 'partial',
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->refund_code,
                'message' => 'Refund initiated successfully',
                'refund' => $refund,
            ];

        } catch (\Exception $e) {
            Log::error("Refund processing failed", [
                'order_id' => $order->id,
                'amount' => $amount,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'refund_id' => null,
                'message' => $e->getMessage(),
                'refund' => null,
            ];
        }
    }

    /**
     * Get refund status for an order
     *
     * @param Order $order
     * @return array
     */
    public function getRefundStatus(Order $order): array
    {
        $refunds = OrderRefund::where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalRefunded = $refunds->where('status', 'completed')->sum('amount');
        $pendingRefunds = $refunds->whereIn('status', ['initiated', 'processing'])->sum('amount');

        return [
            'total_refunded' => $totalRefunded,
            'pending_refunds' => $pendingRefunds,
            'refunds' => $refunds,
        ];
    }

    /**
     * Check if an order is eligible for refund
     *
     * @param Order $order
     * @return array{eligible: bool, max_refundable: float, message: string|null}
     */
    public function checkRefundEligibility(Order $order): array
    {
        $payment = OrderPayment::where('order_id', $order->id)
            ->where('status', 'success')
            ->first();

        if (!$payment) {
            return [
                'eligible' => false,
                'max_refundable' => 0,
                'message' => 'No successful payment found',
            ];
        }

        $existingRefunds = OrderRefund::where('order_id', $order->id)
            ->whereIn('status', ['initiated', 'processing', 'completed'])
            ->sum('amount');

        $maxRefundable = max(0, $payment->amount - $existingRefunds);

        return [
            'eligible' => $maxRefundable > 0,
            'max_refundable' => $maxRefundable,
            'message' => $maxRefundable > 0 ? null : 'No refundable amount remaining',
        ];
    }
}
