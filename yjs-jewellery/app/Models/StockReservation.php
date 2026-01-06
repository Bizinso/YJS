<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Reservation Model
 *
 * Tracks stock reserved for pending orders.
 */
class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'variant_id',
        'order_id',
        'quantity',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    const STATUS_RESERVED = 'reserved';
    const STATUS_ALLOCATED = 'allocated';
    const STATUS_RELEASED = 'released';
    const STATUS_FULFILLED = 'fulfilled';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if reservation is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Fulfill the reservation.
     */
    public function fulfill(): self
    {
        $this->update(['status' => self::STATUS_FULFILLED]);
        return $this;
    }

    /**
     * Release the reservation.
     */
    public function release(): self
    {
        $this->update(['status' => self::STATUS_RELEASED]);
        return $this;
    }

    /**
     * Expire old reservations.
     */
    public static function expireOld(): int
    {
        $expired = self::where('status', self::STATUS_RESERVED)
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expired as $reservation) {
            $stock = WarehouseStock::getOrCreate(
                $reservation->warehouse_id,
                $reservation->product_id,
                $reservation->variant_id
            );

            $stock->releaseReservation($reservation->quantity, $reservation->order_id);
            $count++;
        }

        return $count;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_RESERVED, self::STATUS_ALLOCATED]);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_RESERVED)
            ->where('expires_at', '<', now());
    }
}
