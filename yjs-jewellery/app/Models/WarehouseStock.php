<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Warehouse Stock Model
 *
 * Tracks inventory levels per warehouse.
 */
class WarehouseStock extends Model
{
    use HasFactory;

    protected $table = 'warehouse_stock';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'quantity',
        'reserved_quantity',
        'reorder_level',
        'reorder_quantity',
        'bin_location',
        'last_counted_at',
    ];

    protected $casts = [
        'last_counted_at' => 'datetime',
    ];

    protected $appends = ['available_quantity'];

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

    /**
     * Get available quantity (quantity - reserved).
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if stock is below reorder level.
     */
    public function needsReorder(): bool
    {
        if ($this->reorder_level === null) {
            return false;
        }
        return $this->available_quantity <= $this->reorder_level;
    }

    /**
     * Adjust stock quantity.
     */
    public function adjustQuantity(int $adjustment, string $movementType, ?string $notes = null, ?string $referenceType = null, ?int $referenceId = null): self
    {
        DB::transaction(function () use ($adjustment, $movementType, $notes, $referenceType, $referenceId) {
            $this->quantity += $adjustment;
            $this->save();

            // Log the movement
            StockMovement::create([
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'variant_id' => $this->variant_id,
                'movement_type' => $movementType,
                'quantity' => $adjustment,
                'balance_after' => $this->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            // Check for stock alerts
            $this->checkStockAlerts();
        });

        return $this;
    }

    /**
     * Reserve stock for an order.
     */
    public function reserve(int $quantity, int $orderId): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        DB::transaction(function () use ($quantity, $orderId) {
            $this->reserved_quantity += $quantity;
            $this->save();

            StockReservation::create([
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'variant_id' => $this->variant_id,
                'order_id' => $orderId,
                'quantity' => $quantity,
                'status' => 'reserved',
                'expires_at' => now()->addHours(24),
            ]);

            // Log the movement
            StockMovement::create([
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'variant_id' => $this->variant_id,
                'movement_type' => 'reservation',
                'quantity' => -$quantity,
                'balance_after' => $this->available_quantity,
                'reference_type' => Order::class,
                'reference_id' => $orderId,
                'notes' => 'Stock reserved for order',
                'created_by' => auth()->id(),
            ]);
        });

        return true;
    }

    /**
     * Release reservation.
     */
    public function releaseReservation(int $quantity, int $orderId): self
    {
        DB::transaction(function () use ($quantity, $orderId) {
            $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
            $this->save();

            StockReservation::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->where('variant_id', $this->variant_id)
                ->where('order_id', $orderId)
                ->where('status', 'reserved')
                ->update(['status' => 'released']);

            // Log the movement
            StockMovement::create([
                'warehouse_id' => $this->warehouse_id,
                'product_id' => $this->product_id,
                'variant_id' => $this->variant_id,
                'movement_type' => 'release_reservation',
                'quantity' => $quantity,
                'balance_after' => $this->available_quantity,
                'reference_type' => Order::class,
                'reference_id' => $orderId,
                'notes' => 'Stock reservation released',
                'created_by' => auth()->id(),
            ]);
        });

        return $this;
    }

    /**
     * Check and create stock alerts.
     */
    protected function checkStockAlerts(): void
    {
        // Check for out of stock
        if ($this->quantity <= 0) {
            StockAlert::updateOrCreate(
                [
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $this->product_id,
                    'variant_id' => $this->variant_id,
                    'alert_type' => 'out_of_stock',
                    'status' => 'active',
                ],
                [
                    'current_quantity' => $this->quantity,
                    'threshold_quantity' => 0,
                ]
            );
        } elseif ($this->needsReorder()) {
            StockAlert::updateOrCreate(
                [
                    'warehouse_id' => $this->warehouse_id,
                    'product_id' => $this->product_id,
                    'variant_id' => $this->variant_id,
                    'alert_type' => 'low_stock',
                    'status' => 'active',
                ],
                [
                    'current_quantity' => $this->quantity,
                    'threshold_quantity' => $this->reorder_level,
                ]
            );
        } else {
            // Resolve any existing alerts
            StockAlert::where('warehouse_id', $this->warehouse_id)
                ->where('product_id', $this->product_id)
                ->where('variant_id', $this->variant_id)
                ->whereIn('alert_type', ['low_stock', 'out_of_stock'])
                ->where('status', 'active')
                ->update(['status' => 'resolved']);
        }
    }

    /**
     * Get or create stock record.
     */
    public static function getOrCreate(int $warehouseId, int $productId, ?int $variantId = null): self
    {
        return self::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }
}
