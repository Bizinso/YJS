<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment Settlement Model
 *
 * Tracks payment gateway settlements for reconciliation.
 */
class PaymentSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'payment_gateway',
        'settlement_date',
        'gross_amount',
        'fees',
        'tax',
        'net_amount',
        'transaction_count',
        'status',
        'system_amount',
        'discrepancy',
        'notes',
        'settlement_data',
        'reconciled_by',
        'reconciled_at',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'gross_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'tax' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'system_amount' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'settlement_data' => 'array',
        'reconciled_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_RECONCILED = 'reconciled';
    const STATUS_DISCREPANCY = 'discrepancy';

    /**
     * Relationships
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SettlementTransaction::class, 'settlement_id');
    }

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    /**
     * Reconcile the settlement.
     */
    public function reconcile(?int $reconciledBy = null): self
    {
        // Calculate system amount from matched transactions
        $this->system_amount = $this->transactions()
            ->where('status', 'matched')
            ->sum('amount');

        $this->discrepancy = abs($this->net_amount - ($this->system_amount ?? 0));

        if ($this->discrepancy < 1) { // Allow small discrepancy
            $this->status = self::STATUS_RECONCILED;
        } else {
            $this->status = self::STATUS_DISCREPANCY;
        }

        $this->reconciled_by = $reconciledBy ?? auth()->id();
        $this->reconciled_at = now();
        $this->save();

        return $this;
    }

    /**
     * Get settlement summary statistics.
     */
    public static function getSummary(?string $gateway = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = self::query();

        if ($gateway) {
            $query->where('payment_gateway', $gateway);
        }
        if ($startDate) {
            $query->where('settlement_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('settlement_date', '<=', $endDate);
        }

        $total = $query->count();
        $totalAmount = (clone $query)->sum('net_amount');
        $totalFees = (clone $query)->sum('fees');
        $reconciled = (clone $query)->where('status', self::STATUS_RECONCILED)->count();
        $pending = (clone $query)->where('status', self::STATUS_PENDING)->count();
        $discrepancies = (clone $query)->where('status', self::STATUS_DISCREPANCY)->count();
        $totalDiscrepancy = (clone $query)->where('status', self::STATUS_DISCREPANCY)->sum('discrepancy');

        return [
            'total_settlements' => $total,
            'total_amount' => round($totalAmount, 2),
            'total_fees' => round($totalFees, 2),
            'reconciled_count' => $reconciled,
            'pending_count' => $pending,
            'discrepancy_count' => $discrepancies,
            'total_discrepancy' => round($totalDiscrepancy, 2),
        ];
    }

    /**
     * Scopes
     */
    public function scopeForGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeWithDiscrepancy($query)
    {
        return $query->where('status', self::STATUS_DISCREPANCY);
    }

    public function scopeDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('settlement_date', [$start, $end]);
    }
}
