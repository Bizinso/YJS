<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderRefund;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RAZORPAY PAYMENT SERVICE
 * Based on Official API: https://razorpay.com/docs/api/
 * 
 * BACKWARD COMPATIBLE: Works with existing order_payments table
 * Existing fields used: order_id, payment_mode, transaction_id, amount, status
 * New fields added via migration: razorpay_order_id, razorpay_payment_id, etc.
 */
class RazorpayService
{
    private const API_BASE_URL = 'https://api.razorpay.com/v1';
    
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private bool $isLive;

    public function __construct()
    {
        $this->isLive = config('services.razorpay.mode', 'test') === 'live';
        
        $this->keyId = $this->isLive 
            ? config('services.razorpay.live_key_id')
            : config('services.razorpay.test_key_id');
            
        $this->keySecret = $this->isLive
            ? config('services.razorpay.live_key_secret')
            : config('services.razorpay.test_key_secret');
            
        $this->webhookSecret = $this->isLive
            ? config('services.razorpay.live_webhook_secret')
            : config('services.razorpay.test_webhook_secret');
    }

    /**
     * Create Razorpay order for checkout
     * POST /orders
     */
    public function createOrder(Order $order): array
    {
        // Idempotency: Check if payment order already exists
        $existingPayment = OrderPayment::where('order_id', $order->id)
            ->whereNotNull('razorpay_order_id')
            ->whereIn('status', ['pending', 'success'])
            ->first();

        if ($existingPayment && $existingPayment->razorpay_order_id) {
            return $this->buildCheckoutResponse($existingPayment, $order);
        }

        // Amount in paise (Razorpay requires smallest currency unit)
        $amountPaise = (int) ($order->order_total * 100);

        $payload = [
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => $order->custom_order_code,
            'notes' => [
                'order_id' => (string) $order->id,
                'customer_id' => (string) $order->customer_id,
            ],
        ];

        $response = $this->request('POST', '/orders', $payload);

        // Create payment record using EXISTING schema + new fields
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_mode' => 'upi', // Default, will be updated after payment
            'amount' => $order->order_total,
            'status' => 'pending',
            'razorpay_order_id' => $response['id'],
            'currency' => 'INR',
        ]);

        return $this->buildCheckoutResponse($payment, $order);
    }

    /**
     * Build checkout options for frontend Razorpay SDK
     */
    private function buildCheckoutResponse(OrderPayment $payment, Order $order): array
    {
        $customer = $order->customer;
        
        return [
            'success' => true,
            'razorpay_order_id' => $payment->razorpay_order_id,
            'razorpay_key' => $this->keyId,
            'amount' => (int) ($payment->amount * 100),
            'currency' => 'INR',
            'order_id' => $order->id,
            'checkout_options' => [
                'key' => $this->keyId,
                'amount' => (int) ($payment->amount * 100),
                'currency' => 'INR',
                'name' => config('app.name', 'YJS Jewellers'),
                'description' => 'Order #' . $order->custom_order_code,
                'order_id' => $payment->razorpay_order_id,
                'prefill' => [
                    'name' => $customer ? trim($customer->first_name . ' ' . $customer->last_name) : '',
                    'email' => $order->email ?? $customer?->email ?? '',
                    'contact' => $customer?->phone ?? '',
                ],
                'theme' => [
                    'color' => config('services.razorpay.theme_color', '#B8860B'),
                ],
            ],
        ];
    }

    /**
     * Verify payment signature after checkout
     * Signature = HMAC SHA256(razorpay_order_id|razorpay_payment_id, key_secret)
     */
    public function verifyPayment(string $razorpayOrderId, string $razorpayPaymentId, string $razorpaySignature): array
    {
        $payment = OrderPayment::where('razorpay_order_id', $razorpayOrderId)->first();
        
        if (!$payment) {
            return ['success' => false, 'error' => 'Payment record not found'];
        }

        // Verify signature
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        
        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            Log::warning('Razorpay signature mismatch', [
                'order_id' => $payment->order_id,
                'razorpay_order_id' => $razorpayOrderId,
            ]);
            
            $payment->update([
                'status' => 'failed', // Uses existing enum
                'error_description' => 'Signature verification failed',
            ]);
            
            return ['success' => false, 'error' => 'Signature verification failed'];
        }

        // Fetch payment details from Razorpay
        $paymentDetails = $this->fetchPayment($razorpayPaymentId);

        // Verify amount matches
        $expectedAmountPaise = (int) ($payment->amount * 100);
        if ($paymentDetails['amount'] != $expectedAmountPaise) {
            return ['success' => false, 'error' => 'Amount mismatch'];
        }

        // Map Razorpay method to existing payment_mode enum
        $paymentModeMap = [
            'upi' => 'upi',
            'card' => 'card',
            'netbanking' => 'netbanking',
            'wallet' => 'upi', // Map wallet to UPI
        ];
        $paymentMode = $paymentModeMap[$paymentDetails['method']] ?? 'upi';

        // Update payment record
        DB::transaction(function () use ($payment, $razorpayPaymentId, $paymentDetails, $paymentMode) {
            $payment->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'transaction_id' => $razorpayPaymentId, // Also set existing field
                'status' => 'success', // Uses existing enum
                'payment_mode' => $paymentMode, // Uses existing enum
                'method' => $paymentDetails['method'] ?? null,
                'bank' => $paymentDetails['bank'] ?? null,
                'wallet' => $paymentDetails['wallet'] ?? null,
                'vpa' => $paymentDetails['vpa'] ?? null,
                'card_last4' => $paymentDetails['card']['last4'] ?? null,
                'card_network' => $paymentDetails['card']['network'] ?? null,
                'fee' => ($paymentDetails['fee'] ?? 0) / 100, // Convert paise to rupees
                'tax' => ($paymentDetails['tax'] ?? 0) / 100,
                'captured_at' => $paymentDetails['status'] === 'captured' ? now() : null,
                'gateway_response' => json_encode($paymentDetails),
            ]);

            // Update order using EXISTING fields
            $order = $payment->order;
            $order->update([
                'payment_status' => 'paid', // Uses existing enum
                'order_status' => 'confirmed', // Uses existing enum
                'payment_method' => $paymentDetails['method'] ?? 'online',
                'paid_at' => now(),
            ]);
        });

        return [
            'success' => true,
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'message' => 'Payment verified successfully',
        ];
    }

    /**
     * Fetch payment details from Razorpay
     */
    public function fetchPayment(string $paymentId): array
    {
        return $this->request('GET', "/payments/{$paymentId}");
    }

    /**
     * Capture authorized payment (for manual capture flow)
     */
    public function capturePayment(string $paymentId, int $amountPaise): array
    {
        return $this->request('POST', "/payments/{$paymentId}/capture", [
            'amount' => $amountPaise,
            'currency' => 'INR',
        ]);
    }

    /**
     * Create full refund for order
     */
    public function refundFull(Order $order, string $reason = 'customer_request'): ?OrderRefund
    {
        $payment = OrderPayment::where('order_id', $order->id)
            ->where('status', 'success')
            ->whereNotNull('razorpay_payment_id')
            ->first();

        if (!$payment) {
            throw new \RuntimeException('No successful payment found for this order');
        }

        $amountPaise = (int) ($payment->amount * 100);
        return $this->processRefund($payment, $amountPaise, 'full', $reason);
    }

    /**
     * Create partial refund
     */
    public function refundPartial(Order $order, float $amountRupees, string $reason = 'partial_return'): ?OrderRefund
    {
        $payment = OrderPayment::where('order_id', $order->id)
            ->where('status', 'success')
            ->whereNotNull('razorpay_payment_id')
            ->first();

        if (!$payment) {
            throw new \RuntimeException('No successful payment found');
        }

        // Calculate max refundable
        $existingRefunds = OrderRefund::where('payment_id', $payment->id)
            ->whereIn('status', ['initiated', 'pending', 'processed'])
            ->sum('amount');

        $maxRefundable = $payment->amount - $existingRefunds;

        if ($amountRupees > $maxRefundable) {
            throw new \RuntimeException("Maximum refundable: ₹" . number_format($maxRefundable, 2));
        }

        $amountPaise = (int) ($amountRupees * 100);
        return $this->processRefund($payment, $amountPaise, 'partial', $reason);
    }

    /**
     * Process refund via Razorpay API
     */
    private function processRefund(OrderPayment $payment, int $amountPaise, string $type, string $reason): OrderRefund
    {
        $refund = OrderRefund::create([
            'order_id' => $payment->order_id,
            'payment_id' => $payment->id,
            'refund_code' => 'REF-' . strtoupper(Str::random(8)),
            'type' => $type,
            'amount' => $amountPaise / 100, // Store in rupees
            'reason' => $reason,
            'status' => 'initiated',
            'initiated_at' => now(),
            'initiated_by' => auth()->id(),
        ]);

        try {
            $response = $this->request('POST', "/payments/{$payment->razorpay_payment_id}/refund", [
                'amount' => $amountPaise,
                'speed' => 'normal',
                'notes' => [
                    'reason' => $reason,
                    'order_id' => (string) $payment->order_id,
                    'refund_code' => $refund->refund_code,
                ],
            ]);

            $refund->update([
                'razorpay_refund_id' => $response['id'],
                'status' => $response['status'] === 'processed' ? 'processed' : 'pending',
                'gateway_response' => json_encode($response),
                'processed_at' => $response['status'] === 'processed' ? now() : null,
            ]);

            // Update order payment status if full refund
            if ($type === 'full') {
                $payment->order->update(['payment_status' => 'failed']); // Using existing enum closest to 'refunded'
            }

            return $refund;

        } catch (\Exception $e) {
            $refund->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $rawBody, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $rawBody, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Process webhook event
     */
    public function processWebhook(array $payload): array
    {
        $event = $payload['event'] ?? null;

        // Duplicate prevention
        $cacheKey = 'razorpay_webhook_' . md5(json_encode($payload));
        if (Cache::has($cacheKey)) {
            return ['success' => true, 'message' => 'Already processed'];
        }
        Cache::put($cacheKey, true, 86400);

        Log::info('Razorpay webhook received', ['event' => $event]);

        return match ($event) {
            'payment.authorized' => $this->handlePaymentAuthorized($payload),
            'payment.captured' => $this->handlePaymentCaptured($payload),
            'payment.failed' => $this->handlePaymentFailed($payload),
            'refund.processed' => $this->handleRefundProcessed($payload),
            'refund.failed' => $this->handleRefundFailed($payload),
            default => ['success' => true, 'message' => 'Event ignored'],
        };
    }

    private function handlePaymentAuthorized(array $payload): array
    {
        $paymentData = $payload['payload']['payment']['entity'];
        $orderId = $paymentData['order_id'];

        $payment = OrderPayment::where('razorpay_order_id', $orderId)->first();
        if ($payment && $payment->status === 'pending') {
            $payment->update([
                'razorpay_payment_id' => $paymentData['id'],
                'transaction_id' => $paymentData['id'],
            ]);
        }

        return ['success' => true];
    }

    private function handlePaymentCaptured(array $payload): array
    {
        $paymentData = $payload['payload']['payment']['entity'];
        $orderId = $paymentData['order_id'];

        $payment = OrderPayment::where('razorpay_order_id', $orderId)->first();
        if ($payment) {
            DB::transaction(function () use ($payment, $paymentData) {
                $payment->update([
                    'razorpay_payment_id' => $paymentData['id'],
                    'transaction_id' => $paymentData['id'],
                    'status' => 'success',
                    'captured_at' => now(),
                    'gateway_response' => json_encode($paymentData),
                ]);

                $payment->order->update([
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'paid_at' => now(),
                ]);
            });
        }

        return ['success' => true];
    }

    private function handlePaymentFailed(array $payload): array
    {
        $paymentData = $payload['payload']['payment']['entity'];
        $orderId = $paymentData['order_id'];

        $payment = OrderPayment::where('razorpay_order_id', $orderId)->first();
        if ($payment) {
            $payment->update([
                'status' => 'failed',
                'error_code' => $paymentData['error_code'] ?? null,
                'error_description' => $paymentData['error_description'] ?? null,
            ]);

            $payment->order->update([
                'payment_status' => 'failed',
            ]);
        }

        return ['success' => true];
    }

    private function handleRefundProcessed(array $payload): array
    {
        $refundData = $payload['payload']['refund']['entity'];
        
        $refund = OrderRefund::where('razorpay_refund_id', $refundData['id'])->first();
        if ($refund) {
            $refund->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        }

        return ['success' => true];
    }

    private function handleRefundFailed(array $payload): array
    {
        $refundData = $payload['payload']['refund']['entity'];
        
        $refund = OrderRefund::where('razorpay_refund_id', $refundData['id'])->first();
        if ($refund) {
            $refund->update([
                'status' => 'failed',
                'failure_reason' => $refundData['error_description'] ?? 'Refund failed',
            ]);
        }

        return ['success' => true];
    }

    /**
     * Make API request to Razorpay
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_BASE_URL . $endpoint;

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->timeout(30)
            ->acceptJson();

        $response = match (strtoupper($method)) {
            'GET' => $response->get($url, $data),
            'POST' => $response->post($url, $data),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->successful()) {
            return $response->json();
        }

        $error = $response->json();
        throw new RazorpayException(
            $error['error']['description'] ?? 'API error',
            $error['error']['code'] ?? 'API_ERROR',
            $response->status()
        );
    }

    public function isLive(): bool
    {
        return $this->isLive;
    }
}

class RazorpayException extends \Exception
{
    public function __construct(
        string $message,
        public string $errorCode,
        public int $httpStatus
    ) {
        parent::__construct($message);
    }
}
