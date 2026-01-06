<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Movement Model
 *
 * Comprehensive stock movement history.
 */
class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'movement_type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_RETURN = 'return';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_COUNT_CORRECTION = 'count_correction';
    const TYPE_DAMAGE = 'damage';
    const TYPE_EXPIRED = 'expired';
    const TYPE_RESERVATION = 'reservation';
    const TYPE_RELEASE_RESERVATION = 'release_reservation';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference model.
     */
    public function reference()
    {
        if ($this->reference_type && $this->reference_id) {
            return $this->reference_type::find($this->reference_id);
        }
        return null;
    }

    /**
     * Get movement type label.
     */
    public function getMovementLabelAttribute(): string
    {
        return match ($this->movement_type) {
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_RETURN => 'Return',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_COUNT_CORRECTION => 'Count Correction',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_EXPIRED => 'Expired',
            self::TYPE_RESERVATION => 'Reserved',
            self::TYPE_RELEASE_RESERVATION => 'Released',
            default => ucfirst(str_replace('_', ' ', $this->movement_type)),
        };
    }

    /**
     * Get direction (in/out).
     */
    public function getDirectionAttribute(): string
    {
        return $this->quantity >= 0 ? 'in' : 'out';
    }

    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        return $query->where('product_id', $productId)
            ->where('variant_id', $variantId);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeIncoming($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('quantity', '<', 0);
    }
}
