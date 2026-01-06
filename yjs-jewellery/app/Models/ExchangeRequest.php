<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exchange Request Model
 *
 * Handles customer exchange requests for orders.
 *
 * @property int $id
 * @property string $exchange_code
 * @property int $order_id
 * @property int $user_id
 * @property int|null $return_request_id
 * @property string $status
 * @property string|null $reason_code
 * @property string|null $reason_description
 * @property string|null $customer_notes
 * @property array|null $images
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \DateTime|null $reviewed_at
 * @property float|null $original_amount
 * @property float|null $new_amount
 * @property float $price_difference
 * @property string $adjustment_type
 * @property bool $adjustment_paid
 * @property int|null $new_order_id
 * @property int|null $shipping_address_id
 * @property string|null $tracking_number
 * @property string|null $courier_name
 * @property \DateTime|null $shipped_at
 * @property \DateTime|null $delivered_at
 */
class ExchangeRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exchange_code',
        'order_id',
        'user_id',
        'return_request_id',
        'status',
        'reason_code',
        'reason_description',
        'customer_notes',
        'images',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'original_amount',
        'new_amount',
        'price_difference',
        'adjustment_type',
        'adjustment_paid',
        'new_order_id',
        'shipping_address_id',
        'tracking_number',
        'courier_name',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'images' => 'array',
        'reviewed_at' => 'datetime',
        'original_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'adjustment_paid' => 'boolean',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_AWAITING_RETURN = 'awaiting_return';
    const STATUS_RETURN_RECEIVED = 'return_received';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CLOSED = 'closed';

    const ADJUSTMENT_NONE = 'none';
    const ADJUSTMENT_PAY_EXTRA = 'pay_extra';
    const ADJUSTMENT_REFUND_DIFFERENCE = 'refund_difference';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->exchange_code)) {
                $model->exchange_code = self::generateExchangeCode();
            }
        });
    }

    /**
     * Generate unique exchange code
     */
    public static function generateExchangeCode(): string
    {
        do {
            $code = 'EXC-' . strtoupper(date('Ymd')) . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('exchange_code', $code)->exists());

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
     * Get the return request if linked
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * Get the new order if created
     */
    public function newOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'new_order_id');
    }

    /**
     * Get shipping address
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    /**
     * Get exchange items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ExchangeRequestItem::class);
    }

    /**
     * Get status history
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id')
            ->where('request_type', 'exchange')
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
                'request_type' => 'exchange',
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
     * Calculate price difference
     */
    public function calculatePriceDifference(): void
    {
        $originalTotal = $this->items->sum(function ($item) {
            return $item->orderItem->price * $item->original_quantity;
        });

        $newTotal = $this->items->sum(function ($item) {
            if ($item->new_product_id && $item->new_quantity) {
                $product = Product::find($item->new_product_id);
                return ($product->final_price ?? $product->base_price ?? 0) * $item->new_quantity;
            }
            return 0;
        });

        $this->original_amount = $originalTotal;
        $this->new_amount = $newTotal;
        $this->price_difference = $newTotal - $originalTotal;

        if ($this->price_difference > 0) {
            $this->adjustment_type = self::ADJUSTMENT_PAY_EXTRA;
        } elseif ($this->price_difference < 0) {
            $this->adjustment_type = self::ADJUSTMENT_REFUND_DIFFERENCE;
            $this->price_difference = abs($this->price_difference);
        } else {
            $this->adjustment_type = self::ADJUSTMENT_NONE;
        }

        $this->save();
    }

    /**
     * Approve the exchange request
     */
    public function approve(int $reviewerId, ?string $notes = null): bool
    {
        $this->reviewed_by = $reviewerId;
        $this->reviewed_at = now();
        $this->admin_notes = $notes;
        $this->save();

        return $this->updateStatus(self::STATUS_APPROVED, $notes, $reviewerId);
    }

    /**
     * Reject the exchange request
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
     * Mark as shipped
     */
    public function markShipped(string $trackingNumber, string $courier): bool
    {
        $this->tracking_number = $trackingNumber;
        $this->courier_name = $courier;
        $this->shipped_at = now();
        $this->save();

        return $this->updateStatus(self::STATUS_SHIPPED, "Shipped via {$courier}");
    }

    /**
     * Mark as delivered
     */
    public function markDelivered(): bool
    {
        $this->delivered_at = now();
        $this->save();

        return $this->updateStatus(self::STATUS_DELIVERED, 'Exchange items delivered');
    }

    /**
     * Check if exchange can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
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
            self::STATUS_AWAITING_RETURN => 'Awaiting Return',
            self::STATUS_RETURN_RECEIVED => 'Return Received',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CLOSED => 'Closed',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Scope for pending exchanges
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for user's exchanges
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
