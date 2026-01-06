<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory Count Item Model
 */
class InventoryCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'variant_id',
        'bin_location',
        'expected_quantity',
        'counted_quantity',
        'status',
        'notes',
        'counted_by',
        'counted_at',
    ];

    protected $casts = [
        'counted_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_COUNTED = 'counted';
    const STATUS_VERIFIED = 'verified';

    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function countedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    /**
     * Get variance between expected and counted.
     */
    public function getVarianceAttribute(): ?int
    {
        if ($this->counted_quantity === null) {
            return null;
        }
        return $this->counted_quantity - $this->expected_quantity;
    }

    /**
     * Record a count.
     */
    public function recordCount(int $quantity, ?string $notes = null): self
    {
        $this->update([
            'counted_quantity' => $quantity,
            'status' => self::STATUS_COUNTED,
            'notes' => $notes,
            'counted_by' => auth()->id(),
            'counted_at' => now(),
        ]);

        // Update parent progress
        $count = $this->inventoryCount;
        $count->update([
            'items_counted' => $count->items()->where('status', '!=', self::STATUS_PENDING)->count(),
        ]);

        return $this;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeWithVariance($query)
    {
        return $query->whereColumn('counted_quantity', '!=', 'expected_quantity');
    }
}
