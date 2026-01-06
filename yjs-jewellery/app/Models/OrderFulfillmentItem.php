<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Fulfillment Item Model
 *
 * Items within a fulfillment.
 */
class OrderFulfillmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fulfillment_id',
        'order_item_id',
        'product_id',
        'quantity',
        'item_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'item_total' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'fulfillment_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(orderProduct::class, 'order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
