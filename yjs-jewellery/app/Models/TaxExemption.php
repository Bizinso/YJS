<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax Exemption Model
 *
 * Tracks tax exemptions for customers, products, or categories.
 */
class TaxExemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'exemption_type',
        'customer_id',
        'product_id',
        'category_id',
        'tax_rule_id',
        'reason',
        'valid_from',
        'valid_until',
        'documents',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'documents' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
    ];

    const TYPE_CUSTOMER = 'customer';
    const TYPE_PRODUCT = 'product';
    const TYPE_CATEGORY = 'category';

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class);
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check if exemption is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->valid_from > $today) {
            return false;
        }

        if ($this->valid_until && $this->valid_until < $today) {
            return false;
        }

        return true;
    }

    /**
     * Approve the exemption.
     */
    public function approve(?string $notes = null): self
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'admin_notes' => $notes,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Reject the exemption.
     */
    public function reject(string $reason): self
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'admin_notes' => $reason,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $this;
    }

    /**
     * Check if customer has valid exemption.
     */
    public static function hasValidExemption(int $customerId, ?int $taxRuleId = null): bool
    {
        $query = self::where('exemption_type', self::TYPE_CUSTOMER)
            ->where('customer_id', $customerId)
            ->where('status', self::STATUS_APPROVED)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });

        if ($taxRuleId) {
            $query->where(function ($q) use ($taxRuleId) {
                $q->where('tax_rule_id', $taxRuleId)->orWhereNull('tax_rule_id');
            });
        }

        return $query->exists();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeValid($query)
    {
        return $query->approved()
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            });
    }
}
