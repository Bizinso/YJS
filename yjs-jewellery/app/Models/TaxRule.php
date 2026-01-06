<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tax Rule Model
 *
 * Specific tax calculations for products and orders.
 */
class TaxRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'tax_zone_id',
        'tax_type',
        'rate',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'apply_to',
        'apply_to_ids',
        'min_amount',
        'max_amount',
        'calculation_type',
        'is_inclusive',
        'is_compound',
        'priority',
        'is_active',
        'valid_from',
        'valid_until',
        'created_by',
    ];

    protected $casts = [
        'apply_to_ids' => 'array',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'is_inclusive' => 'boolean',
        'is_compound' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    const TYPE_GST = 'gst';
    const TYPE_IGST = 'igst';
    const TYPE_CGST_SGST = 'cgst_sgst';
    const TYPE_VAT = 'vat';
    const TYPE_CUSTOM = 'custom';

    const APPLY_ALL = 'all';
    const APPLY_CATEGORY = 'category';
    const APPLY_PRODUCT = 'product';
    const APPLY_TAG = 'tag';

    public function taxZone(): BelongsTo
    {
        return $this->belongsTo(TaxZone::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rateHistory(): HasMany
    {
        return $this->hasMany(TaxRateHistory::class);
    }

    public function exemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class);
    }

    /**
     * Calculate tax for a given amount.
     */
    public function calculateTax(float $amount, bool $isInterstate = false): array
    {
        if ($this->is_inclusive) {
            $taxableAmount = $amount / (1 + ($this->rate / 100));
            $taxAmount = $amount - $taxableAmount;
        } else {
            $taxableAmount = $amount;
            $taxAmount = $amount * ($this->rate / 100);
        }

        $breakdown = [
            'taxable_amount' => round($taxableAmount, 2),
            'total_tax' => round($taxAmount, 2),
            'rate' => $this->rate,
            'tax_type' => $this->tax_type,
        ];

        // For GST, provide CGST/SGST or IGST breakdown
        if (in_array($this->tax_type, [self::TYPE_GST, self::TYPE_CGST_SGST, self::TYPE_IGST])) {
            if ($isInterstate && $this->igst_rate) {
                $breakdown['igst'] = round($taxableAmount * ($this->igst_rate / 100), 2);
                $breakdown['igst_rate'] = $this->igst_rate;
            } else {
                if ($this->cgst_rate) {
                    $breakdown['cgst'] = round($taxableAmount * ($this->cgst_rate / 100), 2);
                    $breakdown['cgst_rate'] = $this->cgst_rate;
                }
                if ($this->sgst_rate) {
                    $breakdown['sgst'] = round($taxableAmount * ($this->sgst_rate / 100), 2);
                    $breakdown['sgst_rate'] = $this->sgst_rate;
                }
            }
        }

        return $breakdown;
    }

    /**
     * Check if this rule applies to a product.
     */
    public function appliesToProduct(Product $product): bool
    {
        if ($this->apply_to === self::APPLY_ALL) {
            return true;
        }

        if (!$this->apply_to_ids) {
            return false;
        }

        return match ($this->apply_to) {
            self::APPLY_PRODUCT => in_array($product->id, $this->apply_to_ids),
            self::APPLY_CATEGORY => in_array($product->category_id, $this->apply_to_ids),
            self::APPLY_TAG => $product->tags()->whereIn('id', $this->apply_to_ids)->exists(),
            default => false,
        };
    }

    /**
     * Check if rule is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $today = now()->toDateString();

        if ($this->valid_from && $today < $this->valid_from->toDateString()) {
            return false;
        }

        if ($this->valid_until && $today > $this->valid_until->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Update the rate and log history.
     */
    public function updateRate(float $newRate, ?float $cgst = null, ?float $sgst = null, ?float $igst = null, ?string $reason = null): self
    {
        TaxRateHistory::create([
            'tax_rule_id' => $this->id,
            'old_rate' => $this->rate,
            'new_rate' => $newRate,
            'old_cgst' => $this->cgst_rate,
            'new_cgst' => $cgst,
            'old_sgst' => $this->sgst_rate,
            'new_sgst' => $sgst,
            'old_igst' => $this->igst_rate,
            'new_igst' => $igst,
            'reason' => $reason,
            'changed_by' => auth()->id(),
            'effective_from' => now(),
        ]);

        $this->update([
            'rate' => $newRate,
            'cgst_rate' => $cgst,
            'sgst_rate' => $sgst,
            'igst_rate' => $igst,
        ]);

        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            });
    }

    public function scopeForZone($query, $zoneId)
    {
        return $query->where(function ($q) use ($zoneId) {
            $q->where('tax_zone_id', $zoneId)->orWhereNull('tax_zone_id');
        });
    }
}
