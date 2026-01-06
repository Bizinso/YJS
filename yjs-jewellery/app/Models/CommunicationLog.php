<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Communication Log Model
 *
 * Tracks all customer communications across channels.
 */
class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'channel',
        'type',
        'direction',
        'subject',
        'content',
        'recipient',
        'status',
        'metadata',
        'sent_at',
        'delivered_at',
        'read_at',
        'error_message',
        'sent_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    // Channels
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_TICKET = 'ticket';

    // Types
    const TYPE_ORDER_CONFIRMATION = 'order_confirmation';
    const TYPE_SHIPPING_UPDATE = 'shipping_update';
    const TYPE_PAYMENT_REMINDER = 'payment_reminder';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_REFUND_PROCESSED = 'refund_processed';
    const TYPE_TICKET_RESPONSE = 'ticket_response';
    const TYPE_PROMOTIONAL = 'promotional';
    const TYPE_GENERAL = 'general';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_READ = 'read';

    // Directions
    const DIRECTION_INBOUND = 'inbound';
    const DIRECTION_OUTBOUND = 'outbound';

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

    public function sentByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Log a communication.
     */
    public static function log(
        int $userId,
        string $channel,
        string $type,
        string $content,
        ?string $subject = null,
        ?int $orderId = null,
        ?string $recipient = null,
        array $metadata = [],
        string $direction = self::DIRECTION_OUTBOUND
    ): self {
        return self::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'channel' => $channel,
            'type' => $type,
            'direction' => $direction,
            'subject' => $subject,
            'content' => $content,
            'recipient' => $recipient,
            'status' => self::STATUS_PENDING,
            'metadata' => $metadata,
            'sent_by' => auth()->id(),
        ]);
    }

    /**
     * Mark as sent.
     */
    public function markSent(): self
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = now();
        $this->save();
        return $this;
    }

    /**
     * Mark as delivered.
     */
    public function markDelivered(): self
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_at = now();
        $this->save();
        return $this;
    }

    /**
     * Mark as read.
     */
    public function markRead(): self
    {
        $this->status = self::STATUS_READ;
        $this->read_at = now();
        $this->save();
        return $this;
    }

    /**
     * Mark as failed.
     */
    public function markFailed(?string $error = null): self
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $error;
        $this->save();
        return $this;
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }
}
