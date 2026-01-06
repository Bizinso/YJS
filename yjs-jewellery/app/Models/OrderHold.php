<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Hold Model
 *
 * Tracks hold and release actions for orders.
 */
class OrderHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'hold_reason_code',
        'hold_reason',
        'status',
        'hold_until',
        'released_at',
        'release_notes',
        'held_by',
        'released_by',
    ];

    protected $casts = [
        'hold_until' => 'datetime',
        'released_at' => 'datetime',
    ];

    // Statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_RELEASED = 'released';
    const STATUS_EXPIRED = 'expired';

    // Hold Reason Codes
    const REASON_FRAUD_CHECK = 'fraud_check';
    const REASON_PAYMENT_VERIFICATION = 'payment_verification';
    const REASON_STOCK_ISSUE = 'stock_issue';
    const REASON_ADDRESS_VERIFICATION = 'address_verification';
    const REASON_CUSTOMER_REQUEST = 'customer_request';
    const REASON_PRICING_ERROR = 'pricing_error';
    const REASON_COMPLIANCE = 'compliance';
    const REASON_OTHER = 'other';

    /**
     * Get hold reason labels.
     */
    public static function getReasonLabels(): array
    {
        return [
            self::REASON_FRAUD_CHECK => 'Fraud Check Required',
            self::REASON_PAYMENT_VERIFICATION => 'Payment Verification',
            self::REASON_STOCK_ISSUE => 'Stock Issue',
            self::REASON_ADDRESS_VERIFICATION => 'Address Verification',
            self::REASON_CUSTOMER_REQUEST => 'Customer Request',
            self::REASON_PRICING_ERROR => 'Pricing Error',
            self::REASON_COMPLIANCE => 'Compliance Review',
            self::REASON_OTHER => 'Other',
        ];
    }

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function heldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * Release the hold.
     */
    public function release(?string $notes = null, ?int $releasedBy = null): self
    {
        $this->status = self::STATUS_RELEASED;
        $this->released_at = now();
        $this->release_notes = $notes;
        $this->released_by = $releasedBy ?? auth()->id();
        $this->save();

        // Update order
        $this->order->update([
            'is_on_hold' => false,
            'hold_reason_code' => null,
            'hold_since' => null,
        ]);

        // Log to timeline
        OrderTimeline::logEvent(
            $this->order_id,
            OrderTimeline::TYPE_HOLD_RELEASED,
            'Order hold released',
            $notes,
            ['hold_id' => $this->id, 'reason_code' => $this->hold_reason_code],
            'on_hold',
            'released',
            $releasedBy
        );

        return $this;
    }

    /**
     * Check if hold is expired.
     */
    public function isExpired(): bool
    {
        return $this->hold_until && now()->isAfter($this->hold_until);
    }

    /**
     * Get reason label.
     */
    public function getReasonLabelAttribute(): string
    {
        return self::getReasonLabels()[$this->hold_reason_code] ?? ucfirst(str_replace('_', ' ', $this->hold_reason_code));
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeExpiredHolds($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('hold_until')
            ->where('hold_until', '<', now());
    }
}
