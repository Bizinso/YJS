<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OrderOffer - Frozen snapshot of offer at order time
 * This record is IMMUTABLE - changes to the original offer do not affect this.
 */
class OrderOffer extends Model
{
    protected $fillable = [
        'order_id',
        'offer_id',
        'offer_code',
        'offer_title',
        'offer_type_id',
        'discount_type',
        'discount_amount',
        'discount_percent',
        'applied_discount',
        'coupon_code_used',
        'offer_snapshot',
        'applied_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'applied_discount' => 'decimal:2',
        'offer_snapshot' => 'array',
        'applied_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    public function getAppliedDiscountDisplayAttribute(): string
    {
        return '₹' . number_format($this->applied_discount, 2);
    }
}
