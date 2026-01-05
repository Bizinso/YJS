<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Offer Bundle Model
 *
 * Defines combo/bundle offers with multiple products.
 */
class OfferBundle extends Model
{
    protected $table = 'offer_bundles';

    protected $fillable = [
        'offer_id',
        'bundle_name',
        'product_ids',
        'required_quantities',
        'bundle_price',
        'bundle_discount_percent',
        'max_bundles_per_order',
        'is_active',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'required_quantities' => 'array',
        'bundle_price' => 'decimal:2',
        'bundle_discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offers::class, 'offer_id');
    }

    /**
     * Get products in the bundle
     */
    public function getProducts()
    {
        return Product::whereIn('id', $this->product_ids ?? [])->get();
    }

    /**
     * Calculate bundle discount
     */
    public function calculateDiscount(array $cartItems): array
    {
        $bundleProductIds = $this->product_ids ?? [];
        $cartProductIds = array_column($cartItems, 'product_id');

        // Check if all bundle products are in cart
        $hasAllProducts = empty(array_diff($bundleProductIds, $cartProductIds));

        if (!$hasAllProducts) {
            return ['applicable' => false, 'reason' => 'Not all bundle products in cart'];
        }

        // Check quantities if required
        if ($this->required_quantities) {
            foreach ($this->required_quantities as $productId => $requiredQty) {
                $cartItem = collect($cartItems)->firstWhere('product_id', $productId);
                if (!$cartItem || $cartItem['quantity'] < $requiredQty) {
                    return ['applicable' => false, 'reason' => 'Insufficient quantity for bundle'];
                }
            }
        }

        // Calculate original total
        $originalTotal = 0;
        foreach ($cartItems as $item) {
            if (in_array($item['product_id'], $bundleProductIds)) {
                $originalTotal += $item['price'] * ($this->required_quantities[$item['product_id']] ?? 1);
            }
        }

        // Calculate discount
        if ($this->bundle_price) {
            $discount = max(0, $originalTotal - $this->bundle_price);
        } elseif ($this->bundle_discount_percent) {
            $discount = $originalTotal * ($this->bundle_discount_percent / 100);
        } else {
            $discount = 0;
        }

        return [
            'applicable' => true,
            'original_total' => $originalTotal,
            'bundle_price' => $this->bundle_price ?? ($originalTotal - $discount),
            'discount' => round($discount, 2),
        ];
    }

    /**
     * Scope for active bundles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
