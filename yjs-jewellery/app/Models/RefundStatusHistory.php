<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Refund Status History Model
 */
class RefundStatusHistory extends Model
{
    protected $table = 'refund_status_history';

    protected $fillable = [
        'refund_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class, 'refund_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
