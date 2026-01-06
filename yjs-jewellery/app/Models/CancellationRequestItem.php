<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cancellation Request Item Model
 *
 * Individual items within a partial cancellation request.
 *
 * @property int $id
 * @property int $cancellation_request_id
 * @property int $order_item_id
 * @property int $product_id
 * @property int $quantity
 * @property float|null $item_amount
 * @property float|null $refund_amount
 * @property string $item_status
 */
class CancellationRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_request_id',
        'order_item_id',
        'product_id',
        'quantity',
        'item_amount',
        'refund_amount',
        'item_status',
    ];

    protected $casts = [
        'item_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the cancellation request
     */
    public function cancellationRequest(): BelongsTo
    {
        return $this->belongsTo(CancellationRequest::class);
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
     * Mark as refunded
     */
    public function markRefunded(): bool
    {
        $this->item_status = self::STATUS_REFUNDED;
        return $this->save();
    }

    /**
     * Get product name
     */
    public function getProductNameAttribute(): string
    {
        return $this->product->name ?? 'Unknown Product';
    }
}
