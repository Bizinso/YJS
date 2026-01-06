<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Status History Model
 */
class TicketStatusHistory extends Model
{
    protected $table = 'ticket_status_history';

    protected $fillable = [
        'ticket_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
