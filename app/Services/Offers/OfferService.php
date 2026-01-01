<?php

namespace App\Services\Offers;

use App\Models\Cart;
use App\Models\Offers;
use App\Models\OfferUsage;
use App\Models\OrderOffer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * OFFER SERVICE - B2C Promotions
 * 
 * BACKWARD COMPATIBLE: Uses EXISTING offers table fields:
 *   - title (not 'name')
 *   - discount_type: 'flat', 'percent'
 *   - discount_amount, discount_percent, max_discount_amount
 *   - apply_on, apply_on_value (JSON)
 *   - valid_from, valid_to
 *   - coupon_code
 *   - details (JSON)
 *   - status: 'active', 'inactive', 'expired'
 * 
 * NOTE: B2B uses tier-based pricing, NOT this offer system
 */
class OfferService
{
    public const REJECTION_CODES = [
        'OFFER_NOT_FOUND' => 'Offer not found',
        'OFFER_INACTIVE' => 'Offer is not active',
        'OFFER_EXPIRED' => 'Offer has expired',
        'OFFER_NOT_STARTED' => 'Offer has not started yet',
        'MIN_CART_NOT_MET' => 'Minimum cart value not met',
        'MAX_CART_EXCEEDED' => 'Cart value exceeds maximum limit',
        'PRODUCT_NOT_APPLICABLE' => 'Offer not valid for products in cart',
        'CATEGORY_NOT_APPLICABLE' => 'Offer not valid for categories in cart',
        'NOT_NEW_CUSTOMER' => 'Offer is for new customers only',
        'GLOBAL_LIMIT_REACHED' => 'Offer usage limit has been reached',
        'USER_LIMIT_REACHED' => 'You have already used this offer',
        'COUPON_REQUIRED' => 'Coupon code is required',
        'INVALID_COUPON' => 'Invalid coupon code',
    ];

    /**
     * Get all applicable offers for cart
     * @param int $userId - Customer ID
     * @param float $cartTotal - Cart total in rupees
     * @param array $productIds - Product IDs in cart
     * @param array $categoryIds - Category IDs in cart
     */
    public function getApplicableOffers(int $userId, float $cartTotal, array $productIds = [], array $categoryIds = []): array
    {
        // Get active offers using EXISTING status field
        $offers = Offers::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now());
            })
            ->get();

        $applicableOffers = [];
        $unavailableOffers = [];

        foreach ($offers as $offer) {
            $check = $this->checkOfferApplicability($offer, $userId, $cartTotal, $productIds, $categoryIds);
            
            if ($check['applicable']) {
                $discount = $this->calculateDiscount($offer, $cartTotal);
                $applicableOffers[] = [
                    'id' => $offer->id,
                    'code' => $offer->coupon_code,
                    'title' => $offer->title, // Use EXISTING field
                    'description' => $offer->description,
                    'discount_type' => $offer->discount_type,
                    'discount_amount' => $offer->discount_amount,
                    'discount_percent' => $offer->discount_percent,
                    'max_discount_amount' => $offer->max_discount_amount,
                    'calculated_discount' => $discount,
                    'calculated_discount_display' => '₹' . number_format($discount, 2),
                    'has_coupon' => !empty($offer->coupon_code),
                    'valid_until' => $offer->valid_to?->format('d M Y'),
                ];
            } else {
                $unavailableOffers[] = [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'reason_code' => $check['reason_code'],
                    'reason' => $check['reason'],
                ];
            }
        }

        // Sort by discount (highest first)
        usort($applicableOffers, fn($a, $b) => $b['calculated_discount'] <=> $a['calculated_discount']);

        return [
            'applicable' => $applicableOffers,
            'unavailable' => $unavailableOffers,
            'cart_total' => $cartTotal,
        ];
    }

    /**
     * Apply offer to order
     * Returns discount amount in rupees
     */
    public function applyOffer(int $offerId, int $userId, float $cartTotal, array $productIds = [], array $categoryIds = [], ?string $couponCode = null): array
    {
        $offer = Offers::find($offerId);
        if (!$offer) {
            return ['success' => false, 'error' => 'Offer not found', 'error_code' => 'OFFER_NOT_FOUND'];
        }

        // Check applicability
        $check = $this->checkOfferApplicability($offer, $userId, $cartTotal, $productIds, $categoryIds);
        if (!$check['applicable']) {
            return ['success' => false, 'error' => $check['reason'], 'error_code' => $check['reason_code']];
        }

        // Verify coupon if offer has one
        if ($offer->coupon_code) {
            if (!$couponCode) {
                return ['success' => false, 'error' => 'Coupon code is required', 'error_code' => 'COUPON_REQUIRED'];
            }
            if (strtoupper($couponCode) !== strtoupper($offer->coupon_code)) {
                return ['success' => false, 'error' => 'Invalid coupon code', 'error_code' => 'INVALID_COUPON'];
            }
        }

        // Calculate discount
        $discount = $this->calculateDiscount($offer, $cartTotal);

        return [
            'success' => true,
            'offer_id' => $offer->id,
            'offer_title' => $offer->title,
            'coupon_code' => $offer->coupon_code,
            'discount_amount' => $discount,
            'discount_display' => '₹' . number_format($discount, 2),
            'new_total' => $cartTotal - $discount,
        ];
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(string $couponCode, int $userId, float $cartTotal, array $productIds = [], array $categoryIds = []): array
    {
        // Find by EXISTING coupon_code field
        $offer = Offers::where('coupon_code', strtoupper($couponCode))->first();
        
        if (!$offer) {
            return ['valid' => false, 'error' => 'Invalid coupon code', 'error_code' => 'INVALID_COUPON'];
        }

        $check = $this->checkOfferApplicability($offer, $userId, $cartTotal, $productIds, $categoryIds);
        
        if (!$check['applicable']) {
            return ['valid' => false, 'error' => $check['reason'], 'error_code' => $check['reason_code']];
        }

        $discount = $this->calculateDiscount($offer, $cartTotal);

        return [
            'valid' => true,
            'offer_id' => $offer->id,
            'offer_title' => $offer->title,
            'discount' => $discount,
            'discount_display' => '₹' . number_format($discount, 2),
        ];
    }

    /**
     * Check offer applicability
     */
    public function checkOfferApplicability(
        Offers $offer, 
        int $userId, 
        float $cartTotal, 
        array $productIds, 
        array $categoryIds
    ): array {
        // Active check using EXISTING status field
        if ($offer->status !== 'active') {
            return ['applicable' => false, 'reason_code' => 'OFFER_INACTIVE', 'reason' => self::REJECTION_CODES['OFFER_INACTIVE']];
        }

        // Date validity using EXISTING fields
        if ($offer->valid_from && $offer->valid_from > now()) {
            return ['applicable' => false, 'reason_code' => 'OFFER_NOT_STARTED', 'reason' => self::REJECTION_CODES['OFFER_NOT_STARTED']];
        }
        if ($offer->valid_to && $offer->valid_to < now()) {
            return ['applicable' => false, 'reason_code' => 'OFFER_EXPIRED', 'reason' => self::REJECTION_CODES['OFFER_EXPIRED']];
        }

        // Check apply_on rules using EXISTING fields
        $applyOn = $offer->apply_on;
        $applyOnValue = $offer->apply_on_value; // Already cast to array

        if ($applyOn === 'products' && !empty($applyOnValue)) {
            $hasApplicable = !empty(array_intersect($productIds, $applyOnValue));
            if (!$hasApplicable) {
                return ['applicable' => false, 'reason_code' => 'PRODUCT_NOT_APPLICABLE', 'reason' => self::REJECTION_CODES['PRODUCT_NOT_APPLICABLE']];
            }
        }

        if ($applyOn === 'categories' && !empty($applyOnValue)) {
            $hasApplicable = !empty(array_intersect($categoryIds, $applyOnValue));
            if (!$hasApplicable) {
                return ['applicable' => false, 'reason_code' => 'CATEGORY_NOT_APPLICABLE', 'reason' => self::REJECTION_CODES['CATEGORY_NOT_APPLICABLE']];
            }
        }

        // Check details JSON for min_cart_value using EXISTING field
        $details = $offer->details ?? []; // Already cast to array
        
        if (isset($details['min_cart_value']) && $cartTotal < $details['min_cart_value']) {
            $required = '₹' . number_format($details['min_cart_value'], 0);
            return ['applicable' => false, 'reason_code' => 'MIN_CART_NOT_MET', 'reason' => "Minimum cart value of {$required} required"];
        }

        // Check for first order offers
        if (isset($details['first_order_only']) && $details['first_order_only']) {
            $hasOrders = Order::where('customer_id', $userId)
                ->where('payment_status', 'paid')
                ->exists();
            if ($hasOrders) {
                return ['applicable' => false, 'reason_code' => 'NOT_NEW_CUSTOMER', 'reason' => self::REJECTION_CODES['NOT_NEW_CUSTOMER']];
            }
        }

        // Check global usage limit
        if (isset($details['max_usage_global'])) {
            $globalUsage = OfferUsage::where('offer_id', $offer->id)
                ->where('reversed', false)
                ->count();
            if ($globalUsage >= $details['max_usage_global']) {
                return ['applicable' => false, 'reason_code' => 'GLOBAL_LIMIT_REACHED', 'reason' => self::REJECTION_CODES['GLOBAL_LIMIT_REACHED']];
            }
        }

        // Check per-user usage limit
        if (isset($details['max_usage_per_user'])) {
            $userUsage = OfferUsage::where('offer_id', $offer->id)
                ->where('customer_id', $userId)
                ->where('reversed', false)
                ->count();
            if ($userUsage >= $details['max_usage_per_user']) {
                return ['applicable' => false, 'reason_code' => 'USER_LIMIT_REACHED', 'reason' => self::REJECTION_CODES['USER_LIMIT_REACHED']];
            }
        }

        return ['applicable' => true];
    }

    /**
     * Calculate discount using EXISTING fields
     */
    public function calculateDiscount(Offers $offer, float $cartTotal): float
    {
        $discount = 0;

        // Use EXISTING discount_type enum ('flat', 'percent')
        if ($offer->discount_type === 'percent' && $offer->discount_percent) {
            $discount = $cartTotal * ($offer->discount_percent / 100);
        } elseif ($offer->discount_type === 'flat' && $offer->discount_amount) {
            $discount = (float) $offer->discount_amount;
        }

        // Apply max discount cap using EXISTING field
        if ($offer->max_discount_amount && $discount > $offer->max_discount_amount) {
            $discount = (float) $offer->max_discount_amount;
        }

        // Can't exceed cart total
        $discount = min($discount, $cartTotal);

        return round($discount, 2);
    }

    /**
     * Snapshot offer for order (freeze offer details at order time)
     */
    public function snapshotOfferForOrder(Order $order, int $offerId, float $discountApplied, ?string $couponCodeUsed = null): ?OrderOffer
    {
        $offer = Offers::find($offerId);
        if (!$offer) {
            return null;
        }

        // Record usage
        $this->recordUsage($offer, $order->id, $order->customer_id, $discountApplied);

        // Create immutable snapshot
        return OrderOffer::create([
            'order_id' => $order->id,
            'offer_id' => $offer->id,
            'offer_code' => $offer->coupon_code,
            'offer_title' => $offer->title, // Use EXISTING field
            'offer_type_id' => $offer->offer_type_id,
            'discount_type' => $offer->discount_type,
            'discount_amount' => $offer->discount_amount,
            'discount_percent' => $offer->discount_percent,
            'applied_discount' => $discountApplied,
            'coupon_code_used' => $couponCodeUsed,
            'offer_snapshot' => json_encode($offer->toArray()),
            'applied_at' => now(),
        ]);
    }

    /**
     * Record offer usage for tracking
     */
    private function recordUsage(Offers $offer, int $orderId, ?int $customerId, float $discountAmount): void
    {
        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'discount_amount' => $discountAmount,
            'used_at' => now(),
        ]);
    }

    /**
     * Rollback offer usage (for order cancellation)
     */
    public function rollbackOfferUsage(int $orderId, string $reason = 'order_cancelled'): void
    {
        $usages = OfferUsage::where('order_id', $orderId)
            ->where('reversed', false)
            ->get();

        foreach ($usages as $usage) {
            $usage->update([
                'reversed' => true,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);
        }
    }

    /**
     * Get offer by coupon code
     */
    public function getOfferByCoupon(string $couponCode): ?Offers
    {
        return Offers::where('coupon_code', strtoupper($couponCode))
            ->where('status', 'active')
            ->first();
    }
}
