<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Credit Note Model
 *
 * Store credits issued to customers.
 */
class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_note_number',
        'order_id',
        'refund_id',
        'user_id',
        'amount',
        'used_amount',
        'balance',
        'status',
        'reason_code',
        'reason_description',
        'valid_from',
        'valid_until',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    // Statuses
    const STATUS_ACTIVE = 'active';
    const STATUS_PARTIALLY_USED = 'partially_used';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Reason Codes
    const REASON_REFUND = 'refund';
    const REASON_GOODWILL = 'goodwill';
    const REASON_COMPENSATION = 'compensation';
    const REASON_LOYALTY = 'loyalty';
    const REASON_PROMOTIONAL = 'promotional';
    const REASON_OTHER = 'other';

    /**
     * Boot method.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creditNote) {
            if (empty($creditNote->credit_note_number)) {
                $creditNote->credit_note_number = 'CN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
            if (empty($creditNote->valid_from)) {
                $creditNote->valid_from = now()->toDateString();
            }
            if (empty($creditNote->balance)) {
                $creditNote->balance = $creditNote->amount;
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

    public function refund(): BelongsTo
    {
        return $this->belongsTo(RefundRequest::class, 'refund_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CreditNoteUsage::class)->orderBy('created_at', 'desc');
    }

    /**
     * Use credit note for an order.
     */
    public function useForOrder(int $orderId, float $amount): ?CreditNoteUsage
    {
        if (!$this->canBeUsed()) {
            return null;
        }

        $amountToUse = min($amount, $this->balance);

        $usage = CreditNoteUsage::create([
            'credit_note_id' => $this->id,
            'order_id' => $orderId,
            'amount_used' => $amountToUse,
            'balance_before' => $this->balance,
            'balance_after' => $this->balance - $amountToUse,
        ]);

        $this->used_amount += $amountToUse;
        $this->balance -= $amountToUse;

        if ($this->balance <= 0) {
            $this->status = self::STATUS_EXHAUSTED;
        } elseif ($this->used_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_USED;
        }

        $this->save();

        return $usage;
    }

    /**
     * Reverse a usage (for order cancellation).
     */
    public function reverseUsage(CreditNoteUsage $usage): self
    {
        $this->used_amount -= $usage->amount_used;
        $this->balance += $usage->amount_used;

        if ($this->balance >= $this->amount) {
            $this->status = self::STATUS_ACTIVE;
        } elseif ($this->used_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_USED;
        }

        $this->save();
        $usage->delete();

        return $this;
    }

    /**
     * Check if credit note can be used.
     */
    public function canBeUsed(): bool
    {
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_USED])) {
            return false;
        }

        if ($this->balance <= 0) {
            return false;
        }

        if ($this->valid_until && now()->gt($this->valid_until)) {
            $this->update(['status' => self::STATUS_EXPIRED]);
            return false;
        }

        return true;
    }

    /**
     * Check and update expired status.
     */
    public function checkExpiry(): self
    {
        if ($this->valid_until && now()->gt($this->valid_until) &&
            !in_array($this->status, [self::STATUS_EXPIRED, self::STATUS_EXHAUSTED, self::STATUS_CANCELLED])) {
            $this->update(['status' => self::STATUS_EXPIRED]);
        }

        return $this;
    }

    /**
     * Cancel the credit note.
     */
    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    /**
     * Get available credit notes for a user.
     */
    public static function getAvailableForUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_USED])
            ->where('balance', '>', 0)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->orderBy('valid_until', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get total available credit for a user.
     */
    public static function getTotalAvailableForUser(int $userId): float
    {
        return (float) self::getAvailableForUser($userId)->sum('balance');
    }

    /**
     * Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PARTIALLY_USED]);
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where('balance', '>', 0)
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            });
    }
}
