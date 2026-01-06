<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Return Request Model
 *
 * Handles customer return requests for orders.
 *
 * @property int $id
 * @property string $return_code
 * @property int $order_id
 * @property int $user_id
 * @property string $status
 * @property string $return_type
 * @property string|null $reason_code
 * @property string|null $reason_description
 * @property string|null $customer_notes
 * @property array|null $images
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \DateTime|null $reviewed_at
 * @property string|null $pickup_address
 * @property \DateTime|null $pickup_scheduled_at
 * @property \DateTime|null $picked_up_at
 * @property string|null $pickup_tracking_number
 * @property string|null $pickup_courier
 * @property string|null $inspection_result
 * @property string|null $inspection_notes
 * @property \DateTime|null $inspected_at
 * @property float|null $refund_amount
 * @property float $restocking_fee
 * @property float $shipping_deduction
 * @property float|null $final_refund_amount
 * @property string|null $refund_method
 * @property string|null $refund_reference
 * @property \DateTime|null $refund_initiated_at
 * @property \DateTime|null $refund_completed_at
 */
class ReturnRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'return_code',
        'order_id',
        'user_id',
        'status',
        'return_type',
        'reason_code',
        'reason_description',
        'customer_notes',
        'images',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'pickup_address',
        'pickup_scheduled_at',
        'picked_up_at',
        'pickup_tracking_number',
        'pickup_courier',
        'inspection_result',
        'inspection_notes',
        'inspected_at',
        'refund_amount',
        'restocking_fee',
        'shipping_deduction',
        'final_refund_amount',
        'refund_method',
        'refund_reference',
        'refund_initiated_at',
        'refund_completed_at',
    ];

    protected $casts = [
        'images' => 'array',
        'reviewed_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'inspected_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'restocking_fee' => 'decimal:2',
        'shipping_deduction' => 'decimal:2',
        'final_refund_amount' => 'decimal:2',
        'refund_initiated_at' => 'datetime',
        'refund_completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PICKUP_SCHEDULED = 'pickup_scheduled';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSPECTED = 'inspected';
    const STATUS_REFUND_INITIATED = 'refund_initiated';
    const STATUS_REFUND_COMPLETED = 'refund_completed';
    const STATUS_CLOSED = 'closed';

    const TYPE_REFUND = 'refund';
    const TYPE_STORE_CREDIT = 'store_credit';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->return_code)) {
                $model->return_code = self::generateReturnCode();
            }
        });
    }

    /**
     * Generate unique return code
     */
    public static function generateReturnCode(): string
    {
        do {
            $code = 'RET-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('return_code', $code)->exists());

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
     * Get return items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    /**
     * Get status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id')
            ->where('request_type', 'return')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get exchange request if converted
     */
    public function exchangeRequest(): HasOne
    {
        return $this->hasOne(ExchangeRequest::class);
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
                'request_type' => 'return',
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
     * Calculate total refund amount
     */
    public function calculateRefundAmount(): float
    {
        $itemsTotal = $this->items->sum('refund_amount') ?? 0;
        $restockingFee = $this->restocking_fee ?? 0;
        $shippingDeduction = $this->shipping_deduction ?? 0;

        return max(0, $itemsTotal - $restockingFee - $shippingDeduction);
    }

    /**
     * Approve the return request
     */
    public function approve(int $reviewerId, ?string $notes = null): bool
    {
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->admin_notes = $notes;

        return $this->updateStatus(self::STATUS_APPROVED, $notes, $reviewerId);
    }

    /**
     * Reject the return request
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
     * Schedule pickup
     */
    public function schedulePickup(string $address, \DateTime $scheduledAt, ?string $courier = null): bool
    {
        $this->pickup_address = $address;
        $this->pickup_scheduled_at = $scheduledAt;
        $this->pickup_courier = $courier;
        $this->save();

        return $this->updateStatus(self::STATUS_PICKUP_SCHEDULED, 'Pickup scheduled');
    }

    /**
     * Mark as picked up
     */
    public function markPickedUp(?string $trackingNumber = null): bool
    {
        $this->picked_up_at = now();
        $this->pickup_tracking_number = $trackingNumber;
        $this->save();

        return $this->updateStatus(self::STATUS_PICKED_UP, 'Items picked up');
    }

    /**
     * Mark as received
     */
    public function markReceived(?string $notes = null): bool
    {
        return $this->updateStatus(self::STATUS_RECEIVED, $notes ?? 'Items received at warehouse');
    }

    /**
     * Record inspection result
     */
    public function recordInspection(string $result, ?string $notes = null): bool
    {
        $this->inspection_result = $result;
        $this->inspection_notes = $notes;
        $this->inspected_at = now();
        $this->save();

        return $this->updateStatus(self::STATUS_INSPECTED, "Inspection: {$result}");
    }

    /**
     * Initiate refund
     */
    public function initiateRefund(float $amount, string $method, ?string $reference = null): bool
    {
        $this->final_refund_amount = $amount;
        $this->refund_method = $method;
        $this->refund_reference = $reference;
        $this->refund_initiated_at = now();
        $this->save();

        return $this->updateStatus(self::STATUS_REFUND_INITIATED, "Refund of {$amount} initiated");
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

        return $this->updateStatus(self::STATUS_REFUND_COMPLETED, 'Refund completed');
    }

    /**
     * Check if return can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Check if return is in final state
     */
    public function isClosed(): bool
    {
        return in_array($this->status, [
            self::STATUS_REJECTED,
            self::STATUS_REFUND_COMPLETED,
            self::STATUS_CLOSED,
        ]);
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
            self::STATUS_PICKUP_SCHEDULED => 'Pickup Scheduled',
            self::STATUS_PICKED_UP => 'Picked Up',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_INSPECTED => 'Inspected',
            self::STATUS_REFUND_INITIATED => 'Refund Initiated',
            self::STATUS_REFUND_COMPLETED => 'Refund Completed',
            self::STATUS_CLOSED => 'Closed',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Scope for pending returns
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for returns needing action
     */
    public function scopeNeedsAction($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_RECEIVED,
        ]);
    }

    /**
     * Scope for user's returns
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
