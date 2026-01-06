<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Alert Model
 *
 * Tracks low stock and other inventory alerts.
 */
class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'alert_type',
        'current_quantity',
        'threshold_quantity',
        'status',
        'acknowledged_by',
        'acknowledged_at',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_OUT_OF_STOCK = 'out_of_stock';
    const TYPE_OVERSTOCK = 'overstock';
    const TYPE_EXPIRING = 'expiring';

    const STATUS_ACTIVE = 'active';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';

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

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Get alert type label.
     */
    public function getAlertLabelAttribute(): string
    {
        return match ($this->alert_type) {
            self::TYPE_LOW_STOCK => 'Low Stock',
            self::TYPE_OUT_OF_STOCK => 'Out of Stock',
            self::TYPE_OVERSTOCK => 'Overstock',
            self::TYPE_EXPIRING => 'Expiring Soon',
            default => ucfirst(str_replace('_', ' ', $this->alert_type)),
        };
    }

    /**
     * Get severity level.
     */
    public function getSeverityAttribute(): string
    {
        return match ($this->alert_type) {
            self::TYPE_OUT_OF_STOCK => 'critical',
            self::TYPE_LOW_STOCK => 'warning',
            self::TYPE_EXPIRING => 'warning',
            self::TYPE_OVERSTOCK => 'info',
            default => 'info',
        };
    }

    /**
     * Acknowledge the alert.
     */
    public function acknowledge(): self
    {
        $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);

        return $this;
    }

    /**
     * Resolve the alert.
     */
    public function resolve(): self
    {
        $this->update(['status' => self::STATUS_RESOLVED]);
        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeCritical($query)
    {
        return $query->where('alert_type', self::TYPE_OUT_OF_STOCK);
    }

    public function scopeWarning($query)
    {
        return $query->whereIn('alert_type', [self::TYPE_LOW_STOCK, self::TYPE_EXPIRING]);
    }
}
