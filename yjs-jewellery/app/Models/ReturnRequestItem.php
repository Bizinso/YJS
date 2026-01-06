<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Return Request Item Model
 *
 * Individual items within a return request.
 *
 * @property int $id
 * @property int $return_request_id
 * @property int $order_item_id
 * @property int $product_id
 * @property int $quantity
 * @property string|null $reason_code
 * @property string|null $reason_description
 * @property array|null $images
 * @property string|null $condition
 * @property string $item_status
 * @property string|null $inspection_notes
 * @property float|null $refund_amount
 */
class ReturnRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_request_id',
        'order_item_id',
        'product_id',
        'quantity',
        'reason_code',
        'reason_description',
        'images',
        'condition',
        'item_status',
        'inspection_notes',
        'refund_amount',
    ];

    protected $casts = [
        'images' => 'array',
        'refund_amount' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RECEIVED = 'received';
    const STATUS_INSPECTED = 'inspected';

    const CONDITION_UNOPENED = 'unopened';
    const CONDITION_OPENED = 'opened';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_DEFECTIVE = 'defective';
    const CONDITION_WRONG_ITEM = 'wrong_item';

    /**
     * Get the return request
     */
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    /**
     * Get the order item
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_item_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate refund amount for this item
     */
    public function calculateRefundAmount(): float
    {
        if ($this->orderItem) {
            $unitPrice = $this->orderItem->price ?? 0;
            return round($unitPrice * $this->quantity, 2);
        }
        return 0;
    }

    /**
     * Approve the item
     */
    public function approve(): bool
    {
        $this->item_status = self::STATUS_APPROVED;
        $this->refund_amount = $this->calculateRefundAmount();
        return $this->save();
    }

    /**
     * Reject the item
     */
    public function reject(): bool
    {
        $this->item_status = self::STATUS_REJECTED;
        $this->refund_amount = 0;
        return $this->save();
    }

    /**
     * Mark as received
     */
    public function markReceived(): bool
    {
        $this->item_status = self::STATUS_RECEIVED;
        return $this->save();
    }

    /**
     * Record inspection
     */
    public function inspect(string $condition, ?string $notes = null, bool $approve = true): bool
    {
        $this->condition = $condition;
        $this->inspection_notes = $notes;
        $this->item_status = self::STATUS_INSPECTED;

        if ($approve) {
            $this->refund_amount = $this->calculateRefundAmount();
        } else {
            $this->refund_amount = 0;
        }

        return $this->save();
    }

    /**
     * Get condition label
     */
    public function getConditionLabelAttribute(): string
    {
        $labels = [
            self::CONDITION_UNOPENED => 'Unopened/Sealed',
            self::CONDITION_OPENED => 'Opened',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_DEFECTIVE => 'Defective',
            self::CONDITION_WRONG_ITEM => 'Wrong Item',
        ];

        return $labels[$this->condition] ?? $this->condition ?? 'Unknown';
    }
}
