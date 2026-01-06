<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ticket Message Model
 */
class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender_id',
        'sender_type',
        'message',
        'attachments',
        'is_internal_note',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal_note' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Mark as read.
     */
    public function markAsRead(): self
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
        return $this;
    }
}
