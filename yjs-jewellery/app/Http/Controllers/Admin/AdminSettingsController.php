<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\SettingsHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Admin Settings Controller
 *
 * Manages all system settings across different groups.
 */
class AdminSettingsController extends Controller
{
    /**
     * Get all settings groups overview.
     */
    public function index(): JsonResponse
    {
        $groups = [
            SystemSetting::GROUP_STORE => 'Store Settings',
            SystemSetting::GROUP_PAYMENT => 'Payment Settings',
            SystemSetting::GROUP_SHIPPING => 'Shipping Settings',
            SystemSetting::GROUP_EMAIL => 'Email Settings',
            SystemSetting::GROUP_SMS => 'SMS Settings',
            SystemSetting::GROUP_CURRENCY => 'Currency Settings',
            SystemSetting::GROUP_TAX => 'Tax Settings',
            SystemSetting::GROUP_NOTIFICATION => 'Notification Settings',
            SystemSetting::GROUP_SECURITY => 'Security Settings',
        ];

        $summary = [];
        foreach ($groups as $key => $label) {
            $count = SystemSetting::group($key)->count();
            $lastUpdated = SystemSetting::group($key)->max('updated_at');
            $summary[$key] = [
                'label' => $label,
                'count' => $count,
                'last_updated' => $lastUpdated,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get settings for a specific group.
     */
    public function getGroup(string $group): JsonResponse
    {
        $validGroups = [
            'store', 'payment', 'shipping', 'email', 'sms',
            'currency', 'tax', 'notification', 'security', 'integration',
        ];

        if (!in_array($group, $validGroups)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings group',
            ], 400);
        }

        $settings = SystemSetting::getGroup($group);

        return response()->json([
            'success' => true,
            'data' => [
                'group' => $group,
                'settings' => $settings,
            ],
        ]);
    }

    /**
     * Update settings for a specific group.
     */
    public function updateGroup(Request $request, string $group): JsonResponse
    {
        $validGroups = [
            'store', 'payment', 'shipping', 'email', 'sms',
            'currency', 'tax', 'notification', 'security', 'integration',
        ];

        if (!in_array($group, $validGroups)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings group',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = SystemSetting::updateGroup($group, $request->settings, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => [
                'group' => $group,
                'results' => $results,
            ],
        ]);
    }

    // ============ STORE SETTINGS ============

    /**
     * Get store settings.
     */
    public function storeSettings(): JsonResponse
    {
        return $this->getGroup('store');
    }

    /**
     * Update store settings.
     */
    public function updateStoreSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|array',
            'logo_url' => 'nullable|url|max:500',
            'favicon_url' => 'nullable|url|max:500',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'is_maintenance_mode' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'name', 'email', 'phone', 'address', 'logo_url',
            'favicon_url', 'timezone', 'date_format', 'is_maintenance_mode',
        ]);

        $results = SystemSetting::updateGroup('store', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Store settings updated',
            'data' => $results,
        ]);
    }

    // ============ PAYMENT SETTINGS ============

    /**
     * Get payment settings.
     */
    public function paymentSettings(): JsonResponse
    {
        return $this->getGroup('payment');
    }

    /**
     * Update payment settings.
     */
    public function updatePaymentSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'razorpay_enabled' => 'nullable|boolean',
            'razorpay_key_id' => 'nullable|string|max:255',
            'razorpay_key_secret' => 'nullable|string|max:255',
            'cod_enabled' => 'nullable|boolean',
            'cod_min_order' => 'nullable|integer|min:0',
            'cod_max_order' => 'nullable|integer|min:0',
            'prepaid_discount_enabled' => 'nullable|boolean',
            'prepaid_discount_percent' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'razorpay_enabled', 'razorpay_key_id', 'razorpay_key_secret',
            'cod_enabled', 'cod_min_order', 'cod_max_order',
            'prepaid_discount_enabled', 'prepaid_discount_percent',
        ]);

        $results = SystemSetting::updateGroup('payment', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Payment settings updated',
            'data' => $results,
        ]);
    }

    // ============ SHIPPING SETTINGS ============

    /**
     * Get shipping settings.
     */
    public function shippingSettings(): JsonResponse
    {
        return $this->getGroup('shipping');
    }

    /**
     * Update shipping settings.
     */
    public function updateShippingSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shiprocket_enabled' => 'nullable|boolean',
            'shiprocket_email' => 'nullable|email|max:255',
            'shiprocket_password' => 'nullable|string|max:255',
            'free_shipping_threshold' => 'nullable|integer|min:0',
            'default_shipping_charge' => 'nullable|integer|min:0',
            'express_shipping_charge' => 'nullable|integer|min:0',
            'pickup_location' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'shiprocket_enabled', 'shiprocket_email', 'shiprocket_password',
            'free_shipping_threshold', 'default_shipping_charge',
            'express_shipping_charge', 'pickup_location',
        ]);

        $results = SystemSetting::updateGroup('shipping', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Shipping settings updated',
            'data' => $results,
        ]);
    }

    // ============ EMAIL SETTINGS ============

    /**
     * Get email settings.
     */
    public function emailSettings(): JsonResponse
    {
        return $this->getGroup('email');
    }

    /**
     * Update email settings.
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_address' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'from_address', 'from_name', 'smtp_host', 'smtp_port',
            'smtp_username', 'smtp_password', 'smtp_encryption',
        ]);

        $results = SystemSetting::updateGroup('email', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Email settings updated',
            'data' => $results,
        ]);
    }

    /**
     * Test email configuration.
     */
    public function testEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'to_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // In production, you would send a test email here
        // For now, we just validate the configuration
        $emailSettings = SystemSetting::getGroup('email');

        $required = ['from_address', 'smtp_host', 'smtp_port'];
        $missing = [];
        foreach ($required as $key) {
            if (empty($emailSettings[$key]['value'])) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Missing required email settings: ' . implode(', ', $missing),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test email would be sent to ' . $request->to_email,
        ]);
    }

    // ============ SMS SETTINGS ============

    /**
     * Get SMS settings.
     */
    public function smsSettings(): JsonResponse
    {
        return $this->getGroup('sms');
    }

    /**
     * Update SMS settings.
     */
    public function updateSmsSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'nullable|string|max:50',
            'api_key' => 'nullable|string|max:255',
            'sender_id' => 'nullable|string|max:20',
            'enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only(['provider', 'api_key', 'sender_id', 'enabled']);

        $results = SystemSetting::updateGroup('sms', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'SMS settings updated',
            'data' => $results,
        ]);
    }

    // ============ CURRENCY SETTINGS ============

    /**
     * Get currency settings.
     */
    public function currencySettings(): JsonResponse
    {
        return $this->getGroup('currency');
    }

    /**
     * Update currency settings.
     */
    public function updateCurrencySettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'default_currency' => 'nullable|string|size:3',
            'currency_symbol' => 'nullable|string|max:5',
            'currency_position' => 'nullable|in:before,after',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'thousand_separator' => 'nullable|string|max:1',
            'decimal_separator' => 'nullable|string|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'default_currency', 'currency_symbol', 'currency_position',
            'decimal_places', 'thousand_separator', 'decimal_separator',
        ]);

        $results = SystemSetting::updateGroup('currency', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Currency settings updated',
            'data' => $results,
        ]);
    }

    // ============ TAX SETTINGS ============

    /**
     * Get tax settings.
     */
    public function taxSettings(): JsonResponse
    {
        return $this->getGroup('tax');
    }

    /**
     * Update tax settings.
     */
    public function updateTaxSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'gst_enabled' => 'nullable|boolean',
            'gst_number' => 'nullable|string|max:20',
            'default_gst_rate' => 'nullable|integer|min:0|max:100',
            'prices_include_tax' => 'nullable|boolean',
            'display_tax_in_cart' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'gst_enabled', 'gst_number', 'default_gst_rate',
            'prices_include_tax', 'display_tax_in_cart',
        ]);

        $results = SystemSetting::updateGroup('tax', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Tax settings updated',
            'data' => $results,
        ]);
    }

    // ============ NOTIFICATION SETTINGS ============

    /**
     * Get notification settings.
     */
    public function notificationSettings(): JsonResponse
    {
        return $this->getGroup('notification');
    }

    /**
     * Update notification settings.
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_confirmation_email' => 'nullable|boolean',
            'order_confirmation_sms' => 'nullable|boolean',
            'shipping_update_email' => 'nullable|boolean',
            'shipping_update_sms' => 'nullable|boolean',
            'admin_new_order_email' => 'nullable|boolean',
            'admin_email_addresses' => 'nullable|array',
            'admin_email_addresses.*' => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'order_confirmation_email', 'order_confirmation_sms',
            'shipping_update_email', 'shipping_update_sms',
            'admin_new_order_email', 'admin_email_addresses',
        ]);

        $results = SystemSetting::updateGroup('notification', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'data' => $results,
        ]);
    }

    // ============ SECURITY SETTINGS ============

    /**
     * Get security settings.
     */
    public function securitySettings(): JsonResponse
    {
        return $this->getGroup('security');
    }

    /**
     * Update security settings.
     */
    public function updateSecuritySettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'max_login_attempts' => 'nullable|integer|min:1|max:20',
            'lockout_duration' => 'nullable|integer|min:1|max:1440',
            'password_min_length' => 'nullable|integer|min:6|max:32',
            'require_2fa_admin' => 'nullable|boolean',
            'session_lifetime' => 'nullable|integer|min:5|max:10080',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->only([
            'max_login_attempts', 'lockout_duration', 'password_min_length',
            'require_2fa_admin', 'session_lifetime',
        ]);

        $results = SystemSetting::updateGroup('security', array_filter($settings, fn($v) => $v !== null), auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Security settings updated',
            'data' => $results,
        ]);
    }

    // ============ SETTINGS HISTORY ============

    /**
     * Get settings change history.
     */
    public function history(Request $request): JsonResponse
    {
        $query = SettingsHistory::with('changedByUser:id,name,email');

        if ($request->group) {
            $query->forGroup($request->group);
        }
        if ($request->user_id) {
            $query->byUser($request->user_id);
        }
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $history = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    // ============ SYSTEM ACTIONS ============

    /**
     * Clear all caches.
     */
    public function clearCache(): JsonResponse
    {
        Cache::flush();
        SystemSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully',
        ]);
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        SystemSetting::setValue('store', 'is_maintenance_mode', $request->enabled, auth()->id());

        return response()->json([
            'success' => true,
            'message' => $request->enabled ? 'Maintenance mode enabled' : 'Maintenance mode disabled',
        ]);
    }

    /**
     * Seed default settings.
     */
    public function seedDefaults(): JsonResponse
    {
        SystemSetting::seedDefaults();

        return response()->json([
            'success' => true,
            'message' => 'Default settings seeded successfully',
        ]);
    }

    /**
     * Export all settings.
     */
    public function export(): JsonResponse
    {
        $groups = [
            'store', 'payment', 'shipping', 'email', 'sms',
            'currency', 'tax', 'notification', 'security',
        ];

        $export = [];
        foreach ($groups as $group) {
            $export[$group] = SystemSetting::getGroup($group);
        }

        return response()->json([
            'success' => true,
            'data' => $export,
        ]);
    }

    /**
     * Import settings.
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $imported = 0;
        $failed = 0;

        foreach ($request->settings as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $data) {
                    try {
                        $value = is_array($data) ? ($data['value'] ?? null) : $data;
                        if ($value !== null) {
                            SystemSetting::setValue($group, $key, $value, auth()->id());
                            $imported++;
                        }
                    } catch (\Exception $e) {
                        $failed++;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Imported {$imported} settings, {$failed} failed",
            'data' => [
                'imported' => $imported,
                'failed' => $failed,
            ],
        ]);
    }
}
