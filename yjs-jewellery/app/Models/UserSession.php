<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Session Model
 *
 * Tracks active user sessions for security management.
 */
class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location',
        'last_activity_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a session from request.
     */
    public static function createFromRequest(int $userId, string $tokenId, $request): self
    {
        $userAgent = $request->userAgent();
        $parsed = self::parseUserAgent($userAgent);

        return self::create([
            'user_id' => $userId,
            'token_id' => $tokenId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $parsed['device'],
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Parse user agent string.
     */
    protected static function parseUserAgent(?string $userAgent): array
    {
        $device = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        if (!$userAgent) {
            return compact('device', 'browser', 'platform');
        }

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            $device = preg_match('/ipad|tablet/i', $userAgent) ? 'tablet' : 'mobile';
        }

        // Detect browser
        if (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $m)) {
            $browser = 'Chrome ' . explode('.', $m[1])[0];
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $m)) {
            $browser = 'Firefox ' . explode('.', $m[1])[0];
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/i', $userAgent, $m)) {
            $browser = 'Edge ' . explode('.', $m[1])[0];
        }

        // Detect platform
        if (preg_match('/Windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac OS/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/iPhone|iPad/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $platform = 'Android';
        }

        return compact('device', 'browser', 'platform');
    }

    /**
     * Update last activity.
     */
    public function touch(): bool
    {
        $this->last_activity_at = now();
        return $this->save();
    }

    /**
     * Revoke the session.
     */
    public function revoke(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeInactive($query, int $minutes = 30)
    {
        return $query->where('last_activity_at', '<', now()->subMinutes($minutes));
    }
}
