<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bulk Price Update Model
 *
 * Tracks bulk price update operations.
 */
class BulkPriceUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'value',
        'formula',
        'apply_to',
        'product_ids',
        'category_id',
        'status',
        'products_affected',
        'preview_data',
        'created_by',
        'applied_at',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'preview_data' => 'array',
        'applied_at' => 'datetime',
        'value' => 'decimal:2',
    ];

    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    const TYPE_FORMULA = 'formula';

    const APPLY_ALL = 'all';
    const APPLY_CATEGORY = 'category';
    const APPLY_SELECTED = 'selected';

    const STATUS_PENDING = 'pending';
    const STATUS_PREVIEW = 'preview';
    const STATUS_APPLIED = 'applied';
    const STATUS_CANCELLED = 'cancelled';

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Calculate new price based on update type.
     */
    public function calculateNewPrice(float $currentPrice): float
    {
        return match ($this->type) {
            self::TYPE_PERCENTAGE => $currentPrice * (1 + ($this->value / 100)),
            self::TYPE_FIXED => $currentPrice + $this->value,
            default => $currentPrice,
        };
    }

    /**
     * Apply the price update.
     */
    public function apply(): self
    {
        $this->status = self::STATUS_APPLIED;
        $this->applied_at = now();
        $this->save();
        return $this;
    }
}
