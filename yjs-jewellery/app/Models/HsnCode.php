<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HSN Code Model
 *
 * Harmonized System of Nomenclature codes for GST compliance.
 */
class HsnCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'gst_rate',
        'cgst_rate',
        'sgst_rate',
        'igst_rate',
        'cess_rate',
        'type',
        'is_active',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'cgst_rate' => 'decimal:2',
        'sgst_rate' => 'decimal:2',
        'igst_rate' => 'decimal:2',
        'cess_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    const TYPE_GOODS = 'goods';
    const TYPE_SERVICES = 'services';

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_hsn_mappings')
            ->withTimestamps();
    }

    /**
     * Calculate tax for amount using HSN rates.
     */
    public function calculateTax(float $amount, bool $isInterstate = false): array
    {
        $result = [
            'taxable_amount' => $amount,
            'hsn_code' => $this->code,
            'gst_rate' => $this->gst_rate,
        ];

        if ($isInterstate) {
            $igstRate = $this->igst_rate ?? $this->gst_rate;
            $result['igst'] = round($amount * ($igstRate / 100), 2);
            $result['igst_rate'] = $igstRate;
            $result['total_tax'] = $result['igst'];
        } else {
            $cgstRate = $this->cgst_rate ?? ($this->gst_rate / 2);
            $sgstRate = $this->sgst_rate ?? ($this->gst_rate / 2);

            $result['cgst'] = round($amount * ($cgstRate / 100), 2);
            $result['cgst_rate'] = $cgstRate;
            $result['sgst'] = round($amount * ($sgstRate / 100), 2);
            $result['sgst_rate'] = $sgstRate;
            $result['total_tax'] = $result['cgst'] + $result['sgst'];
        }

        // Add cess if applicable
        if ($this->cess_rate && $this->cess_rate > 0) {
            $result['cess'] = round($amount * ($this->cess_rate / 100), 2);
            $result['cess_rate'] = $this->cess_rate;
            $result['total_tax'] += $result['cess'];
        }

        return $result;
    }

    /**
     * Search HSN codes.
     */
    public static function search(string $term)
    {
        return self::where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGoods($query)
    {
        return $query->where('type', self::TYPE_GOODS);
    }

    public function scopeServices($query)
    {
        return $query->where('type', self::TYPE_SERVICES);
    }
}
