<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * System Setting Model
 *
 * Unified settings management with caching and encryption support.
 */
class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_sensitive',
        'updated_by',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    protected $hidden = [
        'value', // Hide by default, use accessor
    ];

    // Setting Groups
    const GROUP_STORE = 'store';
    const GROUP_PAYMENT = 'payment';
    const GROUP_SHIPPING = 'shipping';
    const GROUP_EMAIL = 'email';
    const GROUP_SMS = 'sms';
    const GROUP_CURRENCY = 'currency';
    const GROUP_TAX = 'tax';
    const GROUP_NOTIFICATION = 'notification';
    const GROUP_SECURITY = 'security';
    const GROUP_INTEGRATION = 'integration';

    // Value Types
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_ENCRYPTED = 'encrypted';

    // Cache key prefix
    const CACHE_PREFIX = 'settings:';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Relationships
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(SettingsHistory::class, 'setting_id');
    }

    /**
     * Get the typed value.
     */
    public function getTypedValueAttribute()
    {
        $value = $this->attributes['value'] ?? null;

        if ($value === null) {
            return null;
        }

        // Decrypt if sensitive
        if ($this->is_sensitive && $this->type === self::TYPE_ENCRYPTED) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }

        return match ($this->type) {
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get raw value (for admin display).
     */
    public function getRawValueAttribute(): ?string
    {
        $value = $this->attributes['value'] ?? null;

        // Mask sensitive values
        if ($this->is_sensitive && $value) {
            return '********';
        }

        return $value;
    }

    /**
     * Set the value with proper encoding.
     */
    public function setTypedValue($value): self
    {
        $encodedValue = match ($this->type) {
            self::TYPE_INTEGER => (string) $value,
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            self::TYPE_JSON => json_encode($value),
            self::TYPE_ENCRYPTED => Crypt::encryptString($value),
            default => $value,
        };

        $this->value = $encodedValue;
        return $this;
    }

    /**
     * Get a setting value by group and key.
     */
    public static function getValue(string $group, string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . "{$group}:{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group, $key, $default) {
            $setting = self::where('group', $group)->where('key', $key)->first();
            return $setting ? $setting->typed_value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function setValue(string $group, string $key, $value, ?int $userId = null): self
    {
        $setting = self::where('group', $group)->where('key', $key)->first();

        if (!$setting) {
            throw new \Exception("Setting {$group}.{$key} not found");
        }

        $oldValue = $setting->attributes['value'];

        $setting->setTypedValue($value);
        $setting->updated_by = $userId ?? auth()->id();
        $setting->save();

        // Log history
        SettingsHistory::create([
            'setting_id' => $setting->id,
            'group' => $group,
            'key' => $key,
            'old_value' => $setting->is_sensitive ? '********' : $oldValue,
            'new_value' => $setting->is_sensitive ? '********' : $setting->attributes['value'],
            'changed_by' => $userId ?? auth()->id(),
        ]);

        // Clear cache
        Cache::forget(self::CACHE_PREFIX . "{$group}:{$key}");

        return $setting;
    }

    /**
     * Get all settings for a group.
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = [
                'value' => $setting->typed_value,
                'raw_value' => $setting->raw_value,
                'type' => $setting->type,
                'description' => $setting->description,
                'is_sensitive' => $setting->is_sensitive,
            ];
        }

        return $result;
    }

    /**
     * Update multiple settings in a group.
     */
    public static function updateGroup(string $group, array $values, ?int $userId = null): array
    {
        $updated = [];

        foreach ($values as $key => $value) {
            try {
                $setting = self::setValue($group, $key, $value, $userId);
                $updated[$key] = true;
            } catch (\Exception $e) {
                $updated[$key] = false;
            }
        }

        return $updated;
    }

    /**
     * Create or update a setting.
     */
    public static function set(
        string $group,
        string $key,
        $value,
        string $type = self::TYPE_STRING,
        ?string $description = null,
        bool $isSensitive = false
    ): self {
        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'type' => $type,
                'description' => $description,
                'is_sensitive' => $isSensitive,
                'updated_by' => auth()->id(),
            ]
        );

        $setting->setTypedValue($value);
        $setting->save();

        Cache::forget(self::CACHE_PREFIX . "{$group}:{$key}");

        return $setting;
    }

    /**
     * Get default settings configuration.
     */
    public static function getDefaults(): array
    {
        return [
            self::GROUP_STORE => [
                ['key' => 'name', 'value' => 'YJS Jewellery', 'type' => self::TYPE_STRING, 'description' => 'Store name'],
                ['key' => 'email', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'Store contact email'],
                ['key' => 'phone', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'Store contact phone'],
                ['key' => 'address', 'value' => '', 'type' => self::TYPE_JSON, 'description' => 'Store address'],
                ['key' => 'logo_url', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'Store logo URL'],
                ['key' => 'favicon_url', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'Favicon URL'],
                ['key' => 'timezone', 'value' => 'Asia/Kolkata', 'type' => self::TYPE_STRING, 'description' => 'Store timezone'],
                ['key' => 'date_format', 'value' => 'd-m-Y', 'type' => self::TYPE_STRING, 'description' => 'Date format'],
                ['key' => 'is_maintenance_mode', 'value' => '0', 'type' => self::TYPE_BOOLEAN, 'description' => 'Maintenance mode'],
            ],
            self::GROUP_PAYMENT => [
                ['key' => 'razorpay_enabled', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable Razorpay'],
                ['key' => 'razorpay_key_id', 'value' => '', 'type' => self::TYPE_ENCRYPTED, 'description' => 'Razorpay Key ID', 'is_sensitive' => true],
                ['key' => 'razorpay_key_secret', 'value' => '', 'type' => self::TYPE_ENCRYPTED, 'description' => 'Razorpay Key Secret', 'is_sensitive' => true],
                ['key' => 'cod_enabled', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable COD'],
                ['key' => 'cod_min_order', 'value' => '0', 'type' => self::TYPE_INTEGER, 'description' => 'Minimum order for COD'],
                ['key' => 'cod_max_order', 'value' => '50000', 'type' => self::TYPE_INTEGER, 'description' => 'Maximum order for COD'],
                ['key' => 'prepaid_discount_enabled', 'value' => '0', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable prepaid discount'],
                ['key' => 'prepaid_discount_percent', 'value' => '5', 'type' => self::TYPE_INTEGER, 'description' => 'Prepaid discount percentage'],
            ],
            self::GROUP_SHIPPING => [
                ['key' => 'shiprocket_enabled', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable Shiprocket'],
                ['key' => 'shiprocket_email', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'Shiprocket email'],
                ['key' => 'shiprocket_password', 'value' => '', 'type' => self::TYPE_ENCRYPTED, 'description' => 'Shiprocket password', 'is_sensitive' => true],
                ['key' => 'free_shipping_threshold', 'value' => '5000', 'type' => self::TYPE_INTEGER, 'description' => 'Free shipping threshold'],
                ['key' => 'default_shipping_charge', 'value' => '100', 'type' => self::TYPE_INTEGER, 'description' => 'Default shipping charge'],
                ['key' => 'express_shipping_charge', 'value' => '250', 'type' => self::TYPE_INTEGER, 'description' => 'Express shipping charge'],
                ['key' => 'pickup_location', 'value' => '', 'type' => self::TYPE_JSON, 'description' => 'Default pickup location'],
            ],
            self::GROUP_EMAIL => [
                ['key' => 'from_address', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'From email address'],
                ['key' => 'from_name', 'value' => 'YJS Jewellery', 'type' => self::TYPE_STRING, 'description' => 'From name'],
                ['key' => 'smtp_host', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'SMTP host'],
                ['key' => 'smtp_port', 'value' => '587', 'type' => self::TYPE_INTEGER, 'description' => 'SMTP port'],
                ['key' => 'smtp_username', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'SMTP username'],
                ['key' => 'smtp_password', 'value' => '', 'type' => self::TYPE_ENCRYPTED, 'description' => 'SMTP password', 'is_sensitive' => true],
                ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => self::TYPE_STRING, 'description' => 'SMTP encryption (tls/ssl)'],
            ],
            self::GROUP_SMS => [
                ['key' => 'provider', 'value' => 'msg91', 'type' => self::TYPE_STRING, 'description' => 'SMS provider'],
                ['key' => 'api_key', 'value' => '', 'type' => self::TYPE_ENCRYPTED, 'description' => 'SMS API key', 'is_sensitive' => true],
                ['key' => 'sender_id', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'SMS sender ID'],
                ['key' => 'enabled', 'value' => '0', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable SMS notifications'],
            ],
            self::GROUP_CURRENCY => [
                ['key' => 'default_currency', 'value' => 'INR', 'type' => self::TYPE_STRING, 'description' => 'Default currency code'],
                ['key' => 'currency_symbol', 'value' => '₹', 'type' => self::TYPE_STRING, 'description' => 'Currency symbol'],
                ['key' => 'currency_position', 'value' => 'before', 'type' => self::TYPE_STRING, 'description' => 'Symbol position (before/after)'],
                ['key' => 'decimal_places', 'value' => '2', 'type' => self::TYPE_INTEGER, 'description' => 'Decimal places'],
                ['key' => 'thousand_separator', 'value' => ',', 'type' => self::TYPE_STRING, 'description' => 'Thousand separator'],
                ['key' => 'decimal_separator', 'value' => '.', 'type' => self::TYPE_STRING, 'description' => 'Decimal separator'],
            ],
            self::GROUP_TAX => [
                ['key' => 'gst_enabled', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Enable GST'],
                ['key' => 'gst_number', 'value' => '', 'type' => self::TYPE_STRING, 'description' => 'GST number'],
                ['key' => 'default_gst_rate', 'value' => '3', 'type' => self::TYPE_INTEGER, 'description' => 'Default GST rate for jewellery'],
                ['key' => 'prices_include_tax', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Prices include tax'],
                ['key' => 'display_tax_in_cart', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Display tax in cart'],
            ],
            self::GROUP_NOTIFICATION => [
                ['key' => 'order_confirmation_email', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Send order confirmation email'],
                ['key' => 'order_confirmation_sms', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Send order confirmation SMS'],
                ['key' => 'shipping_update_email', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Send shipping update email'],
                ['key' => 'shipping_update_sms', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Send shipping update SMS'],
                ['key' => 'admin_new_order_email', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'description' => 'Notify admin on new order'],
                ['key' => 'admin_email_addresses', 'value' => '', 'type' => self::TYPE_JSON, 'description' => 'Admin notification emails'],
            ],
            self::GROUP_SECURITY => [
                ['key' => 'max_login_attempts', 'value' => '5', 'type' => self::TYPE_INTEGER, 'description' => 'Max login attempts before lockout'],
                ['key' => 'lockout_duration', 'value' => '30', 'type' => self::TYPE_INTEGER, 'description' => 'Lockout duration in minutes'],
                ['key' => 'password_min_length', 'value' => '8', 'type' => self::TYPE_INTEGER, 'description' => 'Minimum password length'],
                ['key' => 'require_2fa_admin', 'value' => '0', 'type' => self::TYPE_BOOLEAN, 'description' => 'Require 2FA for admins'],
                ['key' => 'session_lifetime', 'value' => '120', 'type' => self::TYPE_INTEGER, 'description' => 'Session lifetime in minutes'],
            ],
        ];
    }

    /**
     * Seed default settings.
     */
    public static function seedDefaults(): void
    {
        $defaults = self::getDefaults();

        foreach ($defaults as $group => $settings) {
            foreach ($settings as $setting) {
                self::firstOrCreate(
                    ['group' => $group, 'key' => $setting['key']],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'description' => $setting['description'] ?? null,
                        'is_sensitive' => $setting['is_sensitive'] ?? false,
                    ]
                );
            }
        }
    }

    /**
     * Clear all settings cache.
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX . "{$setting->group}:{$setting->key}");
        }
    }

    /**
     * Scopes
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }
}
