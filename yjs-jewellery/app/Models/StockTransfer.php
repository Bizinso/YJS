<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Stock Transfer Model
 *
 * Tracks transfers of inventory between warehouses.
 */
class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'shipped_at',
        'received_at',
        'tracking_number',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transfer) {
            if (empty($transfer->transfer_number)) {
                $transfer->transfer_number = self::generateTransferNumber();
            }
        });
    }

    public static function generateTransferNumber(): string
    {
        $prefix = 'TRF';
        $date = now()->format('Ymd');
        $lastTransfer = self::whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $sequence = $lastTransfer ? ((int) substr($lastTransfer->transfer_number, -4) + 1) : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the transfer.
     */
    public function approve(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \Exception('Only draft transfers can be approved');
        }

        $this->update([
            'status' => self::STATUS_PENDING,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Ship the transfer.
     */
    public function ship(?string $trackingNumber = null): self
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \Exception('Only pending transfers can be shipped');
        }

        DB::transaction(function () use ($trackingNumber) {
            // Deduct stock from source warehouse
            foreach ($this->items as $item) {
                $stock = WarehouseStock::getOrCreate(
                    $this->from_warehouse_id,
                    $item->product_id,
                    $item->variant_id
                );

                $quantity = $item->quantity_sent ?? $item->quantity_requested;
                $stock->adjustQuantity(
                    -$quantity,
                    'transfer_out',
                    "Transfer to {$this->toWarehouse->name}",
                    self::class,
                    $this->id
                );
            }

            $this->update([
                'status' => self::STATUS_IN_TRANSIT,
                'shipped_at' => now(),
                'tracking_number' => $trackingNumber,
            ]);
        });

        return $this;
    }

    /**
     * Receive the transfer.
     */
    public function receive(array $receivedQuantities = []): self
    {
        if ($this->status !== self::STATUS_IN_TRANSIT) {
            throw new \Exception('Only in-transit transfers can be received');
        }

        DB::transaction(function () use ($receivedQuantities) {
            foreach ($this->items as $item) {
                $quantityReceived = $receivedQuantities[$item->id] ?? ($item->quantity_sent ?? $item->quantity_requested);
                $item->update(['quantity_received' => $quantityReceived]);

                // Add stock to destination warehouse
                $stock = WarehouseStock::getOrCreate(
                    $this->to_warehouse_id,
                    $item->product_id,
                    $item->variant_id
                );

                $stock->adjustQuantity(
                    $quantityReceived,
                    'transfer_in',
                    "Transfer from {$this->fromWarehouse->name}",
                    self::class,
                    $this->id
                );
            }

            $this->update([
                'status' => self::STATUS_RECEIVED,
                'received_at' => now(),
            ]);
        });

        return $this;
    }

    /**
     * Cancel the transfer.
     */
    public function cancel(): self
    {
        if (!in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING])) {
            throw new \Exception('Only draft or pending transfers can be cancelled');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }
}
