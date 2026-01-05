<?php

namespace App\Services\Offers;

use App\Models\Offers;
use App\Models\OfferUsage;
use App\Models\OrderOffer;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\FlashSaleInventory;
use App\Models\OfferBundle;
use App\Models\OrderFreeGift;
use App\Models\LoyaltyPoints;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Advanced Offer Service
 *
 * Handles all types of offers including:
 * - Basic discounts (flat, percent, category, product)
 * - BOGO (Buy One Get One)
 * - Buy X Get Y
 * - Combo/Bundle offers
 * - Payment method offers
 * - Flash sales
 * - Free gifts
 * - Tiered discounts
 * - Birthday/Anniversary offers
 * - Nth item discounts
 * - Offer stacking
 */
class AdvancedOfferService
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
        'FLASH_SALE_SOLD_OUT' => 'Flash sale items sold out',
        'PAYMENT_METHOD_NOT_ELIGIBLE' => 'Offer not valid for selected payment method',
        'BANK_NOT_ELIGIBLE' => 'Offer not valid for selected bank',
        'NOT_BIRTHDAY_ELIGIBLE' => 'Birthday offer not applicable',
        'BOGO_QUANTITY_NOT_MET' => 'Buy quantity not met for BOGO offer',
        'COMBO_PRODUCTS_MISSING' => 'All combo products must be in cart',
        'NOT_STACKABLE' => 'This offer cannot be combined with other offers',
        'USER_NOT_TARGETED' => 'Offer not available for your account',
    ];

    /**
     * Get all applicable offers for a cart
     */
    public function getApplicableOffers(
        int $userId,
        array $cartItems,
        float $cartTotal,
        array $productIds = [],
        array $categoryIds = [],
        ?string $paymentMethod = null,
        ?string $bank = null
    ): array {
        $offers = Offers::active()->get();

        $applicableOffers = [];
        $unavailableOffers = [];
        $user = User::find($userId);

        foreach ($offers as $offer) {
            $check = $this->checkOfferApplicability(
                $offer,
                $userId,
                $cartItems,
                $cartTotal,
                $productIds,
                $categoryIds,
                $paymentMethod,
                $bank,
                $user
            );

            if ($check['applicable']) {
                $discount = $this->calculateDiscount($offer, $cartItems, $cartTotal, $productIds);
                $applicableOffers[] = $this->formatOfferResponse($offer, $discount, $cartTotal);
            } else {
                $unavailableOffers[] = [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'reason_code' => $check['reason_code'],
                    'reason' => $check['reason'],
                ];
            }
        }

        // Sort by discount amount (highest first)
        usort($applicableOffers, fn($a, $b) => $b['calculated_discount'] <=> $a['calculated_discount']);

        // Get best offer
        $bestOffer = $applicableOffers[0] ?? null;

        // Get stackable offers if applicable
        $stackableOffers = $this->getStackableOffers($applicableOffers);

        return [
            'applicable' => $applicableOffers,
            'unavailable' => $unavailableOffers,
            'best_offer' => $bestOffer,
            'stackable_offers' => $stackableOffers,
            'cart_total' => $cartTotal,
        ];
    }

    /**
     * Format offer response
     */
    private function formatOfferResponse(Offers $offer, array $discountInfo, float $cartTotal): array
    {
        return [
            'id' => $offer->id,
            'code' => $offer->coupon_code,
            'title' => $offer->title,
            'description' => $offer->description,
            'offer_type' => $offer->offerType?->offer_type,
            'offer_category' => $offer->offer_category,
            'discount_type' => $offer->discount_type,
            'discount_amount' => $offer->discount_amount,
            'discount_percent' => $offer->discount_percent,
            'max_discount_amount' => $offer->max_discount_amount,
            'calculated_discount' => $discountInfo['discount'],
            'calculated_discount_display' => '₹' . number_format($discountInfo['discount'], 2),
            'discount_breakdown' => $discountInfo['breakdown'] ?? null,
            'has_coupon' => !empty($offer->coupon_code),
            'valid_until' => $offer->valid_to?->format('d M Y'),
            'is_flash_sale' => $offer->is_flash_sale,
            'flash_remaining' => $offer->remaining_flash_stock,
            'is_bogo' => $offer->is_bogo,
            'is_combo' => $offer->is_combo,
            'has_free_gift' => $offer->has_free_gift,
            'free_gift_products' => $offer->free_gift_products,
            'is_stackable' => $offer->is_stackable,
            'badge_text' => $offer->badge_text,
            'badge_color' => $offer->badge_color,
            'terms_conditions' => $offer->terms_conditions,
            'new_total' => $cartTotal - $discountInfo['discount'],
        ];
    }

    /**
     * Check offer applicability
     */
    public function checkOfferApplicability(
        Offers $offer,
        int $userId,
        array $cartItems,
        float $cartTotal,
        array $productIds,
        array $categoryIds,
        ?string $paymentMethod = null,
        ?string $bank = null,
        ?User $user = null
    ): array {
        // Basic status check
        if ($offer->status !== 'active') {
            return ['applicable' => false, 'reason_code' => 'OFFER_INACTIVE', 'reason' => self::REJECTION_CODES['OFFER_INACTIVE']];
        }

        // Date validity
        if ($offer->valid_from && $offer->valid_from > now()) {
            return ['applicable' => false, 'reason_code' => 'OFFER_NOT_STARTED', 'reason' => self::REJECTION_CODES['OFFER_NOT_STARTED']];
        }
        if ($offer->valid_to && $offer->valid_to < now()) {
            return ['applicable' => false, 'reason_code' => 'OFFER_EXPIRED', 'reason' => self::REJECTION_CODES['OFFER_EXPIRED']];
        }

        // Flash sale stock check
        if ($offer->is_flash_sale && $offer->flash_stock_limit) {
            if ($offer->flash_sold_count >= $offer->flash_stock_limit) {
                return ['applicable' => false, 'reason_code' => 'FLASH_SALE_SOLD_OUT', 'reason' => self::REJECTION_CODES['FLASH_SALE_SOLD_OUT']];
            }
        }

        // Target user check
        if (!empty($offer->target_user_ids) && !in_array($userId, $offer->target_user_ids)) {
            return ['applicable' => false, 'reason_code' => 'USER_NOT_TARGETED', 'reason' => self::REJECTION_CODES['USER_NOT_TARGETED']];
        }

        // Birthday offer check
        if ($offer->is_birthday_offer) {
            $birthdayCheck = $this->checkBirthdayEligibility($user, $offer);
            if (!$birthdayCheck['eligible']) {
                return ['applicable' => false, 'reason_code' => 'NOT_BIRTHDAY_ELIGIBLE', 'reason' => $birthdayCheck['reason']];
            }
        }

        // Apply_on rules (products/categories)
        $applyOn = $offer->apply_on;
        $applyOnValue = $offer->apply_on_value ?? [];

        if ($applyOn === 'products' && !empty($applyOnValue)) {
            if (empty(array_intersect($productIds, $applyOnValue))) {
                return ['applicable' => false, 'reason_code' => 'PRODUCT_NOT_APPLICABLE', 'reason' => self::REJECTION_CODES['PRODUCT_NOT_APPLICABLE']];
            }
        }

        if ($applyOn === 'categories' && !empty($applyOnValue)) {
            if (empty(array_intersect($categoryIds, $applyOnValue))) {
                return ['applicable' => false, 'reason_code' => 'CATEGORY_NOT_APPLICABLE', 'reason' => self::REJECTION_CODES['CATEGORY_NOT_APPLICABLE']];
            }
        }

        // Min cart value check
        $details = $offer->details ?? [];
        if (isset($details['min_cart_value']) && $cartTotal < $details['min_cart_value']) {
            $required = '₹' . number_format($details['min_cart_value'], 0);
            return ['applicable' => false, 'reason_code' => 'MIN_CART_NOT_MET', 'reason' => "Minimum cart value of {$required} required"];
        }

        // BOGO quantity check
        if ($offer->buy_quantity) {
            $bogoCheck = $this->checkBogoEligibility($offer, $cartItems, $productIds);
            if (!$bogoCheck['eligible']) {
                return ['applicable' => false, 'reason_code' => 'BOGO_QUANTITY_NOT_MET', 'reason' => $bogoCheck['reason']];
            }
        }

        // Combo products check
        if (!empty($offer->combo_products)) {
            if (!$this->hasAllComboProducts($offer->combo_products, $productIds)) {
                return ['applicable' => false, 'reason_code' => 'COMBO_PRODUCTS_MISSING', 'reason' => self::REJECTION_CODES['COMBO_PRODUCTS_MISSING']];
            }
        }

        // Payment method check
        if ($paymentMethod && !empty($offer->payment_methods)) {
            if (!in_array($paymentMethod, $offer->payment_methods)) {
                return ['applicable' => false, 'reason_code' => 'PAYMENT_METHOD_NOT_ELIGIBLE', 'reason' => self::REJECTION_CODES['PAYMENT_METHOD_NOT_ELIGIBLE']];
            }
        }

        // Bank check
        if ($bank && !empty($offer->banks)) {
            if (!in_array(strtolower($bank), array_map('strtolower', $offer->banks))) {
                return ['applicable' => false, 'reason_code' => 'BANK_NOT_ELIGIBLE', 'reason' => self::REJECTION_CODES['BANK_NOT_ELIGIBLE']];
            }
        }

        // First order check
        if (isset($details['first_order_only']) && $details['first_order_only']) {
            $hasOrders = Order::where('customer_id', $userId)
                ->where('payment_status', 'paid')
                ->exists();
            if ($hasOrders) {
                return ['applicable' => false, 'reason_code' => 'NOT_NEW_CUSTOMER', 'reason' => self::REJECTION_CODES['NOT_NEW_CUSTOMER']];
            }
        }

        // Global usage limit
        if (isset($details['max_usage_global'])) {
            $globalUsage = OfferUsage::where('offer_id', $offer->id)
                ->where('reversed', false)
                ->count();
            if ($globalUsage >= $details['max_usage_global']) {
                return ['applicable' => false, 'reason_code' => 'GLOBAL_LIMIT_REACHED', 'reason' => self::REJECTION_CODES['GLOBAL_LIMIT_REACHED']];
            }
        }

        // Per-user usage limit
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
     * Calculate discount based on offer type
     */
    public function calculateDiscount(
        Offers $offer,
        array $cartItems,
        float $cartTotal,
        array $productIds = []
    ): array {
        $discount = 0;
        $breakdown = [];

        // Check for tiered discount first
        if (!empty($offer->tier_rules)) {
            return $this->calculateTieredDiscount($offer, $cartTotal);
        }

        // Check for BOGO/Buy X Get Y
        if ($offer->buy_quantity && $offer->get_quantity) {
            return $this->calculateBogoDiscount($offer, $cartItems, $productIds);
        }

        // Check for combo discount
        if (!empty($offer->combo_products) && $offer->combo_price) {
            return $this->calculateComboDiscount($offer, $cartItems);
        }

        // Check for Nth item discount
        if ($offer->nth_item_number && $offer->nth_item_discount_percent) {
            return $this->calculateNthItemDiscount($offer, $cartItems);
        }

        // Standard discount calculation
        if ($offer->discount_type === 'percent' && $offer->discount_percent) {
            $discount = $cartTotal * ($offer->discount_percent / 100);
            $breakdown[] = [
                'type' => 'percent',
                'rate' => $offer->discount_percent,
                'amount' => $discount,
            ];
        } elseif ($offer->discount_type === 'flat' && $offer->discount_amount) {
            $discount = (float) $offer->discount_amount;
            $breakdown[] = [
                'type' => 'flat',
                'amount' => $discount,
            ];
        }

        // Apply max discount cap
        if ($offer->max_discount_amount && $discount > $offer->max_discount_amount) {
            $discount = (float) $offer->max_discount_amount;
            $breakdown[] = ['type' => 'capped', 'max' => $offer->max_discount_amount];
        }

        // Can't exceed cart total
        $discount = min($discount, $cartTotal);

        return [
            'discount' => round($discount, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate tiered/slab discount
     */
    private function calculateTieredDiscount(Offers $offer, float $cartTotal): array
    {
        $tierRules = $offer->tier_rules ?? [];
        $discount = 0;
        $appliedTier = null;

        foreach ($tierRules as $tier) {
            $minValue = $tier['min_value'] ?? 0;
            $maxValue = $tier['max_value'] ?? PHP_INT_MAX;

            if ($cartTotal >= $minValue && $cartTotal <= $maxValue) {
                if (($tier['discount_type'] ?? 'percent') === 'percent') {
                    $discount = $cartTotal * (($tier['discount_value'] ?? 0) / 100);
                } else {
                    $discount = $tier['discount_value'] ?? 0;
                }
                $appliedTier = $tier;
                break;
            }
        }

        if ($offer->max_discount_amount && $discount > $offer->max_discount_amount) {
            $discount = (float) $offer->max_discount_amount;
        }

        return [
            'discount' => round(min($discount, $cartTotal), 2),
            'breakdown' => [
                ['type' => 'tiered', 'tier' => $appliedTier, 'amount' => $discount],
            ],
        ];
    }

    /**
     * Calculate BOGO/Buy X Get Y discount
     */
    private function calculateBogoDiscount(Offers $offer, array $cartItems, array $productIds): array
    {
        $buyQty = $offer->buy_quantity;
        $getQty = $offer->get_quantity;
        $getDiscount = $offer->get_discount_percent ?? 100; // Default 100% = free
        $getProducts = $offer->get_products ?? [];

        $discount = 0;
        $breakdown = [];

        // Get applicable products
        $applicableProductIds = $offer->apply_on === 'products' ? ($offer->apply_on_value ?? $productIds) : $productIds;

        foreach ($cartItems as $item) {
            if (!in_array($item['product_id'], $applicableProductIds)) {
                continue;
            }

            $totalQty = $item['quantity'];
            $sets = floor($totalQty / ($buyQty + $getQty));

            if ($sets > 0) {
                $freeItems = $sets * $getQty;
                $itemDiscount = ($item['price'] * $freeItems) * ($getDiscount / 100);
                $discount += $itemDiscount;

                $breakdown[] = [
                    'type' => 'bogo',
                    'product_id' => $item['product_id'],
                    'buy_qty' => $buyQty,
                    'get_qty' => $getQty,
                    'get_discount' => $getDiscount,
                    'sets' => $sets,
                    'amount' => $itemDiscount,
                ];
            }
        }

        return [
            'discount' => round($discount, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate combo/bundle discount
     */
    private function calculateComboDiscount(Offers $offer, array $cartItems): array
    {
        $comboProducts = $offer->combo_products ?? [];
        $comboPrice = $offer->combo_price;

        // Calculate original total of combo products
        $originalTotal = 0;
        foreach ($cartItems as $item) {
            if (in_array($item['product_id'], $comboProducts)) {
                $originalTotal += $item['price'] * $item['quantity'];
            }
        }

        $discount = max(0, $originalTotal - $comboPrice);

        return [
            'discount' => round($discount, 2),
            'breakdown' => [
                [
                    'type' => 'combo',
                    'original_total' => $originalTotal,
                    'combo_price' => $comboPrice,
                    'amount' => $discount,
                ],
            ],
        ];
    }

    /**
     * Calculate Nth item discount
     */
    private function calculateNthItemDiscount(Offers $offer, array $cartItems): array
    {
        $nthItem = $offer->nth_item_number;
        $discountPercent = $offer->nth_item_discount_percent;

        // Sort items by price (cheapest items get discount)
        $sortedItems = collect($cartItems)->sortBy('price')->values()->all();

        $discount = 0;
        $breakdown = [];
        $itemCount = 0;

        foreach ($sortedItems as $item) {
            for ($i = 0; $i < $item['quantity']; $i++) {
                $itemCount++;
                if ($itemCount % $nthItem === 0) {
                    $itemDiscount = $item['price'] * ($discountPercent / 100);
                    $discount += $itemDiscount;
                    $breakdown[] = [
                        'type' => 'nth_item',
                        'item_number' => $itemCount,
                        'product_id' => $item['product_id'],
                        'discount_percent' => $discountPercent,
                        'amount' => $itemDiscount,
                    ];
                }
            }
        }

        return [
            'discount' => round($discount, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Check BOGO eligibility
     */
    private function checkBogoEligibility(Offers $offer, array $cartItems, array $productIds): array
    {
        $buyQty = $offer->buy_quantity;
        $getQty = $offer->get_quantity;
        $requiredQty = $buyQty + $getQty;

        $applicableProductIds = $offer->apply_on === 'products' ? ($offer->apply_on_value ?? $productIds) : $productIds;

        foreach ($cartItems as $item) {
            if (in_array($item['product_id'], $applicableProductIds)) {
                if ($item['quantity'] >= $requiredQty) {
                    return ['eligible' => true];
                }
            }
        }

        return [
            'eligible' => false,
            'reason' => "Add {$requiredQty} items to qualify for Buy {$buyQty} Get {$getQty}",
        ];
    }

    /**
     * Check if cart has all combo products
     */
    private function hasAllComboProducts(array $comboProducts, array $cartProductIds): bool
    {
        return empty(array_diff($comboProducts, $cartProductIds));
    }

    /**
     * Check birthday eligibility
     */
    private function checkBirthdayEligibility(?User $user, Offers $offer): array
    {
        if (!$user || !$user->date_of_birth) {
            return ['eligible' => false, 'reason' => 'Date of birth not set'];
        }

        $birthday = Carbon::parse($user->date_of_birth);
        $today = Carbon::today();

        // Set birthday to current year
        $birthdayThisYear = $birthday->copy()->year($today->year);

        // If birthday has passed this year, check next year's birthday too
        if ($birthdayThisYear < $today->copy()->subDays($offer->birthday_days_after ?? 7)) {
            $birthdayThisYear->addYear();
        }

        $windowStart = $birthdayThisYear->copy()->subDays($offer->birthday_days_before ?? 0);
        $windowEnd = $birthdayThisYear->copy()->addDays($offer->birthday_days_after ?? 7);

        if ($today >= $windowStart && $today <= $windowEnd) {
            return ['eligible' => true];
        }

        return [
            'eligible' => false,
            'reason' => 'Birthday offer is only valid during your birthday window',
        ];
    }

    /**
     * Get stackable offers
     */
    private function getStackableOffers(array $applicableOffers): array
    {
        return array_filter($applicableOffers, fn($offer) => $offer['is_stackable'] ?? false);
    }

    /**
     * Apply multiple stacked offers
     */
    public function applyStackedOffers(
        array $offerIds,
        int $userId,
        array $cartItems,
        float $cartTotal,
        array $productIds = [],
        array $categoryIds = []
    ): array {
        $offers = Offers::whereIn('id', $offerIds)->orderBy('stack_priority', 'desc')->get();

        $totalDiscount = 0;
        $appliedOffers = [];
        $remainingTotal = $cartTotal;

        foreach ($offers as $offer) {
            // Check if offer can stack with already applied offers
            if (!$this->canStackWith($offer, collect($appliedOffers)->pluck('offer_id')->toArray())) {
                continue;
            }

            $check = $this->checkOfferApplicability(
                $offer,
                $userId,
                $cartItems,
                $remainingTotal,
                $productIds,
                $categoryIds
            );

            if ($check['applicable']) {
                $discountInfo = $this->calculateDiscount($offer, $cartItems, $remainingTotal, $productIds);
                $discount = $discountInfo['discount'];

                $totalDiscount += $discount;
                $remainingTotal -= $discount;

                $appliedOffers[] = [
                    'offer_id' => $offer->id,
                    'title' => $offer->title,
                    'discount' => $discount,
                    'priority' => $offer->stack_priority,
                ];
            }
        }

        return [
            'success' => true,
            'total_discount' => round($totalDiscount, 2),
            'applied_offers' => $appliedOffers,
            'new_total' => round($cartTotal - $totalDiscount, 2),
        ];
    }

    /**
     * Check if offer can stack with other offers
     */
    private function canStackWith(Offers $offer, array $appliedOfferIds): bool
    {
        if (!$offer->is_stackable) {
            return empty($appliedOfferIds);
        }

        // Check exclusive_with
        if (!empty($offer->exclusive_with)) {
            if (!empty(array_intersect($appliedOfferIds, $offer->exclusive_with))) {
                return false;
            }
        }

        // Check stackable_with (if specified, only stack with those)
        if (!empty($offer->stackable_with)) {
            if (!empty(array_diff($appliedOfferIds, $offer->stackable_with))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate and apply coupon code
     */
    public function validateCoupon(
        string $couponCode,
        int $userId,
        array $cartItems,
        float $cartTotal,
        array $productIds = [],
        array $categoryIds = []
    ): array {
        $offer = Offers::where('coupon_code', strtoupper($couponCode))
            ->where('status', 'active')
            ->first();

        if (!$offer) {
            return ['valid' => false, 'error' => 'Invalid coupon code', 'error_code' => 'INVALID_COUPON'];
        }

        $check = $this->checkOfferApplicability(
            $offer,
            $userId,
            $cartItems,
            $cartTotal,
            $productIds,
            $categoryIds
        );

        if (!$check['applicable']) {
            return ['valid' => false, 'error' => $check['reason'], 'error_code' => $check['reason_code']];
        }

        $discountInfo = $this->calculateDiscount($offer, $cartItems, $cartTotal, $productIds);

        return [
            'valid' => true,
            'offer_id' => $offer->id,
            'offer_title' => $offer->title,
            'discount' => $discountInfo['discount'],
            'discount_display' => '₹' . number_format($discountInfo['discount'], 2),
            'breakdown' => $discountInfo['breakdown'],
            'free_gifts' => $offer->free_gift_products,
        ];
    }

    /**
     * Apply offer to order and record usage
     */
    public function applyOfferToOrder(
        Order $order,
        int $offerId,
        float $discountApplied,
        ?string $couponCode = null
    ): ?OrderOffer {
        $offer = Offers::find($offerId);
        if (!$offer) {
            return null;
        }

        // Record usage
        OfferUsage::create([
            'offer_id' => $offer->id,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'discount_amount' => $discountApplied,
            'used_at' => now(),
        ]);

        // Update offer stats
        $offer->addDiscountGiven($discountApplied);
        if ($offer->is_flash_sale) {
            $offer->incrementFlashSold();
        }

        // Create order offer snapshot
        return OrderOffer::create([
            'order_id' => $order->id,
            'offer_id' => $offer->id,
            'offer_code' => $offer->coupon_code,
            'offer_title' => $offer->title,
            'offer_type_id' => $offer->offer_type_id,
            'discount_type' => $offer->discount_type,
            'discount_amount' => $offer->discount_amount,
            'discount_percent' => $offer->discount_percent,
            'applied_discount' => $discountApplied,
            'coupon_code_used' => $couponCode,
            'offer_snapshot' => json_encode($offer->toArray()),
            'applied_at' => now(),
        ]);
    }

    /**
     * Add free gifts to order
     */
    public function addFreeGiftsToOrder(Order $order, Offers $offer): array
    {
        $freeGifts = [];

        if (empty($offer->free_gift_products)) {
            return $freeGifts;
        }

        foreach ($offer->free_gift_products as $productId) {
            $product = Product::find($productId);
            if (!$product) {
                continue;
            }

            $gift = OrderFreeGift::create([
                'order_id' => $order->id,
                'offer_id' => $offer->id,
                'product_id' => $productId,
                'quantity' => $offer->free_gift_quantity ?? 1,
                'gift_value' => $product->base_price ?? 0,
            ]);

            $freeGifts[] = $gift;
        }

        return $freeGifts;
    }

    /**
     * Get flash sales
     */
    public function getFlashSales(): Collection
    {
        return Offers::active()
            ->flashSales()
            ->orderBy('valid_to')
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'description' => $offer->description,
                    'discount_type' => $offer->discount_type,
                    'discount_amount' => $offer->discount_amount,
                    'discount_percent' => $offer->discount_percent,
                    'ends_at' => $offer->valid_to,
                    'ends_at_formatted' => $offer->valid_to?->format('d M Y H:i'),
                    'stock_limit' => $offer->flash_stock_limit,
                    'sold_count' => $offer->flash_sold_count,
                    'remaining' => $offer->remaining_flash_stock,
                    'sold_percentage' => $offer->flash_stock_limit > 0
                        ? round(($offer->flash_sold_count / $offer->flash_stock_limit) * 100, 1)
                        : 0,
                    'badge_text' => $offer->badge_text ?? 'FLASH SALE',
                    'badge_color' => $offer->badge_color ?? '#ff4444',
                    'products' => $offer->apply_on_value,
                ];
            });
    }

    /**
     * Get birthday offers for user
     */
    public function getBirthdayOffersForUser(User $user): Collection
    {
        if (!$user->date_of_birth) {
            return collect();
        }

        return Offers::active()
            ->birthdayOffers()
            ->get()
            ->filter(function ($offer) use ($user) {
                $check = $this->checkBirthdayEligibility($user, $offer);
                return $check['eligible'];
            })
            ->values();
    }

    /**
     * Rollback offer usage for cancelled orders
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

            // Revert flash sale count
            $offer = Offers::find($usage->offer_id);
            if ($offer && $offer->is_flash_sale) {
                $offer->decrement('flash_sold_count');
            }
        }
    }
}
