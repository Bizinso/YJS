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
            
            Log::info('Razorpay webhook received', [
                'event' => $payload['event'] ?? 'unknown',
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
        try {
            $payload = $request->all();
            
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
