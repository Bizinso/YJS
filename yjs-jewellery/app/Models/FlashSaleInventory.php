<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Flash Sale Inventory Model
 *
 * Tracks limited stock for flash sales.
 */
class FlashSaleInventory extends Model
{
    protected $table = 'flash_sale_inventory';

    protected $fillable = [
        'offer_id',
        'product_id',
        'allocated_stock',
        'sold_stock',
        'reserved_stock',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get available stock
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->allocated_stock - $this->sold_stock - $this->reserved_stock;
    }

    /**
     * Check if stock is available
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Reserve stock
     */
    public function reserveStock(int $quantity): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        $this->reserved_stock += $quantity;
        $this->save();
        return true;
    }

    /**
     * Release reserved stock
     */
    public function releaseStock(int $quantity): void
    {
        $this->reserved_stock = max(0, $this->reserved_stock - $quantity);
        $this->save();
    }

    /**
     * Confirm sale (convert reserved to sold)
     */
    public function confirmSale(int $quantity): void
    {
        $this->reserved_stock = max(0, $this->reserved_stock - $quantity);
        $this->sold_stock += $quantity;
        $this->save();
    }

    /**
     * Get sold percentage
     */
    public function getSoldPercentageAttribute(): float
    {
        if ($this->allocated_stock == 0) {
            return 0;
        }
        return round(($this->sold_stock / $this->allocated_stock) * 100, 1);
    }
}
