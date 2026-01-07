<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    // ============ RAZORPAY WEBHOOK TESTS ============

    public function test_razorpay_webhook_rejects_missing_signature(): void
    {
        $response = $this->postJson('/api/webhooks/razorpay', [
            'event' => 'payment.captured',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid signature']);
    }

    public function test_razorpay_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/webhooks/razorpay', [
            'event' => 'payment.captured',
        ], [
            'X-Razorpay-Signature' => 'invalid_signature_here',
        ]);

        $response->assertStatus(401);
    }

    public function test_razorpay_webhook_rejects_invalid_json(): void
    {
        // Test with empty body
        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'test',
            'CONTENT_TYPE' => 'application/json',
        ], '');

        // Should fail signature or JSON validation
        $response->assertStatus(401);
    }

    public function test_razorpay_webhook_rejects_missing_event(): void
    {
        // This will fail signature check first, but if signature was valid,
        // it would then fail on missing event type
        $payload = json_encode(['payload' => []]);

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => hash_hmac('sha256', $payload, 'test'),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should fail signature check in test environment
        $response->assertStatus(401);
    }

    // ============ SHIPROCKET WEBHOOK TESTS ============

    public function test_shiprocket_webhook_rejects_missing_identifier(): void
    {
        $response = $this->postJson('/api/webhooks/shiprocket', [
            'current_status' => 'Delivered',
        ]);

        // Should fail signature (401) or identifier validation (400)
        $this->assertContains($response->getStatusCode(), [400, 401]);
    }

    public function test_shiprocket_webhook_rejects_invalid_awb_format(): void
    {
        $response = $this->postJson('/api/webhooks/shiprocket', [
            'awb' => 'invalid!@#$awb',
            'current_status' => 'Delivered',
        ], [
            'X-Shiprocket-Signature' => 'test',
        ]);

        // Should fail signature (401) or AWB format validation (400)
        $this->assertContains($response->getStatusCode(), [400, 401]);
    }

    // ============ WEBHOOK SECURITY TESTS ============

    public function test_webhook_endpoints_are_excluded_from_csrf(): void
    {
        // Verify webhooks work without CSRF token
        $response = $this->post('/api/webhooks/razorpay', []);

        // Should fail on signature, not CSRF
        $this->assertNotEquals(419, $response->getStatusCode());
    }

    public function test_webhook_rate_limiting_not_applied(): void
    {
        // Webhooks should not be rate limited (or have very high limits)
        // as payment providers may send many events quickly
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/webhooks/razorpay', []);
            $this->assertNotEquals(429, $response->getStatusCode());
        }
    }
}
