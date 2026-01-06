<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Settlement Transaction Model
 */
class SettlementTransaction extends Model
{
    protected $fillable = [
        'settlement_id',
        'order_id',
        'payment_id',
        'transaction_id',
        'type',
        'amount',
        'fee',
        'tax',
        'status',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // Types
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND = 'refund';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_MATCHED = 'matched';
    const STATUS_UNMATCHED = 'unmatched';

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(PaymentSettlement::class, 'settlement_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Try to match transaction with an order payment.
     */
    public function matchWithPayment(): bool
    {
        $payment = OrderPayment::where('razorpay_payment_id', $this->transaction_id)->first();

        if ($payment) {
            $this->update([
                'order_id' => $payment->order_id,
                'payment_id' => $payment->id,
                'status' => self::STATUS_MATCHED,
            ]);
            return true;
        }

        $this->update(['status' => self::STATUS_UNMATCHED]);
        return false;
    }
}
