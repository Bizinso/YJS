<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Shipment Item Model
 *
 * Items within a shipment.
 */
class OrderShipmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'order_item_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * Relationships
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'shipment_id');
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
