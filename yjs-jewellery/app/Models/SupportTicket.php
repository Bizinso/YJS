<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Support Ticket Model
 */
class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'order_id',
        'subject',
        'description',
        'category',
        'priority',
        'status',
        'assigned_to',
        'assigned_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'resolution_notes',
        'rating',
        'rating_feedback',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'assigned_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Statuses
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_CUSTOMER = 'pending_customer';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Priorities
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Categories
    const CATEGORY_ORDER = 'order_issue';
    const CATEGORY_PRODUCT = 'product_inquiry';
    const CATEGORY_PAYMENT = 'payment_issue';
    const CATEGORY_SHIPPING = 'shipping';
    const CATEGORY_RETURN = 'return_exchange';
    const CATEGORY_OTHER = 'other';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = 'TKT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(string $newStatus, ?string $notes = null, ?int $changedBy = null): self
    {
        $oldStatus = $this->status;

        TicketStatusHistory::create([
            'ticket_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $changedBy ?? auth()->id(),
        ]);

        $this->status = $newStatus;

        switch ($newStatus) {
            case self::STATUS_RESOLVED:
                $this->resolved_at = now();
                break;
            case self::STATUS_CLOSED:
                $this->closed_at = now();
                break;
        }

        $this->save();
        return $this;
    }

    /**
     * Assign ticket to an agent.
     */
    public function assignTo(int $agentId, ?int $assignedBy = null): self
    {
        $this->assigned_to = $agentId;
        $this->assigned_at = now();

        if ($this->status === self::STATUS_OPEN) {
            $this->status = self::STATUS_ASSIGNED;
        }

        $this->save();

        TicketStatusHistory::create([
            'ticket_id' => $this->id,
            'from_status' => $this->status,
            'to_status' => self::STATUS_ASSIGNED,
            'notes' => "Assigned to agent #{$agentId}",
            'changed_by' => $assignedBy ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Add a message to the ticket.
     */
    public function addMessage(int $senderId, string $senderType, string $message, ?array $attachments = null, bool $isInternal = false): TicketMessage
    {
        $ticketMessage = TicketMessage::create([
            'ticket_id' => $this->id,
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'message' => $message,
            'attachments' => $attachments,
            'is_internal_note' => $isInternal,
        ]);

        // Update first response time if admin responding
        if ($senderType === 'admin' && !$this->first_response_at) {
            $this->first_response_at = now();
            $this->save();
        }

        return $ticketMessage;
    }

    /**
     * Rate the ticket resolution.
     */
    public function rate(int $rating, ?string $feedback = null): self
    {
        $this->rating = $rating;
        $this->rating_feedback = $feedback;
        $this->save();
        return $this;
    }

    /**
     * Get priority label.
     */
    public static function getPriorityLabels(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Get category labels.
     */
    public static function getCategoryLabels(): array
    {
        return [
            self::CATEGORY_ORDER => 'Order Issue',
            self::CATEGORY_PRODUCT => 'Product Inquiry',
            self::CATEGORY_PAYMENT => 'Payment Issue',
            self::CATEGORY_SHIPPING => 'Shipping',
            self::CATEGORY_RETURN => 'Return/Exchange',
            self::CATEGORY_OTHER => 'Other',
        ];
    }

    /**
     * Get status labels.
     */
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_PENDING_CUSTOMER => 'Pending Customer',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('assigned_to', $agentId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
