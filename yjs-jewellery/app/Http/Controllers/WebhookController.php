<?php

namespace App\Http\Controllers;

use App\Services\Payment\RazorpayService;
use App\Services\Shipping\ShiprocketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Controller
 * Handles incoming webhooks from Razorpay and Shiprocket
 * 
 * IMPORTANT: These routes should NOT have CSRF protection
 * Add 'api/webhooks/*' to VerifyCsrfToken middleware $except array
 */
class WebhookController extends Controller
{
    public function __construct(
        private RazorpayService $razorpay,
        private ShiprocketService $shiprocket
    ) {}

    /**
     * Razorpay webhook handler
     * POST /api/webhooks/razorpay
     * 
     * Events handled:
     *   - payment.authorized
     *   - payment.captured
     *   - payment.failed
     *   - refund.processed
     *   - refund.failed
     */
    public function razorpay(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature');
        $rawBody = $request->getContent();

        // Verify signature
        if (!$signature || !$this->razorpay->verifyWebhookSignature($rawBody, $signature)) {
            Log::warning('Razorpay webhook: Invalid signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            $payload = json_decode($rawBody, true);

            if (!is_array($payload)) {
                Log::warning('Razorpay webhook: Invalid JSON payload');
                return response()->json(['error' => 'Invalid JSON payload'], 400);
            }

            // Validate required webhook fields
            if (empty($payload['event'])) {
                Log::warning('Razorpay webhook: Missing event type');
                return response()->json(['error' => 'Missing event type'], 400);
            }

            // Validate entity structure for payment/refund events
            $paymentEvents = ['payment.authorized', 'payment.captured', 'payment.failed'];
            $refundEvents = ['refund.processed', 'refund.failed'];

            if (in_array($payload['event'], $paymentEvents)) {
                if (empty($payload['payload']['payment']['entity']['id'])) {
                    Log::warning('Razorpay webhook: Missing payment entity ID', ['event' => $payload['event']]);
                    return response()->json(['error' => 'Missing payment entity'], 400);
                }
            }

            if (in_array($payload['event'], $refundEvents)) {
                if (empty($payload['payload']['refund']['entity']['id'])) {
                    Log::warning('Razorpay webhook: Missing refund entity ID', ['event' => $payload['event']]);
                    return response()->json(['error' => 'Missing refund entity'], 400);
                }
            }

            Log::info('Razorpay webhook received', [
                'event' => $payload['event'],
            ]);

            $result = $this->razorpay->processWebhook($payload);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Razorpay webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Shiprocket webhook handler
     * POST /api/webhooks/shiprocket
     *
     * Events handled:
     *   - Status updates (shipped, delivered, RTO, etc.)
     */
    public function shiprocket(Request $request): JsonResponse
    {
        $signature = $request->header('X-Shiprocket-Signature');
        $rawBody = $request->getContent();

        // Verify signature if webhook secret is configured
        if (!$this->shiprocket->verifyWebhookSignature($rawBody, $signature ?? '')) {
            Log::warning('Shiprocket webhook: Invalid signature', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            $payload = $request->all();

            // Validate required Shiprocket webhook fields
            if (empty($payload['awb']) && empty($payload['order_id']) && empty($payload['shipment_id'])) {
                Log::warning('Shiprocket webhook: Missing identifier (awb, order_id, or shipment_id)');
                return response()->json(['error' => 'Missing shipment identifier'], 400);
            }

            // Validate status field for status update webhooks
            if (isset($payload['current_status']) && !is_string($payload['current_status'])) {
                Log::warning('Shiprocket webhook: Invalid status format');
                return response()->json(['error' => 'Invalid status format'], 400);
            }

            // Sanitize and validate AWB if present
            if (!empty($payload['awb']) && !preg_match('/^[A-Za-z0-9]+$/', $payload['awb'])) {
                Log::warning('Shiprocket webhook: Invalid AWB format', ['awb' => $payload['awb']]);
                return response()->json(['error' => 'Invalid AWB format'], 400);
            }

            Log::info('Shiprocket webhook received', [
                'awb' => $payload['awb'] ?? 'N/A',
                'status' => $payload['current_status'] ?? 'N/A',
            ]);

            $result = $this->shiprocket->processWebhook($payload);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Shiprocket webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
