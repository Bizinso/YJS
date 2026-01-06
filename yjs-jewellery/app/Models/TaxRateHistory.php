<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tax Rate History Model
 *
 * Audit trail of tax rate changes.
 */
class TaxRateHistory extends Model
{
    use HasFactory;

    protected $table = 'tax_rate_history';

    protected $fillable = [
        'tax_rule_id',
        'old_rate',
        'new_rate',
        'old_cgst',
        'new_cgst',
        'old_sgst',
        'new_sgst',
        'old_igst',
        'new_igst',
        'reason',
        'changed_by',
        'effective_from',
    ];

    protected $casts = [
        'old_rate' => 'decimal:2',
        'new_rate' => 'decimal:2',
        'old_cgst' => 'decimal:2',
        'new_cgst' => 'decimal:2',
        'old_sgst' => 'decimal:2',
        'new_sgst' => 'decimal:2',
        'old_igst' => 'decimal:2',
        'new_igst' => 'decimal:2',
        'effective_from' => 'datetime',
    ];

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get formatted change summary.
     */
    public function getChangeSummaryAttribute(): string
    {
        $changes = [];

        if ($this->old_rate !== $this->new_rate) {
            $changes[] = "Rate: {$this->old_rate}% → {$this->new_rate}%";
        }

        if ($this->old_cgst !== $this->new_cgst) {
            $changes[] = "CGST: {$this->old_cgst}% → {$this->new_cgst}%";
        }

        if ($this->old_sgst !== $this->new_sgst) {
            $changes[] = "SGST: {$this->old_sgst}% → {$this->new_sgst}%";
        }

        if ($this->old_igst !== $this->new_igst) {
            $changes[] = "IGST: {$this->old_igst}% → {$this->new_igst}%";
        }

        return implode(', ', $changes);
    }
}
