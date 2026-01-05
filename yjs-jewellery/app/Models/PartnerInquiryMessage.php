<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner Inquiry Message Model
 *
 * Handles communication between partners and admins
 * regarding bulk order inquiries.
 */
class PartnerInquiryMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'inquiry_id',
        'user_id',
        'sender_type',
        'message',
        'attachments',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Parent inquiry
     */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(PartnerInquiry::class, 'inquiry_id');
    }

    /**
     * Sender user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== ACCESSORS ====================

    /**
     * Check if sent by partner
     */
    public function getIsFromPartnerAttribute(): bool
    {
        return $this->sender_type === 'partner';
    }

    /**
     * Check if sent by admin
     */
    public function getIsFromAdminAttribute(): bool
    {
        return $this->sender_type === 'admin';
    }

    // ==================== METHODS ====================

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            $this->save();
        }
    }
}
