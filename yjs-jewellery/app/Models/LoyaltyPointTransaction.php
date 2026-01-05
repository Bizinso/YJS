<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Loyalty Point Transaction Model
 *
 * Records all loyalty point earn/redeem/expire transactions.
 */
class LoyaltyPointTransaction extends Model
{
    protected $table = 'loyalty_point_transactions';

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope for earned points
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earn');
    }

    /**
     * Scope for redeemed points
     */
    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeem');
    }

    /**
     * Scope for expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }
}
