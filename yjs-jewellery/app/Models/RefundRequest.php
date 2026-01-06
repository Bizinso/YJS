<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Refund Request Model
 *
 * Manages refund workflow with approval process.
 */
class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_code',
        'order_id',
        'user_id',
        'refund_type',
        'original_amount',
        'refund_amount',
        'deductions',
        'deduction_reason',
        'status',
        'source',
        'source_id',
        'source_type',
        'refund_method',
        'reason_code',
        'reason_description',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'payment_gateway',
        'gateway_refund_id',
        'gateway_status',
        'initiated_at',
        'completed_at',
        'gateway_response',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'deductions' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'gateway_response' => 'array',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Refund Types
    const TYPE_FULL = 'full';
    const TYPE_PARTIAL = 'partial';

    // Sources
    const SOURCE_RETURN = 'return';
    const SOURCE_CANCELLATION = 'cancellation';
    const SOURCE_COMPLAINT = 'complaint';
    const SOURCE_MANUAL = 'manual';

    // Refund Methods
    const METHOD_ORIGINAL_PAYMENT = 'original_payment';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_STORE_CREDIT = 'store_credit';

    /**
     * Boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->refund_code)) {
                $refund->refund_code = 'REF-' . strtoupper(Str::random(10));
            }
        });
    }

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RefundStatusHistory::class, 'refund_id')->orderBy('created_at', 'desc');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class, 'refund_id');
    }

    /**
     * Get the source model (return request, cancellation request, etc.)
     */
    public function source()
    {
        if (!$this->source_type || !$this->source_id) return null;

        return match ($this->source_type) {
            'return_request' => ReturnRequest::find($this->source_id),
            'cancellation_request' => CancellationRequest::find($this->source_id),
            default => null,
        };
    }

    /**
     * Update refund status with history.
     */
    public function updateStatus(string $newStatus, ?string $notes = null, ?int $changedBy = null): self
    {
        $oldStatus = $this->status;

        RefundStatusHistory::create([
            'refund_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $changedBy ?? auth()->id(),
        ]);

        $this->status = $newStatus;
        $this->save();

        return $this;
    }

    /**
     * Approve the refund.
     */
    public function approve(?int $approvedBy = null, ?string $notes = null): self
    {
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->updateStatus(self::STATUS_APPROVED, $notes ?? 'Refund approved', $approvedBy);

        return $this;
    }

    /**
     * Reject the refund.
     */
    public function reject(string $reason, ?int $rejectedBy = null): self
    {
        $this->rejection_reason = $reason;
        $this->reviewed_by = $rejectedBy ?? auth()->id();
        $this->reviewed_at = now();
        $this->updateStatus(self::STATUS_REJECTED, $reason, $rejectedBy);

        return $this;
    }

    /**
     * Start review process.
     */
    public function startReview(?int $reviewedBy = null): self
    {
        $this->reviewed_by = $reviewedBy ?? auth()->id();
        $this->reviewed_at = now();
        $this->updateStatus(self::STATUS_UNDER_REVIEW, 'Review started', $reviewedBy);

        return $this;
    }

    /**
     * Mark refund as processing.
     */
    public function markProcessing(): self
    {
        $this->initiated_at = now();
        $this->updateStatus(self::STATUS_PROCESSING, 'Refund initiated');

        return $this;
    }

    /**
     * Mark refund as completed.
     */
    public function markCompleted(?string $gatewayRefundId = null, ?array $gatewayResponse = null): self
    {
        $this->completed_at = now();
        $this->gateway_refund_id = $gatewayRefundId;
        $this->gateway_response = $gatewayResponse;
        $this->gateway_status = 'success';
        $this->updateStatus(self::STATUS_COMPLETED, 'Refund completed');

        return $this;
    }

    /**
     * Mark refund as failed.
     */
    public function markFailed(?string $reason = null, ?array $gatewayResponse = null): self
    {
        $this->gateway_status = 'failed';
        $this->gateway_response = $gatewayResponse;
        $this->updateStatus(self::STATUS_FAILED, $reason ?? 'Refund failed');

        return $this;
    }

    /**
     * Check if refund can be approved.
     */
    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if refund can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Get net refund amount after deductions.
     */
    public function getNetRefundAmountAttribute(): float
    {
        return (float) $this->refund_amount - (float) $this->deductions;
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
