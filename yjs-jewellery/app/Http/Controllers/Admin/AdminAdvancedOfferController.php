<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offers;
use App\Models\offerTypeMaster;
use App\Models\OfferBundle;
use App\Models\FlashSaleInventory;
use App\Models\OfferUsage;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin Advanced Offer Controller
 *
 * Comprehensive offer management for all offer types including:
 * - BOGO, Buy X Get Y
 * - Combo/Bundle offers
 * - Flash sales
 * - Payment method offers
 * - Birthday/Occasion offers
 * - Free gift offers
 * - Tiered discounts
 */
class AdminAdvancedOfferController extends Controller
{
    /**
     * Get all offer types.
     */
    public function getOfferTypes(): JsonResponse
    {
        $types = offerTypeMaster::where('status', 'A')
            ->orderBy('offer_type')
            ->get()
            ->map(fn($type) => [
                'id' => $type->id,
                'offer_type' => $type->offer_type,
                'description' => $type->description,
                'options' => $type->offer_type_option,
                'apply_to' => $type->apply_to,
            ]);

        // Group by category
        $grouped = [
            'promotional' => $types->filter(fn($t) => in_array($t['offer_type'], [
                'flat_discount', 'percentage_discount', 'category_discount', 'product_discount',
                'min_cart_value', 'tiered_discount', 'free_shipping', 'payment_method',
                'bank_offer', 'emi_offer', 'fixed_price',
            ])),
            'quantity_based' => $types->filter(fn($t) => in_array($t['offer_type'], [
                'bogo', 'buy_x_get_y', 'combo_offer', 'bundle_discount',
                'nth_item_discount', 'flash_sale', 'quantity_break',
            ])),
            'audience_based' => $types->filter(fn($t) => in_array($t['offer_type'], [
                'first_order', 'birthday_offer', 'anniversary_offer', 'referral_offer',
                'loyalty_reward', 'vip_exclusive', 'festival_offer', 'win_back', 'ugc_reward',
            ])),
            'coupon_based' => $types->filter(fn($t) => in_array($t['offer_type'], [
                'public_coupon', 'private_coupon', 'time_bound_coupon', 'stackable_coupon',
                'free_gift_coupon', 'influencer_coupon',
            ])),
            'special' => $types->filter(fn($t) => in_array($t['offer_type'], [
                'free_gift', 'gift_with_choice', 'price_drop_alert', 'cart_abandonment',
            ])),
        ];

        return response()->json([
            'success' => true,
            'offer_types' => $grouped,
            'all_types' => $types,
        ]);
    }

    /**
     * Create advanced offer.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'offer_type_id' => 'required|exists:offer_type_masters,id',
            'offer_category' => 'nullable|string|in:promotional,quantity_based,audience_based,coupon_based,special',

            // Basic discount
            'discount_type' => 'nullable|in:flat,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',

            // BOGO / Buy X Get Y
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'get_discount_percent' => 'nullable|numeric|min:0|max:100',
            'get_products' => 'nullable|array',

            // Combo
            'combo_products' => 'nullable|array',
            'combo_price' => 'nullable|numeric|min:0',

            // Payment method
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => 'in:upi,card,netbanking,cod,wallet',
            'card_networks' => 'nullable|array',
            'banks' => 'nullable|array',

            // Flash sale
            'is_flash_sale' => 'nullable|boolean',
            'flash_stock_limit' => 'nullable|integer|min:1',

            // Free gift
            'free_gift_products' => 'nullable|array',
            'free_gift_quantity' => 'nullable|integer|min:1',

            // Tiered discount
            'tier_rules' => 'nullable|array',
            'tier_rules.*.min_value' => 'required_with:tier_rules|numeric|min:0',
            'tier_rules.*.max_value' => 'nullable|numeric|min:0',
            'tier_rules.*.discount_type' => 'required_with:tier_rules|in:flat,percent',
            'tier_rules.*.discount_value' => 'required_with:tier_rules|numeric|min:0',

            // Audience targeting
            'target_user_ids' => 'nullable|array',
            'target_user_tags' => 'nullable|array',
            'is_birthday_offer' => 'nullable|boolean',
            'birthday_days_before' => 'nullable|integer|min:0|max:30',
            'birthday_days_after' => 'nullable|integer|min:0|max:30',

            // Referral
            'is_referral_offer' => 'nullable|boolean',
            'referrer_reward' => 'nullable|numeric|min:0',
            'referee_discount' => 'nullable|numeric|min:0',

            // Stacking
            'is_stackable' => 'nullable|boolean',
            'stackable_with' => 'nullable|array',
            'exclusive_with' => 'nullable|array',
            'stack_priority' => 'nullable|integer|min:0|max:100',

            // Nth item
            'nth_item_number' => 'nullable|integer|min:2',
            'nth_item_discount_percent' => 'nullable|numeric|min:0|max:100',

            // Display
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
            'banner_image' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string|max:2000',

            // Apply on
            'apply_on' => 'nullable|string|in:cart,products,categories,shipping,payment',
            'apply_on_value' => 'nullable|array',

            // Validity
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'coupon_code' => 'nullable|string|max:50|unique:offers,coupon_code',
            'status' => 'nullable|in:active,inactive',

            // Usage limits (in details)
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_cart_value' => 'nullable|numeric|min:0',
            'max_usage_global' => 'nullable|integer|min:1',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'first_order_only' => 'nullable|boolean',
        ]);

        // Build details JSON
        $details = [];
        if (isset($validated['min_cart_value'])) {
            $details['min_cart_value'] = $validated['min_cart_value'];
        }
        if (isset($validated['max_cart_value'])) {
            $details['max_cart_value'] = $validated['max_cart_value'];
        }
        if (isset($validated['max_usage_global'])) {
            $details['max_usage_global'] = $validated['max_usage_global'];
        }
        if (isset($validated['max_usage_per_user'])) {
            $details['max_usage_per_user'] = $validated['max_usage_per_user'];
        }
        if (isset($validated['first_order_only'])) {
            $details['first_order_only'] = $validated['first_order_only'];
        }

        // Create offer
        $offer = Offers::create([
            ...$validated,
            'details' => $details,
            'coupon_code' => isset($validated['coupon_code'])
                ? strtoupper($validated['coupon_code'])
                : null,
            'created_by' => auth()->id(),
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Offer created successfully',
            'offer' => $offer,
        ], 201);
    }

    /**
     * Update advanced offer.
     */
    public function update(Request $request, Offers $offer): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:1000',
            'offer_type_id' => 'sometimes|exists:offer_type_masters,id',
            'offer_category' => 'nullable|string',
            'discount_type' => 'nullable|in:flat,percent',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'buy_quantity' => 'nullable|integer|min:1',
            'get_quantity' => 'nullable|integer|min:1',
            'get_discount_percent' => 'nullable|numeric|min:0|max:100',
            'get_products' => 'nullable|array',
            'combo_products' => 'nullable|array',
            'combo_price' => 'nullable|numeric|min:0',
            'payment_methods' => 'nullable|array',
            'card_networks' => 'nullable|array',
            'banks' => 'nullable|array',
            'is_flash_sale' => 'nullable|boolean',
            'flash_stock_limit' => 'nullable|integer|min:1',
            'free_gift_products' => 'nullable|array',
            'free_gift_quantity' => 'nullable|integer|min:1',
            'tier_rules' => 'nullable|array',
            'target_user_ids' => 'nullable|array',
            'target_user_tags' => 'nullable|array',
            'is_birthday_offer' => 'nullable|boolean',
            'birthday_days_before' => 'nullable|integer|min:0|max:30',
            'birthday_days_after' => 'nullable|integer|min:0|max:30',
            'is_referral_offer' => 'nullable|boolean',
            'referrer_reward' => 'nullable|numeric|min:0',
            'referee_discount' => 'nullable|numeric|min:0',
            'is_stackable' => 'nullable|boolean',
            'stackable_with' => 'nullable|array',
            'exclusive_with' => 'nullable|array',
            'stack_priority' => 'nullable|integer|min:0|max:100',
            'nth_item_number' => 'nullable|integer|min:2',
            'nth_item_discount_percent' => 'nullable|numeric|min:0|max:100',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
            'banner_image' => 'nullable|string|max:500',
            'terms_conditions' => 'nullable|string|max:2000',
            'apply_on' => 'nullable|string',
            'apply_on_value' => 'nullable|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date',
            'coupon_code' => ['nullable', 'string', 'max:50', Rule::unique('offers')->ignore($offer->id)],
            'status' => 'nullable|in:active,inactive,expired',
            'min_cart_value' => 'nullable|numeric|min:0',
            'max_cart_value' => 'nullable|numeric|min:0',
            'max_usage_global' => 'nullable|integer|min:1',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'first_order_only' => 'nullable|boolean',
        ]);

        // Update details
        $details = $offer->details ?? [];
        if (array_key_exists('min_cart_value', $validated)) {
            $details['min_cart_value'] = $validated['min_cart_value'];
        }
        if (array_key_exists('max_cart_value', $validated)) {
            $details['max_cart_value'] = $validated['max_cart_value'];
        }
        if (array_key_exists('max_usage_global', $validated)) {
            $details['max_usage_global'] = $validated['max_usage_global'];
        }
        if (array_key_exists('max_usage_per_user', $validated)) {
            $details['max_usage_per_user'] = $validated['max_usage_per_user'];
        }
        if (array_key_exists('first_order_only', $validated)) {
            $details['first_order_only'] = $validated['first_order_only'];
        }
        $validated['details'] = $details;

        if (isset($validated['coupon_code'])) {
            $validated['coupon_code'] = strtoupper($validated['coupon_code']);
        }

        $offer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Offer updated successfully',
            'offer' => $offer->fresh(),
        ]);
    }

    /**
     * Create flash sale with inventory allocation.
     */
    public function createFlashSale(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:flat,percent',
            'discount_amount' => 'required_if:discount_type,flat|numeric|min:0',
            'discount_percent' => 'required_if:discount_type,percent|numeric|min:0|max:100',
            'valid_from' => 'required|date|after_or_equal:now',
            'valid_to' => 'required|date|after:valid_from',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.allocated_stock' => 'required|integer|min:1',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
        ]);

        $totalStock = collect($validated['products'])->sum('allocated_stock');

        DB::beginTransaction();
        try {
            // Create flash sale offer
            $offer = Offers::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'offer_type_id' => offerTypeMaster::where('offer_type', 'flash_sale')->first()?->id,
                'offer_category' => 'quantity_based',
                'discount_type' => $validated['discount_type'],
                'discount_amount' => $validated['discount_amount'] ?? null,
                'discount_percent' => $validated['discount_percent'] ?? null,
                'valid_from' => $validated['valid_from'],
                'valid_to' => $validated['valid_to'],
                'is_flash_sale' => true,
                'flash_stock_limit' => $totalStock,
                'flash_sold_count' => 0,
                'apply_on' => 'products',
                'apply_on_value' => collect($validated['products'])->pluck('product_id')->toArray(),
                'badge_text' => $validated['badge_text'] ?? 'FLASH SALE',
                'badge_color' => $validated['badge_color'] ?? '#ff4444',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Create inventory allocations
            foreach ($validated['products'] as $product) {
                FlashSaleInventory::create([
                    'offer_id' => $offer->id,
                    'product_id' => $product['product_id'],
                    'allocated_stock' => $product['allocated_stock'],
                    'sold_stock' => 0,
                    'reserved_stock' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Flash sale created successfully',
                'offer' => $offer->load('flashSaleInventory'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create flash sale',
            ], 500);
        }
    }

    /**
     * Create combo/bundle offer.
     */
    public function createCombo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'bundle_name' => 'required|string|max:100',
            'product_ids' => 'required|array|min:2',
            'product_ids.*' => 'exists:products,id',
            'bundle_price' => 'required_without:bundle_discount_percent|numeric|min:0',
            'bundle_discount_percent' => 'required_without:bundle_price|numeric|min:0|max:100',
            'max_bundles_per_order' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'badge_text' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            // Create combo offer
            $offer = Offers::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'offer_type_id' => offerTypeMaster::where('offer_type', 'combo_offer')->first()?->id,
                'offer_category' => 'quantity_based',
                'combo_products' => $validated['product_ids'],
                'combo_price' => $validated['bundle_price'] ?? null,
                'discount_type' => isset($validated['bundle_discount_percent']) ? 'percent' : 'flat',
                'discount_percent' => $validated['bundle_discount_percent'] ?? null,
                'apply_on' => 'products',
                'apply_on_value' => $validated['product_ids'],
                'valid_from' => $validated['valid_from'],
                'valid_to' => $validated['valid_to'],
                'badge_text' => $validated['badge_text'] ?? 'COMBO',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            // Create bundle record
            OfferBundle::create([
                'offer_id' => $offer->id,
                'bundle_name' => $validated['bundle_name'],
                'product_ids' => $validated['product_ids'],
                'bundle_price' => $validated['bundle_price'] ?? null,
                'bundle_discount_percent' => $validated['bundle_discount_percent'] ?? null,
                'max_bundles_per_order' => $validated['max_bundles_per_order'],
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Combo offer created successfully',
                'offer' => $offer->load('bundles'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create combo offer',
            ], 500);
        }
    }

    /**
     * Create BOGO offer.
     */
    public function createBogo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'buy_quantity' => 'required|integer|min:1',
            'get_quantity' => 'required|integer|min:1',
            'get_discount_percent' => 'required|numeric|min:0|max:100',
            'apply_on' => 'required|in:products,categories,cart',
            'apply_on_value' => 'required_if:apply_on,products,categories|array',
            'get_products' => 'nullable|array',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'coupon_code' => 'nullable|string|max:50|unique:offers,coupon_code',
            'badge_text' => 'nullable|string|max:50',
        ]);

        $offer = Offers::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'offer_type_id' => offerTypeMaster::where('offer_type', 'bogo')->first()?->id
                ?? offerTypeMaster::where('offer_type', 'buy_x_get_y')->first()?->id,
            'offer_category' => 'quantity_based',
            'buy_quantity' => $validated['buy_quantity'],
            'get_quantity' => $validated['get_quantity'],
            'get_discount_percent' => $validated['get_discount_percent'],
            'get_products' => $validated['get_products'] ?? null,
            'apply_on' => $validated['apply_on'],
            'apply_on_value' => $validated['apply_on_value'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'],
            'coupon_code' => isset($validated['coupon_code']) ? strtoupper($validated['coupon_code']) : null,
            'badge_text' => $validated['badge_text'] ?? ($validated['get_discount_percent'] == 100 ? 'BOGO' : 'BUY MORE SAVE MORE'),
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'BOGO offer created successfully',
            'offer' => $offer,
        ], 201);
    }

    /**
     * Create tiered discount offer.
     */
    public function createTieredDiscount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'tier_rules' => 'required|array|min:1',
            'tier_rules.*.min_value' => 'required|numeric|min:0',
            'tier_rules.*.max_value' => 'nullable|numeric',
            'tier_rules.*.discount_type' => 'required|in:flat,percent',
            'tier_rules.*.discount_value' => 'required|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after:valid_from',
            'coupon_code' => 'nullable|string|max:50|unique:offers,coupon_code',
        ]);

        // Validate tier rules don't overlap
        $tiers = collect($validated['tier_rules'])->sortBy('min_value');
        $prevMax = null;
        foreach ($tiers as $tier) {
            if ($prevMax !== null && $tier['min_value'] <= $prevMax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Tier ranges must not overlap',
                ], 422);
            }
            $prevMax = $tier['max_value'] ?? PHP_INT_MAX;
        }

        $offer = Offers::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'offer_type_id' => offerTypeMaster::where('offer_type', 'tiered_discount')->first()?->id,
            'offer_category' => 'promotional',
            'tier_rules' => $tiers->values()->toArray(),
            'apply_on' => 'cart',
            'valid_from' => $validated['valid_from'],
            'valid_to' => $validated['valid_to'],
            'coupon_code' => isset($validated['coupon_code']) ? strtoupper($validated['coupon_code']) : null,
            'badge_text' => 'SPEND MORE SAVE MORE',
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tiered discount created successfully',
            'offer' => $offer,
        ], 201);
    }

    /**
     * Get offer analytics.
     */
    public function getAnalytics(Offers $offer): JsonResponse
    {
        $usageStats = OfferUsage::where('offer_id', $offer->id)
            ->selectRaw('COUNT(*) as total_uses')
            ->selectRaw('COUNT(DISTINCT customer_id) as unique_users')
            ->selectRaw('SUM(discount_amount) as total_discount')
            ->selectRaw('AVG(discount_amount) as avg_discount')
            ->selectRaw('SUM(CASE WHEN reversed = 1 THEN 1 ELSE 0 END) as reversed_count')
            ->first();

        $dailyUsage = OfferUsage::where('offer_id', $offer->id)
            ->where('reversed', false)
            ->selectRaw('DATE(used_at) as date')
            ->selectRaw('COUNT(*) as uses')
            ->selectRaw('SUM(discount_amount) as discount')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'offer' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'status' => $offer->status,
                'view_count' => $offer->view_count,
                'usage_count' => $offer->usage_count,
                'total_discount_given' => $offer->total_discount_given,
            ],
            'statistics' => [
                'total_uses' => $usageStats->total_uses ?? 0,
                'unique_users' => $usageStats->unique_users ?? 0,
                'total_discount' => $usageStats->total_discount ?? 0,
                'avg_discount' => round($usageStats->avg_discount ?? 0, 2),
                'reversed_count' => $usageStats->reversed_count ?? 0,
                'conversion_rate' => $offer->view_count > 0
                    ? round(($usageStats->total_uses / $offer->view_count) * 100, 2)
                    : 0,
            ],
            'daily_usage' => $dailyUsage,
            'flash_sale_stats' => $offer->is_flash_sale ? [
                'stock_limit' => $offer->flash_stock_limit,
                'sold_count' => $offer->flash_sold_count,
                'remaining' => $offer->remaining_flash_stock,
                'sold_percentage' => $offer->flash_stock_limit > 0
                    ? round(($offer->flash_sold_count / $offer->flash_stock_limit) * 100, 1)
                    : 0,
            ] : null,
        ]);
    }

    /**
     * Duplicate offer.
     */
    public function duplicate(Offers $offer): JsonResponse
    {
        $newOffer = $offer->replicate();
        $newOffer->title = $offer->title . ' (Copy)';
        $newOffer->coupon_code = null;
        $newOffer->status = 'inactive';
        $newOffer->view_count = 0;
        $newOffer->usage_count = 0;
        $newOffer->total_discount_given = 0;
        $newOffer->flash_sold_count = 0;
        $newOffer->created_by = auth()->id();
        $newOffer->save();

        // Duplicate bundles if any
        foreach ($offer->bundles as $bundle) {
            $newBundle = $bundle->replicate();
            $newBundle->offer_id = $newOffer->id;
            $newBundle->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Offer duplicated successfully',
            'offer' => $newOffer->load('bundles'),
        ], 201);
    }

    /**
     * Bulk status update.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'offer_ids' => 'required|array|min:1|max:50',
            'offer_ids.*' => 'exists:offers,id',
            'status' => 'required|in:active,inactive,expired',
        ]);

        $updated = Offers::whereIn('id', $validated['offer_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} offers updated to {$validated['status']}",
            'updated_count' => $updated,
        ]);
    }
}
