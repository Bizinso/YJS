<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Timeline Model
 *
 * Tracks all activities and events for an order.
 */
class OrderTimeline extends Model
{
    use HasFactory;

    protected $table = 'order_timeline';

    protected $fillable = [
        'order_id',
        'event_type',
        'event_title',
        'event_description',
        'event_data',
        'old_value',
        'new_value',
        'performed_by',
        'performer_type',
        'ip_address',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    // Event Types
    const TYPE_ORDER_CREATED = 'order_created';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_NOTE_ADDED = 'note_added';
    const TYPE_SHIPMENT_CREATED = 'shipment_created';
    const TYPE_SHIPMENT_UPDATED = 'shipment_updated';
    const TYPE_HOLD_PLACED = 'hold_placed';
    const TYPE_HOLD_RELEASED = 'hold_released';
    const TYPE_FULFILLMENT_CREATED = 'fulfillment_created';
    const TYPE_OVERRIDE_APPLIED = 'override_applied';
    const TYPE_REFUND_INITIATED = 'refund_initiated';
    const TYPE_REFUND_COMPLETED = 'refund_completed';
    const TYPE_CANCELLATION_REQUESTED = 'cancellation_requested';
    const TYPE_RETURN_REQUESTED = 'return_requested';
    const TYPE_EXCHANGE_REQUESTED = 'exchange_requested';
    const TYPE_ADDRESS_CHANGED = 'address_changed';
    const TYPE_CUSTOMER_CONTACTED = 'customer_contacted';

    // Performer Types
    const PERFORMER_ADMIN = 'admin';
    const PERFORMER_CUSTOMER = 'customer';
    const PERFORMER_SYSTEM = 'system';

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Log an order event.
     */
    public static function logEvent(
        int $orderId,
        string $eventType,
        string $title,
        ?string $description = null,
        ?array $data = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?int $performedBy = null,
        string $performerType = self::PERFORMER_ADMIN
    ): self {
        return self::create([
            'order_id' => $orderId,
            'event_type' => $eventType,
            'event_title' => $title,
            'event_description' => $description,
            'event_data' => $data,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'performed_by' => $performedBy ?? auth()->id(),
            'performer_type' => $performerType,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Scopes
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeByAdmin($query)
    {
        return $query->where('performer_type', self::PERFORMER_ADMIN);
    }

    public function scopeByCustomer($query)
    {
        return $query->where('performer_type', self::PERFORMER_CUSTOMER);
    }

    public function scopeBySystem($query)
    {
        return $query->where('performer_type', self::PERFORMER_SYSTEM);
    }
}
