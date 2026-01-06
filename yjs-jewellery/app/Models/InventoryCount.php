<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Count Model
 *
 * Physical inventory count / stock audit.
 */
class InventoryCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'count_number',
        'warehouse_id',
        'type',
        'status',
        'notes',
        'created_by',
        'completed_by',
        'started_at',
        'completed_at',
        'total_items',
        'items_counted',
        'discrepancies',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const TYPE_FULL = 'full';
    const TYPE_CYCLE = 'cycle';
    const TYPE_SPOT = 'spot';

    const STATUS_DRAFT = 'draft';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($count) {
            if (empty($count->count_number)) {
                $count->count_number = self::generateCountNumber();
            }
        });
    }

    public static function generateCountNumber(): string
    {
        $prefix = 'CNT';
        $date = now()->format('Ymd');
        $lastCount = self::whereDate('created_at', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        $sequence = $lastCount ? ((int) substr($lastCount->count_number, -4) + 1) : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Start the inventory count.
     */
    public function start(): self
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \Exception('Only draft counts can be started');
        }

        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Complete the count and apply adjustments.
     */
    public function complete(bool $applyAdjustments = true): self
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            throw new \Exception('Only in-progress counts can be completed');
        }

        DB::transaction(function () use ($applyAdjustments) {
            $discrepancies = 0;
            $itemsCounted = 0;

            foreach ($this->items as $item) {
                if ($item->counted_quantity !== null) {
                    $itemsCounted++;
                    $variance = $item->counted_quantity - $item->expected_quantity;

                    if ($variance !== 0) {
                        $discrepancies++;

                        if ($applyAdjustments) {
                            $stock = WarehouseStock::getOrCreate(
                                $this->warehouse_id,
                                $item->product_id,
                                $item->variant_id
                            );

                            $stock->adjustQuantity(
                                $variance,
                                'count_correction',
                                "Inventory count adjustment: {$this->count_number}",
                                self::class,
                                $this->id
                            );
                        }
                    }

                    $item->update(['status' => 'verified']);
                }
            }

            $this->update([
                'status' => self::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'items_counted' => $itemsCounted,
                'discrepancies' => $discrepancies,
            ]);
        });

        return $this;
    }

    /**
     * Cancel the count.
     */
    public function cancel(): self
    {
        if (!in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_PROGRESS])) {
            throw new \Exception('Only draft or in-progress counts can be cancelled');
        }

        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    /**
     * Initialize count items from current stock.
     */
    public function initializeItems(?array $productIds = null): self
    {
        $query = WarehouseStock::where('warehouse_id', $this->warehouse_id);

        if ($productIds) {
            $query->whereIn('product_id', $productIds);
        }

        $stocks = $query->get();

        foreach ($stocks as $stock) {
            $this->items()->create([
                'product_id' => $stock->product_id,
                'variant_id' => $stock->variant_id,
                'bin_location' => $stock->bin_location,
                'expected_quantity' => $stock->quantity,
                'status' => 'pending',
            ]);
        }

        $this->update(['total_items' => $this->items()->count()]);

        return $this;
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentAttribute(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return round(($this->items_counted / $this->total_items) * 100, 1);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
