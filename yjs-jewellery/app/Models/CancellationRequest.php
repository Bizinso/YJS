<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cancellation Request Model
 *
 * Handles customer cancellation requests for orders.
 *
 * @property int $id
 * @property string $cancellation_code
 * @property int $order_id
 * @property int $user_id
 * @property string $status
 * @property string $cancellation_type
 * @property string|null $reason_code
 * @property string|null $reason_description
 * @property string|null $customer_notes
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \DateTime|null $reviewed_at
 * @property bool $auto_approved
 * @property float|null $order_amount
 * @property float $cancellation_fee
 * @property float|null $refund_amount
 * @property string|null $refund_method
 * @property string|null $refund_reference
 * @property \DateTime|null $refund_initiated_at
 * @property \DateTime|null $refund_completed_at
 */
class CancellationRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cancellation_code',
        'order_id',
        'user_id',
        'status',
        'cancellation_type',
        'reason_code',
        'reason_description',
        'customer_notes',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'auto_approved',
        'order_amount',
        'cancellation_fee',
        'refund_amount',
        'refund_method',
        'refund_reference',
        'refund_initiated_at',
        'refund_completed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'auto_approved' => 'boolean',
        'order_amount' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refund_initiated_at' => 'datetime',
        'refund_completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REFUND_INITIATED = 'refund_initiated';
    const STATUS_REFUND_COMPLETED = 'refund_completed';
    const STATUS_CLOSED = 'closed';

    const TYPE_FULL = 'full';
    const TYPE_PARTIAL = 'partial';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->cancellation_code)) {
                $model->cancellation_code = self::generateCancellationCode();
            }
        });
    }

    /**
     * Generate unique cancellation code
     */
    public static function generateCancellationCode(): string
    {
        do {
            $code = 'CAN-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('cancellation_code', $code)->exists());

        return $code;
    }

    /**
     * Get the order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get cancellation items (for partial cancellations)
     */
    public function items(): HasMany
    {
        return $this->hasMany(CancellationRequestItem::class);
    }

    /**
     * Get status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id')
            ->where('request_type', 'cancellation')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Update status with history tracking
     */
    public function updateStatus(string $newStatus, ?string $notes = null, ?int $changedBy = null): bool
    {
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return true;
        }

        $this->status = $newStatus;
        $saved = $this->save();

        if ($saved) {
            RequestStatusHistory::create([
                'request_type' => 'cancellation',
                'request_id' => $this->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'notes' => $notes,
                'changed_by' => $changedBy,
            ]);
        }

        return $saved;
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefundAmount(): float
    {
        if ($this->cancellation_type === self::TYPE_FULL) {
            return max(0, ($this->order_amount ?? 0) - ($this->cancellation_fee ?? 0));
        }

        // For partial cancellations, sum up item amounts
        $itemsTotal = $this->items->sum('refund_amount') ?? 0;
        return max(0, $itemsTotal - ($this->cancellation_fee ?? 0));
    }

    /**
     * Check if order can be cancelled
     */
    public static function canCancelOrder(Order $order): array
    {
        $canCancel = true;
        $reason = null;

        // Check order status
        $nonCancellableStatuses = ['shipped', 'delivered', 'cancelled', 'refunded'];
        if (in_array($order->status, $nonCancellableStatuses)) {
            $canCancel = false;
            $reason = "Order cannot be cancelled in '{$order->status}' status";
        }

        // Check if already has pending cancellation
        $existingRequest = self::where('order_id', $order->id)
            ->whereNotIn('status', [self::STATUS_REJECTED, self::STATUS_CLOSED])
            ->exists();

        if ($existingRequest) {
            $canCancel = false;
            $reason = 'A cancellation request already exists for this order';
        }

        // Check cancellation window
        $policy = ReturnPolicySetting::getActive();
        if ($policy) {
            $orderDate = $order->created_at;
            if (!$policy->isWithinCancellationWindow($orderDate)) {
                $canCancel = false;
                $reason = "Cancellation window of {$policy->cancellation_window_hours} hours has passed";
            }
        }

        return [
            'can_cancel' => $canCancel,
            'reason' => $reason,
        ];
    }

    /**
     * Auto-approve if eligible
     */
    public function checkAutoApproval(): bool
    {
        $policy = ReturnPolicySetting::getActive();

        if (!$policy || !$policy->auto_approve_cancellations) {
            return false;
        }

        $order = $this->order;

        // Auto-approve if order is still pending/processing
        $autoApproveStatuses = ['pending', 'confirmed', 'processing'];
        if (in_array($order->status, $autoApproveStatuses)) {
            $this->auto_approved = true;
            $this->save();

            return $this->approve(null, 'Auto-approved based on order status');
        }

        return false;
    }

    /**
     * Approve the cancellation request
     */
    public function approve(?int $reviewerId, ?string $notes = null): bool
    {
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->admin_notes = $notes;
        $this->refund_amount = $this->calculateRefundAmount();
        $this->save();

        // Update order status
        $this->order->update(['status' => 'cancelled']);

        return $this->updateStatus(self::STATUS_APPROVED, $notes, $reviewerId);
    }

    /**
     * Reject the cancellation request
     */
    public function reject(int $reviewerId, string $reason, ?string $notes = null): bool
    {
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->rejection_reason = $reason;
        $this->admin_notes = $notes;
        $this->save();

        return $this->updateStatus(self::STATUS_REJECTED, $reason, $reviewerId);
    }

    /**
     * Initiate refund
     */
    public function initiateRefund(string $method, ?string $reference = null): bool
    {
        $this->refund_method = $method;
        $this->refund_reference = $reference;
        $this->refund_initiated_at = now();
        $this->save();

        return $this->updateStatus(self::STATUS_REFUND_INITIATED, "Refund initiated via {$method}");
    }

    /**
     * Complete refund
     */
    public function completeRefund(?string $reference = null): bool
    {
        if ($reference) {
            $this->refund_reference = $reference;
        }
        $this->refund_completed_at = now();
        $this->save();

        // Update order status
        $this->order->update(['status' => 'refunded']);

        return $this->updateStatus(self::STATUS_REFUND_COMPLETED, 'Refund completed');
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REFUND_INITIATED => 'Refund Initiated',
            self::STATUS_REFUND_COMPLETED => 'Refund Completed',
            self::STATUS_CLOSED => 'Closed',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Scope for pending cancellations
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for user's cancellations
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
