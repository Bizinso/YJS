<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRefund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'refund_code',
        'razorpay_refund_id',
        'type',
        'amount',
        'reason',
        'status',
        'failure_reason',
        'gateway_response',
        'initiated_by',
        'initiated_at',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'initiated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(OrderPayment::class, 'payment_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function getAmountDisplayAttribute(): string
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getIsProcessedAttribute(): bool
    {
        return $this->status === 'processed';
    }
}
