<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Transfer Item Model
 */
class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'variant_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'notes',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
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
     * Get variance between sent and received.
     */
    public function getVarianceAttribute(): ?int
    {
        if ($this->quantity_sent === null || $this->quantity_received === null) {
            return null;
        }
        return $this->quantity_received - $this->quantity_sent;
    }
}
