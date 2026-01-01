<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ENHANCED OrderPayment Model
 * 
 * PRESERVES existing fields: order_id, payment_mode, transaction_id, amount, status
 * ADDS new Razorpay integration fields
 */
class OrderPayment extends Model
{
    protected $table = 'order_payments';

    protected $fillable = [
        // EXISTING fields
        'order_id',
        'payment_mode',      // enum: upi, card, cod, netbanking
        'transaction_id',
        'amount',
        'status',            // enum: success, failed, pending
        
        // NEW Razorpay fields
        'razorpay_order_id',
        'razorpay_payment_id',
        'currency',
        'method',
        'bank',
        'wallet',
        'vpa',
        'card_last4',
        'card_network',
        'fee',
        'tax',
        'error_code',
        'error_description',
        'gateway_response',
        'captured_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'gateway_response' => 'array',
        'captured_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class, 'payment_id');
    }

    // Accessors
    public function getAmountDisplayAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'success';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getNetAmountAttribute(): float
    {
        return $this->amount - ($this->fee ?? 0) - ($this->tax ?? 0);
    }
}
