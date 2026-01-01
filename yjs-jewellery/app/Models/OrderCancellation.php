<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancellation extends Model
{
    protected $fillable = [
        'order_id',
        'cancelled_by',
        'cancelled_by_user_id',
        'reason_code',
        'reason_text',
        'order_status_at_cancel',
        'refund_id',
        'refund_amount',
        'refund_status',
        'cancelled_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefund::class, 'refund_id');
    }
}
