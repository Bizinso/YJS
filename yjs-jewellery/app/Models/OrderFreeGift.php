<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Free Gift Model
 *
 * Tracks free gifts given with orders.
 */
class OrderFreeGift extends Model
{
    protected $table = 'order_free_gifts';

    protected $fillable = [
        'order_id',
        'offer_id',
        'product_id',
        'quantity',
        'gift_value',
    ];

    protected $casts = [
        'gift_value' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
