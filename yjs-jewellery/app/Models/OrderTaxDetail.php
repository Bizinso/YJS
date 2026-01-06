<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Order Tax Detail Model
 *
 * Tax breakdown for orders.
 */
class OrderTaxDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'tax_rule_id',
        'tax_name',
        'tax_code',
        'tax_type',
        'taxable_amount',
        'rate',
        'tax_amount',
        'hsn_code',
        'is_exempt',
        'exemption_reason',
    ];

    protected $casts = [
        'taxable_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_exempt' => 'boolean',
    ];

    const TYPE_GST = 'gst';
    const TYPE_IGST = 'igst';
    const TYPE_CGST = 'cgst';
    const TYPE_SGST = 'sgst';
    const TYPE_VAT = 'vat';
    const TYPE_CESS = 'cess';
    const TYPE_CUSTOM = 'custom';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class);
    }

    /**
     * Create tax details from tax calculation.
     */
    public static function createFromCalculation(int $orderId, array $calculation, ?TaxRule $taxRule = null): array
    {
        $details = [];
        $taxableAmount = $calculation['taxable_amount'];
        $hsnCode = $calculation['hsn_code'] ?? null;

        // If IGST
        if (isset($calculation['igst'])) {
            $details[] = self::create([
                'order_id' => $orderId,
                'tax_rule_id' => $taxRule?->id,
                'tax_name' => 'IGST',
                'tax_code' => $taxRule?->code,
                'tax_type' => self::TYPE_IGST,
                'taxable_amount' => $taxableAmount,
                'rate' => $calculation['igst_rate'],
                'tax_amount' => $calculation['igst'],
                'hsn_code' => $hsnCode,
            ]);
        }

        // If CGST
        if (isset($calculation['cgst'])) {
            $details[] = self::create([
                'order_id' => $orderId,
                'tax_rule_id' => $taxRule?->id,
                'tax_name' => 'CGST',
                'tax_code' => $taxRule?->code,
                'tax_type' => self::TYPE_CGST,
                'taxable_amount' => $taxableAmount,
                'rate' => $calculation['cgst_rate'],
                'tax_amount' => $calculation['cgst'],
                'hsn_code' => $hsnCode,
            ]);
        }

        // If SGST
        if (isset($calculation['sgst'])) {
            $details[] = self::create([
                'order_id' => $orderId,
                'tax_rule_id' => $taxRule?->id,
                'tax_name' => 'SGST',
                'tax_code' => $taxRule?->code,
                'tax_type' => self::TYPE_SGST,
                'taxable_amount' => $taxableAmount,
                'rate' => $calculation['sgst_rate'],
                'tax_amount' => $calculation['sgst'],
                'hsn_code' => $hsnCode,
            ]);
        }

        // If CESS
        if (isset($calculation['cess'])) {
            $details[] = self::create([
                'order_id' => $orderId,
                'tax_rule_id' => $taxRule?->id,
                'tax_name' => 'Cess',
                'tax_code' => $taxRule?->code,
                'tax_type' => self::TYPE_CESS,
                'taxable_amount' => $taxableAmount,
                'rate' => $calculation['cess_rate'],
                'tax_amount' => $calculation['cess'],
                'hsn_code' => $hsnCode,
            ]);
        }

        return $details;
    }

    /**
     * Get tax summary by type for an order.
     */
    public static function getSummaryByOrder(int $orderId): array
    {
        return self::where('order_id', $orderId)
            ->selectRaw('tax_type, SUM(tax_amount) as total_amount')
            ->groupBy('tax_type')
            ->pluck('total_amount', 'tax_type')
            ->toArray();
    }
}
