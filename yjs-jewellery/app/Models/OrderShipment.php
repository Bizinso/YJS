<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Order Shipment Model
 *
 * Handles split shipments for orders.
 */
class OrderShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipment_code',
        'status',
        'courier_name',
        'courier_id',
        'awb_number',
        'tracking_url',
        'shipping_cost',
        'weight',
        'dimensions',
        'shipping_address_id',
        'notes',
        'shipped_at',
        'delivered_at',
        'pickup_scheduled_at',
        'created_by',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'shipping_cost' => 'decimal:2',
        'weight' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'pickup_scheduled_at' => 'datetime',
    ];

    // Statuses
    const STATUS_CREATED = 'created';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot method for generating shipment code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->shipment_code)) {
                $shipment->shipment_code = 'SHP-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class, 'shipment_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(OrderFulfillment::class, 'shipment_id');
    }

    /**
     * Check if shipment can be modified.
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [self::STATUS_CREATED]);
    }

    /**
     * Check if shipment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_CREATED, self::STATUS_PICKED_UP]);
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Update shipment status.
     */
    public function updateStatus(string $newStatus, ?int $userId = null): self
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Set timestamps based on status
        switch ($newStatus) {
            case self::STATUS_PICKED_UP:
            case self::STATUS_IN_TRANSIT:
                $this->shipped_at = $this->shipped_at ?? now();
                break;
            case self::STATUS_DELIVERED:
                $this->delivered_at = now();
                break;
        }

        $this->save();

        // Log to timeline
        OrderTimeline::logEvent(
            $this->order_id,
            OrderTimeline::TYPE_SHIPMENT_UPDATED,
            "Shipment {$this->shipment_code} status updated",
            "Status changed from {$oldStatus} to {$newStatus}",
            ['shipment_id' => $this->id],
            $oldStatus,
            $newStatus,
            $userId
        );

        return $this;
    }

    /**
     * Scopes
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_CREATED, self::STATUS_PICKED_UP, self::STATUS_IN_TRANSIT]);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }
}
