<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Login Attempt Model
 *
 * Tracks all login attempts for security monitoring.
 */
class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'phone',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'user_id',
        'user_type',
    ];

    protected $casts = [
        'successful' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a login attempt.
     */
    public static function log(
        ?string $email,
        ?string $phone,
        string $ipAddress,
        ?string $userAgent,
        bool $successful,
        ?string $failureReason = null,
        ?int $userId = null,
        ?string $userType = null
    ): self {
        return self::create([
            'email' => $email,
            'phone' => $phone,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => $successful,
            'failure_reason' => $failureReason,
            'user_id' => $userId,
            'user_type' => $userType,
        ]);
    }

    /**
     * Get failed attempts count for email/IP.
     */
    public static function getFailedAttempts(string $identifier, int $minutes = 30): int
    {
        return self::where(function ($query) use ($identifier) {
            $query->where('email', $identifier)
                ->orWhere('ip_address', $identifier);
        })
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if locked out.
     */
    public static function isLockedOut(string $identifier, int $maxAttempts = 5, int $minutes = 30): bool
    {
        return self::getFailedAttempts($identifier, $minutes) >= $maxAttempts;
    }

    /**
     * Scopes
     */
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
}
