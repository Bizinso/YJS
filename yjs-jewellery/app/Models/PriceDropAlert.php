<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Price Drop Alert Model
 *
 * Tracks user price drop notifications for wishlist items.
 */
class PriceDropAlert extends Model
{
    protected $table = 'price_drop_alerts';

    protected $fillable = [
        'user_id',
        'product_id',
        'original_price',
        'target_price',
        'alert_percent',
        'is_active',
        'last_notified_at',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'target_price' => 'decimal:2',
        'alert_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'last_notified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if price drop triggers notification
     */
    public function shouldNotify(float $currentPrice): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check target price
        if ($this->target_price && $currentPrice <= $this->target_price) {
            return true;
        }

        // Check percent drop
        if ($this->alert_percent) {
            $dropPercent = (($this->original_price - $currentPrice) / $this->original_price) * 100;
            return $dropPercent >= $this->alert_percent;
        }

        return false;
    }

    /**
     * Mark as notified
     */
    public function markNotified(): void
    {
        $this->last_notified_at = now();
        $this->save();
    }

    /**
     * Scope for active alerts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for alerts not notified today
     */
    public function scopeNotNotifiedToday($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_notified_at')
              ->orWhereDate('last_notified_at', '<', today());
        });
    }
}
