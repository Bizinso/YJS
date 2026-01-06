<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Override Model
 *
 * Tracks manual overrides applied to orders.
 */
class OrderOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'override_type',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'requires_approval',
        'approval_status',
        'approved_by',
        'approved_at',
        'overridden_by',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Override Types
    const TYPE_STATUS = 'status_override';
    const TYPE_PRICE = 'price_override';
    const TYPE_SHIPPING = 'shipping_override';
    const TYPE_DISCOUNT = 'discount_override';
    const TYPE_TAX = 'tax_override';
    const TYPE_ADDRESS = 'address_override';
    const TYPE_PAYMENT = 'payment_override';
    const TYPE_OTHER = 'other';

    // Approval Statuses
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function overriddenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * Approve the override.
     */
    public function approve(?int $approvedBy = null): self
    {
        $this->approval_status = self::APPROVAL_APPROVED;
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        // Apply the override to the order
        $this->applyOverride();

        return $this;
    }

    /**
     * Reject the override.
     */
    public function reject(?int $rejectedBy = null): self
    {
        $this->approval_status = self::APPROVAL_REJECTED;
        $this->approved_by = $rejectedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    /**
     * Apply the override to the order.
     */
    protected function applyOverride(): void
    {
        if (!$this->field_name) return;

        $order = $this->order;
        $order->{$this->field_name} = $this->new_value;
        $order->save();

        // Log to timeline
        OrderTimeline::logEvent(
            $this->order_id,
            OrderTimeline::TYPE_OVERRIDE_APPLIED,
            "Manual override applied: {$this->override_type}",
            $this->reason,
            ['override_id' => $this->id, 'field' => $this->field_name],
            $this->old_value,
            $this->new_value,
            $this->approved_by
        );
    }

    /**
     * Check if pending approval.
     */
    public function isPending(): bool
    {
        return $this->requires_approval && $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check if approved.
     */
    public function isApproved(): bool
    {
        return !$this->requires_approval || $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Scopes
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopePending($query)
    {
        return $query->where('requires_approval', true)
            ->where('approval_status', self::APPROVAL_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where(function ($q) {
            $q->where('requires_approval', false)
                ->orWhere('approval_status', self::APPROVAL_APPROVED);
        });
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('override_type', $type);
    }
}
