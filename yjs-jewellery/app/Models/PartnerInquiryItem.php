<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner Inquiry Item Model
 *
 * Represents a product/item in a partner's bulk order inquiry.
 */
class PartnerInquiryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit',
        'specifications',
        'notes',
        'unit_price',
        'total_price',
        'discount',
        'quantity_fulfilled',
        'item_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'quantity_fulfilled' => 'integer',
    ];

    /**
     * Item status labels
     */
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'out_of_stock' => 'Out of Stock',
        'partially_available' => 'Partially Available',
        'ready' => 'Ready',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Parent inquiry
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PartnerInquiry::class, 'inquiry_id');
    }

    /**
     * Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product variant (if applicable)
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'variant_id');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->item_status] ?? ucfirst($this->item_status);
    }

    /**
     * Get remaining quantity to fulfill
     */
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->quantity_fulfilled);
    }

    /**
     * Check if fully fulfilled
     */
    public function getIsFullfilledAttribute(): bool
    {
        return $this->quantity_fulfilled >= $this->quantity;
    }

    /**
     * Get fulfillment percentage
     */
    public function getFulfillmentPercentageAttribute(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        return round(($this->quantity_fulfilled / $this->quantity) * 100, 2);
    }

    // ==================== METHODS ====================

    /**
     * Calculate total price based on unit price
     */
    public function calculateTotal(): float
    {
        if (!$this->unit_price) {
            return 0;
        }
        $total = ($this->unit_price * $this->quantity) - ($this->discount ?? 0);
        $this->total_price = max(0, $total);
        return $this->total_price;
    }

    /**
     * Update fulfillment quantity
     */
    public function fulfill(int $quantity): void
    {
        $this->quantity_fulfilled = min($this->quantity, $this->quantity_fulfilled + $quantity);

        if ($this->quantity_fulfilled >= $this->quantity) {
            $this->item_status = 'delivered';
        } elseif ($this->quantity_fulfilled > 0) {
            $this->item_status = 'partially_available';
        }

        $this->save();
    }
}
