<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Order Fulfillment Model
 *
 * Tracks partial fulfillments for an order.
 */
class OrderFulfillment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'fulfillment_code',
        'status',
        'shipment_id',
        'total_amount',
        'notes',
        'fulfilled_by',
        'fulfilled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'fulfilled_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot method for generating fulfillment code.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fulfillment) {
            if (empty($fulfillment->fulfillment_code)) {
                $fulfillment->fulfillment_code = 'FUL-' . strtoupper(Str::random(8));
            }
        });

        static::created(function ($fulfillment) {
            $fulfillment->updateOrderFulfillmentStatus();
        });

        static::updated(function ($fulfillment) {
            $fulfillment->updateOrderFulfillmentStatus();
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
        return $this->hasMany(OrderFulfillmentItem::class, 'fulfillment_id');
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class, 'shipment_id');
    }

    public function fulfilledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * Calculate and update total amount.
     */
    public function calculateTotalAmount(): self
    {
        $this->total_amount = $this->items->sum('item_total');
        $this->save();
        return $this;
    }

    /**
     * Update the parent order's fulfillment status.
     */
    public function updateOrderFulfillmentStatus(): void
    {
        $order = $this->order()->with('orderProducts', 'fulfillments.items')->first();
        if (!$order) return;

        $totalOrderedQty = $order->orderProducts->sum('quantity');
        $totalFulfilledQty = $order->fulfillments
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->flatMap(fn($f) => $f->items)
            ->sum('quantity');

        if ($totalFulfilledQty >= $totalOrderedQty) {
            $order->fulfillment_status = 'fulfilled';
        } elseif ($totalFulfilledQty > 0) {
            $order->fulfillment_status = 'partial';
        } else {
            $order->fulfillment_status = 'unfulfilled';
        }

        $order->save();
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
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
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }
}
