<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credit Note Usage Model
 */
class CreditNoteUsage extends Model
{
    protected $fillable = [
        'credit_note_id',
        'order_id',
        'amount_used',
        'balance_before',
        'balance_after',
    ];

    protected $casts = [
        'amount_used' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
