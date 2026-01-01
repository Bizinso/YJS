<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferUsage extends Model
{
    protected $fillable = [
        'offer_id',
        'order_id',
        'customer_id',
        'discount_amount',
        'used_at',
        'reversed',
        'reversed_at',
        'reversal_reason',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'reversed' => 'boolean',
        'used_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
