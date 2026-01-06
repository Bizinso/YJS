<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Outstanding Payment Model
 *
 * Tracks pending/overdue payments.
 */
class OutstandingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'amount_due',
        'amount_paid',
        'amount_outstanding',
        'status',
        'due_date',
        'reminder_count',
        'last_reminder_at',
        'notes',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_outstanding' => 'decimal:2',
        'due_date' => 'date',
        'last_reminder_at' => 'datetime',
    ];

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PARTIAL = 'partial';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_WRITTEN_OFF = 'written_off';

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a payment.
     */
    public function recordPayment(float $amount): self
    {
        $this->amount_paid += $amount;
        $this->amount_outstanding = max(0, $this->amount_due - $this->amount_paid);

        if ($this->amount_outstanding <= 0) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->amount_paid > 0) {
            $this->status = self::STATUS_PARTIAL;
        }

        $this->save();
        return $this;
    }

    /**
     * Mark as overdue.
     */
    public function markOverdue(): self
    {
        if ($this->status !== self::STATUS_PAID && $this->due_date->lt(now())) {
            $this->status = self::STATUS_OVERDUE;
            $this->save();
        }
        return $this;
    }

    /**
     * Write off the outstanding amount.
     */
    public function writeOff(?string $notes = null): self
    {
        $this->status = self::STATUS_WRITTEN_OFF;
        if ($notes) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "[Write-off] " . $notes;
        }
        $this->save();
        return $this;
    }

    /**
     * Record reminder sent.
     */
    public function recordReminder(): self
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
        return $this;
    }

    /**
     * Get summary statistics.
     */
    public static function getSummary(): array
    {
        $total = self::whereNotIn('status', [self::STATUS_PAID, self::STATUS_WRITTEN_OFF])
            ->sum('amount_outstanding');
        $pending = self::where('status', self::STATUS_PENDING)->count();
        $partial = self::where('status', self::STATUS_PARTIAL)->count();
        $overdue = self::where('status', self::STATUS_OVERDUE)->count();
        $overdueAmount = self::where('status', self::STATUS_OVERDUE)->sum('amount_outstanding');

        return [
            'total_outstanding' => round($total, 2),
            'pending_count' => $pending,
            'partial_count' => $partial,
            'overdue_count' => $overdue,
            'overdue_amount' => round($overdueAmount, 2),
        ];
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_PARTIAL]);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_WRITTEN_OFF]);
    }
}
