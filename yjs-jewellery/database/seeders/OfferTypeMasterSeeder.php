<?php

namespace Database\Seeders;

use App\Models\offerTypeMaster;
use Illuminate\Database\Seeder;

/**
 * Offer Type Master Seeder
 *
 * Seeds all offer types for comprehensive e-commerce promotions.
 * Categories:
 * 1. Promotional & Rule-Based Offers
 * 2. Quantity-Based Offers (BOGO, Combos)
 * 3. Audience & Occasion-Based Offers
 * 4. Coupon-Based Offers
 */
class OfferTypeMasterSeeder extends Seeder
{
    public function run(): void
    {
        $offerTypes = [
            // ===========================================
            // TYPE 1: PROMOTIONAL & RULE-BASED OFFERS
            // ===========================================
            [
                'offer_type' => 'flat_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'flat',
                    'requires_coupon' => false,
                    'can_stack' => false,
                ]),
                'description' => 'Fixed amount discount (e.g., Rs.500 off)',
                'apply_to' => 'cart',
                'apply_to_option' => 'total',
                'status' => 'A',
            ],
            [
                'offer_type' => 'percentage_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'percent',
                    'requires_coupon' => false,
                    'can_stack' => false,
                    'supports_max_cap' => true,
                ]),
                'description' => 'Percentage discount with optional cap (e.g., 10% off up to Rs.1000)',
                'apply_to' => 'cart',
                'apply_to_option' => 'total',
                'status' => 'A',
            ],
            [
                'offer_type' => 'category_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_category_selection' => true,
                    'supports_multiple_categories' => true,
                ]),
                'description' => 'Discount on specific categories',
                'apply_to' => 'category',
                'apply_to_option' => 'selected',
                'status' => 'A',
            ],
            [
                'offer_type' => 'product_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_product_selection' => true,
                    'supports_multiple_products' => true,
                ]),
                'description' => 'Discount on specific products',
                'apply_to' => 'product',
                'apply_to_option' => 'selected',
                'status' => 'A',
            ],
            [
                'offer_type' => 'min_cart_value',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_min_value' => true,
                ]),
                'description' => 'Discount when cart exceeds minimum value',
                'apply_to' => 'cart',
                'apply_to_option' => 'min_value',
                'status' => 'A',
            ],
            [
                'offer_type' => 'tiered_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'tiered',
                    'supports_multiple_slabs' => true,
                    'slab_fields' => ['min_value', 'max_value', 'discount_type', 'discount_value'],
                ]),
                'description' => 'Slab-based discounts (Spend Rs.5000 get 5%, Rs.10000 get 10%)',
                'apply_to' => 'cart',
                'apply_to_option' => 'slabs',
                'status' => 'A',
            ],
            [
                'offer_type' => 'free_shipping',
                'offer_type_option' => json_encode([
                    'discount_type' => 'shipping',
                    'waives_shipping' => true,
                    'supports_min_order' => true,
                ]),
                'description' => 'Free shipping on qualifying orders',
                'apply_to' => 'shipping',
                'apply_to_option' => 'waive',
                'status' => 'A',
            ],
            [
                'offer_type' => 'payment_method',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'payment_options' => ['upi', 'card', 'netbanking', 'cod', 'wallet'],
                    'supports_bank_selection' => true,
                    'supports_card_network' => true,
                ]),
                'description' => 'Discount on specific payment method (e.g., 5% off on UPI)',
                'apply_to' => 'payment',
                'apply_to_option' => 'method',
                'status' => 'A',
            ],
            [
                'offer_type' => 'bank_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_bank_selection' => true,
                    'supported_banks' => ['hdfc', 'icici', 'sbi', 'axis', 'kotak', 'yes', 'bob'],
                ]),
                'description' => 'Bank-specific discounts (e.g., 10% off on HDFC cards)',
                'apply_to' => 'payment',
                'apply_to_option' => 'bank',
                'status' => 'A',
            ],
            [
                'offer_type' => 'emi_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'emi',
                    'supports_no_cost_emi' => true,
                    'emi_tenures' => [3, 6, 9, 12, 18, 24],
                ]),
                'description' => 'No-cost EMI or EMI discounts',
                'apply_to' => 'payment',
                'apply_to_option' => 'emi',
                'status' => 'A',
            ],
            [
                'offer_type' => 'fixed_price',
                'offer_type_option' => json_encode([
                    'discount_type' => 'fixed',
                    'sets_fixed_price' => true,
                ]),
                'description' => 'Fixed price for product/combo (e.g., All rings at Rs.999)',
                'apply_to' => 'product',
                'apply_to_option' => 'fixed_price',
                'status' => 'A',
            ],

            // ===========================================
            // TYPE 2: QUANTITY-BASED OFFERS
            // ===========================================
            [
                'offer_type' => 'bogo',
                'offer_type_option' => json_encode([
                    'discount_type' => 'quantity',
                    'buy_quantity' => 1,
                    'get_quantity' => 1,
                    'get_discount' => 100, // Free
                    'same_product_only' => true,
                ]),
                'description' => 'Buy One Get One Free',
                'apply_to' => 'product',
                'apply_to_option' => 'bogo',
                'status' => 'A',
            ],
            [
                'offer_type' => 'buy_x_get_y',
                'offer_type_option' => json_encode([
                    'discount_type' => 'quantity',
                    'configurable_x' => true,
                    'configurable_y' => true,
                    'configurable_discount' => true,
                    'supports_different_products' => true,
                ]),
                'description' => 'Buy X Get Y (e.g., Buy 2 Get 1 at 50% off)',
                'apply_to' => 'product',
                'apply_to_option' => 'buy_x_get_y',
                'status' => 'A',
            ],
            [
                'offer_type' => 'combo_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'combo',
                    'requires_all_products' => true,
                    'supports_fixed_price' => true,
                    'supports_percent_off' => true,
                ]),
                'description' => 'Bundle discount (Ring + Earring combo at Rs.2999)',
                'apply_to' => 'product',
                'apply_to_option' => 'combo',
                'status' => 'A',
            ],
            [
                'offer_type' => 'bundle_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'bundle',
                    'min_items' => 2,
                    'discount_applies_to' => 'cheapest',
                ]),
                'description' => 'Buy multiple items, get discount on cheapest',
                'apply_to' => 'cart',
                'apply_to_option' => 'bundle',
                'status' => 'A',
            ],
            [
                'offer_type' => 'nth_item_discount',
                'offer_type_option' => json_encode([
                    'discount_type' => 'nth_item',
                    'nth_number' => 3,
                    'discount_percent' => 50,
                ]),
                'description' => 'Every Nth item at discount (Every 3rd item 50% off)',
                'apply_to' => 'cart',
                'apply_to_option' => 'nth_item',
                'status' => 'A',
            ],
            [
                'offer_type' => 'flash_sale',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'is_time_limited' => true,
                    'supports_stock_limit' => true,
                    'show_countdown' => true,
                    'show_stock_remaining' => true,
                ]),
                'description' => 'Limited time/stock flash sale',
                'apply_to' => 'product',
                'apply_to_option' => 'flash',
                'status' => 'A',
            ],
            [
                'offer_type' => 'quantity_break',
                'offer_type_option' => json_encode([
                    'discount_type' => 'quantity_tiered',
                    'tiers' => [
                        ['min_qty' => 2, 'discount' => 5],
                        ['min_qty' => 5, 'discount' => 10],
                        ['min_qty' => 10, 'discount' => 15],
                    ],
                ]),
                'description' => 'Buy more save more (2+ items: 5% off, 5+: 10% off)',
                'apply_to' => 'cart',
                'apply_to_option' => 'quantity_tiers',
                'status' => 'A',
            ],

            // ===========================================
            // TYPE 3: AUDIENCE & OCCASION-BASED OFFERS
            // ===========================================
            [
                'offer_type' => 'first_order',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'new_customer_only' => true,
                    'one_time_use' => true,
                ]),
                'description' => 'First order/New customer discount',
                'apply_to' => 'cart',
                'apply_to_option' => 'first_order',
                'status' => 'A',
            ],
            [
                'offer_type' => 'birthday_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_dob' => true,
                    'days_before' => 0,
                    'days_after' => 7,
                    'one_per_year' => true,
                ]),
                'description' => 'Birthday special discount',
                'apply_to' => 'cart',
                'apply_to_option' => 'birthday',
                'status' => 'A',
            ],
            [
                'offer_type' => 'anniversary_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_anniversary_date' => true,
                    'days_before' => 3,
                    'days_after' => 7,
                ]),
                'description' => 'Anniversary special discount',
                'apply_to' => 'cart',
                'apply_to_option' => 'anniversary',
                'status' => 'A',
            ],
            [
                'offer_type' => 'referral_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'referral',
                    'referrer_gets' => 'points_or_discount',
                    'referee_gets' => 'discount',
                    'requires_first_purchase' => true,
                ]),
                'description' => 'Refer a friend rewards',
                'apply_to' => 'user',
                'apply_to_option' => 'referral',
                'status' => 'A',
            ],
            [
                'offer_type' => 'loyalty_reward',
                'offer_type_option' => json_encode([
                    'discount_type' => 'points',
                    'points_required' => true,
                    'conversion_rate' => 100, // 100 points = Rs.1
                ]),
                'description' => 'Loyalty points redemption',
                'apply_to' => 'cart',
                'apply_to_option' => 'loyalty',
                'status' => 'A',
            ],
            [
                'offer_type' => 'vip_exclusive',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'tier_required' => ['gold', 'platinum'],
                    'early_access' => true,
                ]),
                'description' => 'VIP/Premium customer exclusive offers',
                'apply_to' => 'user',
                'apply_to_option' => 'tier',
                'status' => 'A',
            ],
            [
                'offer_type' => 'festival_offer',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'festival_tags' => ['diwali', 'akshaya_tritiya', 'dhanteras', 'eid', 'christmas'],
                    'limited_period' => true,
                ]),
                'description' => 'Festival/Occasion specific offers',
                'apply_to' => 'cart',
                'apply_to_option' => 'festival',
                'status' => 'A',
            ],
            [
                'offer_type' => 'win_back',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'inactive_days' => 90,
                    'one_time_use' => true,
                ]),
                'description' => 'Re-engagement offer for inactive customers',
                'apply_to' => 'user',
                'apply_to_option' => 'win_back',
                'status' => 'A',
            ],
            [
                'offer_type' => 'ugc_reward',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'reward_for' => ['review', 'photo_review', 'video_review', 'social_share'],
                    'points_or_discount' => true,
                ]),
                'description' => 'Reward for reviews/social shares',
                'apply_to' => 'user',
                'apply_to_option' => 'ugc',
                'status' => 'A',
            ],

            // ===========================================
            // TYPE 4: COUPON-BASED OFFERS
            // ===========================================
            [
                'offer_type' => 'public_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_code' => true,
                    'is_public' => true,
                    'usage_limit_global' => true,
                    'usage_limit_per_user' => true,
                ]),
                'description' => 'Public coupon code with usage limits',
                'apply_to' => 'cart',
                'apply_to_option' => 'coupon_public',
                'status' => 'A',
            ],
            [
                'offer_type' => 'private_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_code' => true,
                    'is_public' => false,
                    'single_use' => true,
                    'assigned_to_user' => true,
                ]),
                'description' => 'Private/Personal coupon code',
                'apply_to' => 'user',
                'apply_to_option' => 'coupon_private',
                'status' => 'A',
            ],
            [
                'offer_type' => 'time_bound_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_code' => true,
                    'time_restricted' => true,
                    'valid_hours' => ['start' => '10:00', 'end' => '14:00'],
                ]),
                'description' => 'Time-restricted coupon (valid only during specific hours)',
                'apply_to' => 'cart',
                'apply_to_option' => 'coupon_timed',
                'status' => 'A',
            ],
            [
                'offer_type' => 'stackable_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_code' => true,
                    'is_stackable' => true,
                    'max_stack' => 2,
                ]),
                'description' => 'Coupon that can be combined with other offers',
                'apply_to' => 'cart',
                'apply_to_option' => 'coupon_stackable',
                'status' => 'A',
            ],
            [
                'offer_type' => 'free_gift_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'gift',
                    'requires_code' => true,
                    'gift_selection' => 'predefined',
                    'min_order_for_gift' => true,
                ]),
                'description' => 'Coupon for free gift with purchase',
                'apply_to' => 'cart',
                'apply_to_option' => 'coupon_gift',
                'status' => 'A',
            ],
            [
                'offer_type' => 'influencer_coupon',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'requires_code' => true,
                    'tracks_attribution' => true,
                    'commission_enabled' => true,
                ]),
                'description' => 'Influencer/Affiliate coupon with tracking',
                'apply_to' => 'cart',
                'apply_to_option' => 'coupon_affiliate',
                'status' => 'A',
            ],

            // ===========================================
            // SPECIAL: FREE GIFT & MISC
            // ===========================================
            [
                'offer_type' => 'free_gift',
                'offer_type_option' => json_encode([
                    'discount_type' => 'gift',
                    'min_cart_for_gift' => true,
                    'gift_choices' => false,
                    'auto_add_to_cart' => true,
                ]),
                'description' => 'Free gift with minimum purchase',
                'apply_to' => 'cart',
                'apply_to_option' => 'free_gift',
                'status' => 'A',
            ],
            [
                'offer_type' => 'gift_with_choice',
                'offer_type_option' => json_encode([
                    'discount_type' => 'gift',
                    'min_cart_for_gift' => true,
                    'gift_choices' => true,
                    'max_choices' => 1,
                ]),
                'description' => 'Choose your free gift',
                'apply_to' => 'cart',
                'apply_to_option' => 'gift_choice',
                'status' => 'A',
            ],
            [
                'offer_type' => 'price_drop_alert',
                'offer_type_option' => json_encode([
                    'discount_type' => 'notification',
                    'triggers_notification' => true,
                    'for_wishlisted_items' => true,
                ]),
                'description' => 'Price drop notification for wishlist items',
                'apply_to' => 'product',
                'apply_to_option' => 'price_alert',
                'status' => 'A',
            ],
            [
                'offer_type' => 'cart_abandonment',
                'offer_type_option' => json_encode([
                    'discount_type' => 'both',
                    'trigger_hours' => 24,
                    'max_reminders' => 3,
                    'escalating_discount' => true,
                ]),
                'description' => 'Cart abandonment recovery offer',
                'apply_to' => 'cart',
                'apply_to_option' => 'abandonment',
                'status' => 'A',
            ],
        ];

        foreach ($offerTypes as $type) {
            offerTypeMaster::updateOrCreate(
                ['offer_type' => $type['offer_type']],
                $type
            );
        }
    }
}
