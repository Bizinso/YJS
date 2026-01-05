<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Partner Inquiry Status History Model
 *
 * Tracks all status changes for a partner inquiry.
 */
class PartnerInquiryStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'partner_inquiry_status_history';

    protected $fillable = [
        'inquiry_id',
        'from_status',
        'to_status',
        'notes',
        'changed_by',
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
     * User who made the change
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted status change
     */
    public function getStatusChangeAttribute(): string
    {
        $from = $this->from_status ? ucfirst($this->from_status) : 'New';
        $to = ucfirst($this->to_status);
        return "{$from} → {$to}";
    }
}
