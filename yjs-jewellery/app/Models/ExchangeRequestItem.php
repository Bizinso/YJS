<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exchange Request Item Model
 *
 * Individual items within an exchange request.
 *
 * @property int $id
 * @property int $exchange_request_id
 * @property int $original_order_item_id
 * @property int $original_product_id
 * @property int $original_quantity
 * @property int|null $new_product_id
 * @property int|null $new_variant_id
 * @property int|null $new_quantity
 * @property string|null $reason_code
 * @property string|null $reason_description
 * @property string $item_status
 * @property float $price_difference
 */
class ExchangeRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_request_id',
        'original_order_item_id',
        'original_product_id',
        'original_quantity',
        'new_product_id',
        'new_variant_id',
        'new_quantity',
        'reason_code',
        'reason_description',
        'item_status',
        'price_difference',
    ];

    protected $casts = [
        'price_difference' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FULFILLED = 'fulfilled';

    /**
     * Get the exchange request
     */
    public function exchangeRequest(): BelongsTo
    {
        return $this->belongsTo(ExchangeRequest::class);
    }

    /**
     * Get the original order item
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'original_order_item_id');
    }

    /**
     * Get the original product
     */
    public function originalProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'original_product_id');
    }

    /**
     * Get the new product
     */
    public function newProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    /**
     * Get the new variant
     */
    public function newVariant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'new_variant_id');
    }

    /**
     * Set new product for exchange
     */
    public function setNewProduct(int $productId, int $quantity, ?int $variantId = null): bool
    {
        $this->new_product_id = $productId;
        $this->new_quantity = $quantity;
        $this->new_variant_id = $variantId;

        // Calculate price difference
        $originalPrice = $this->orderItem->price ?? 0;
        $originalTotal = $originalPrice * $this->original_quantity;

        $newProduct = Product::find($productId);
        $newPrice = $newProduct->final_price ?? $newProduct->base_price ?? 0;
        $newTotal = $newPrice * $quantity;

        $this->price_difference = $newTotal - $originalTotal;

        return $this->save();
    }

    /**
     * Approve the item
     */
    public function approve(): bool
    {
        $this->item_status = self::STATUS_APPROVED;
        return $this->save();
    }

    /**
     * Reject the item
     */
    public function reject(): bool
    {
        $this->item_status = self::STATUS_REJECTED;
        return $this->save();
    }

    /**
     * Mark as fulfilled
     */
    public function markFulfilled(): bool
    {
        $this->item_status = self::STATUS_FULFILLED;
        return $this->save();
    }

    /**
     * Get original product name
     */
    public function getOriginalProductNameAttribute(): string
    {
        return $this->originalProduct->name ?? 'Unknown Product';
    }

    /**
     * Get new product name
     */
    public function getNewProductNameAttribute(): ?string
    {
        return $this->newProduct->name ?? null;
    }
}
