<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Warehouse Model
 *
 * Represents a physical storage location.
 */
class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'manager_id',
        'is_default',
        'is_active',
        'accepts_returns',
        'allows_pickup',
        'priority',
        'operating_hours',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'accepts_returns' => 'boolean',
        'allows_pickup' => 'boolean',
        'operating_hours' => 'array',
    ];

    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_STORE = 'store';
    const TYPE_FULFILLMENT = 'fulfillment_center';

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function inventoryCounts(): HasMany
    {
        return $this->hasMany(InventoryCount::class);
    }

    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    /**
     * Get stock for a specific product.
     */
    public function getProductStock(int $productId, ?int $variantId = null): ?WarehouseStock
    {
        return $this->stock()
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->first();
    }

    /**
     * Get available quantity for a product.
     */
    public function getAvailableQuantity(int $productId, ?int $variantId = null): int
    {
        $stock = $this->getProductStock($productId, $variantId);
        return $stock ? $stock->available_quantity : 0;
    }

    /**
     * Get full address.
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->pincode,
            $this->country,
        ])->filter()->implode(', ');
    }

    /**
     * Get default warehouse.
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->where('is_active', true)->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAcceptsReturns($query)
    {
        return $query->where('accepts_returns', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }
}
