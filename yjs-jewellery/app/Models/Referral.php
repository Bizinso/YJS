<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referral Model
 *
 * Tracks referral relationships and rewards.
 */
class Referral extends Model
{
    protected $table = 'referrals';

    protected $fillable = [
        'referrer_id',
        'referee_id',
        'referral_code',
        'status',
        'referrer_reward',
        'referee_discount',
        'referrer_rewarded',
        'referee_used_discount',
        'referee_order_id',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'referrer_reward' => 'decimal:2',
        'referee_discount' => 'decimal:2',
        'referrer_rewarded' => 'boolean',
        'referee_used_discount' => 'boolean',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function refereeOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'referee_order_id');
    }

    /**
     * Complete the referral
     */
    public function complete(int $orderId): void
    {
        $this->status = 'completed';
        $this->referee_order_id = $orderId;
        $this->referee_used_discount = true;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark referrer as rewarded
     */
    public function rewardReferrer(): void
    {
        $this->referrer_rewarded = true;
        $this->save();
    }

    /**
     * Scope for pending referrals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for completed referrals
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for active (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
