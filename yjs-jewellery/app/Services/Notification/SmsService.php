<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Service
 *
 * Sends SMS notifications via configured gateway.
 * Supports multiple providers: MSG91, Twilio, TextLocal
 */
class SmsService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'msg91');
        $this->config = config("services.sms.{$this->provider}", []);
    }

    /**
     * Send OTP SMS
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        $message = "Your YJS Jewellers OTP is: {$otp}. Valid for 10 minutes. Do not share with anyone.";

        return $this->send($phone, $message, 'otp');
    }

    /**
     * Send order confirmation SMS
     */
    public function sendOrderConfirmation(string $phone, string $orderCode, float $amount): bool
    {
        $message = "Your order #{$orderCode} for Rs.{$amount} has been confirmed. Thank you for shopping at YJS Jewellers!";

        return $this->send($phone, $message, 'transactional');
    }

    /**
     * Send order shipped SMS
     */
    public function sendOrderShipped(string $phone, string $orderCode, string $awb): bool
    {
        $message = "Your order #{$orderCode} has been shipped! Track with AWB: {$awb}. YJS Jewellers";

        return $this->send($phone, $message, 'transactional');
    }

    /**
     * Send order delivered SMS
     */
    public function sendOrderDelivered(string $phone, string $orderCode): bool
    {
        $message = "Your order #{$orderCode} has been delivered. We hope you love it! - YJS Jewellers";

        return $this->send($phone, $message, 'transactional');
    }

    /**
     * Send generic SMS
     */
    public function send(string $phone, string $message, string $type = 'transactional'): bool
    {
        // Normalize phone number
        $phone = $this->normalizePhone($phone);

        if (!$this->isValidPhone($phone)) {
            Log::warning("Invalid phone number for SMS", ['phone' => $phone]);
            return false;
        }

        try {
            return match ($this->provider) {
                'msg91' => $this->sendViaMSG91($phone, $message, $type),
                'twilio' => $this->sendViaTwilio($phone, $message),
                'textlocal' => $this->sendViaTextLocal($phone, $message),
                default => $this->logOnly($phone, $message),
            };
        } catch (\Exception $e) {
            Log::error("SMS sending failed", [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send via MSG91
     */
    protected function sendViaMSG91(string $phone, string $message, string $type): bool
    {
        $authKey = $this->config['auth_key'] ?? '';
        $senderId = $this->config['sender_id'] ?? 'YJSJWL';
        $route = $type === 'otp' ? '4' : '1';

        if (empty($authKey)) {
            return $this->logOnly($phone, $message);
        }

        $response = Http::get('https://api.msg91.com/api/sendhttp.php', [
            'authkey' => $authKey,
            'mobiles' => $phone,
            'message' => $message,
            'sender' => $senderId,
            'route' => $route,
            'country' => '91',
        ]);

        $success = $response->successful();
        Log::info("MSG91 SMS sent", [
            'phone' => $phone,
            'success' => $success,
            'response' => $response->body(),
        ]);

        return $success;
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = $this->config['sid'] ?? '';
        $token = $this->config['token'] ?? '';
        $from = $this->config['from'] ?? '';

        if (empty($sid) || empty($token)) {
            return $this->logOnly($phone, $message);
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => "+91{$phone}",
                'Body' => $message,
            ]);

        $success = $response->successful();
        Log::info("Twilio SMS sent", [
            'phone' => $phone,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * Send via TextLocal
     */
    protected function sendViaTextLocal(string $phone, string $message): bool
    {
        $apiKey = $this->config['api_key'] ?? '';
        $sender = $this->config['sender'] ?? 'YJSJWL';

        if (empty($apiKey)) {
            return $this->logOnly($phone, $message);
        }

        $response = Http::asForm()->post('https://api.textlocal.in/send/', [
            'apikey' => $apiKey,
            'numbers' => $phone,
            'message' => $message,
            'sender' => $sender,
        ]);

        $success = $response->successful();
        Log::info("TextLocal SMS sent", [
            'phone' => $phone,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * Log only (when no provider configured)
     */
    protected function logOnly(string $phone, string $message): bool
    {
        Log::info("SMS (log only - no provider configured)", [
            'phone' => $phone,
            'message' => $message,
        ]);
        return true;
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove country code if present
        if (str_starts_with($phone, '91') && strlen($phone) === 12) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    /**
     * Validate phone number
     */
    protected function isValidPhone(string $phone): bool
    {
        return strlen($phone) === 10 && is_numeric($phone);
    }
}
