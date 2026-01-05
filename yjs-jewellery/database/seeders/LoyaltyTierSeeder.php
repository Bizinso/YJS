<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty Tier Seeder
 *
 * Seeds loyalty program tiers with benefits.
 */
class LoyaltyTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_points' => 0,
                'max_points' => 4999,
                'points_multiplier' => 1.00,
                'benefits' => json_encode([
                    'earn_rate' => '1 point per Rs.100 spent',
                    'redemption_rate' => '100 points = Rs.1',
                    'birthday_bonus' => 100,
                    'welcome_points' => 50,
                    'exclusive_access' => false,
                    'free_shipping_threshold' => 5000,
                    'priority_support' => false,
                ]),
                'badge_icon' => 'bronze-badge.svg',
                'badge_color' => '#CD7F32',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_points' => 5000,
                'max_points' => 14999,
                'points_multiplier' => 1.25,
                'benefits' => json_encode([
                    'earn_rate' => '1.25 points per Rs.100 spent',
                    'redemption_rate' => '100 points = Rs.1.25',
                    'birthday_bonus' => 250,
                    'anniversary_bonus' => 100,
                    'exclusive_access' => false,
                    'free_shipping_threshold' => 3000,
                    'priority_support' => false,
                    'early_sale_access_hours' => 0,
                    'extra_discount_percent' => 2,
                ]),
                'badge_icon' => 'silver-badge.svg',
                'badge_color' => '#C0C0C0',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_points' => 15000,
                'max_points' => 49999,
                'points_multiplier' => 1.50,
                'benefits' => json_encode([
                    'earn_rate' => '1.5 points per Rs.100 spent',
                    'redemption_rate' => '100 points = Rs.1.50',
                    'birthday_bonus' => 500,
                    'anniversary_bonus' => 250,
                    'exclusive_access' => true,
                    'free_shipping_threshold' => 1500,
                    'priority_support' => true,
                    'early_sale_access_hours' => 12,
                    'extra_discount_percent' => 5,
                    'free_gift_wrapping' => true,
                    'exclusive_collections' => true,
                ]),
                'badge_icon' => 'gold-badge.svg',
                'badge_color' => '#FFD700',
                'order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Platinum',
                'slug' => 'platinum',
                'min_points' => 50000,
                'max_points' => null,
                'points_multiplier' => 2.00,
                'benefits' => json_encode([
                    'earn_rate' => '2 points per Rs.100 spent',
                    'redemption_rate' => '100 points = Rs.2',
                    'birthday_bonus' => 1000,
                    'anniversary_bonus' => 500,
                    'exclusive_access' => true,
                    'free_shipping_threshold' => 0,
                    'priority_support' => true,
                    'dedicated_support' => true,
                    'early_sale_access_hours' => 24,
                    'extra_discount_percent' => 10,
                    'free_gift_wrapping' => true,
                    'exclusive_collections' => true,
                    'vip_events_access' => true,
                    'complimentary_cleaning' => true,
                    'personal_stylist' => true,
                    'surprise_gifts' => true,
                ]),
                'badge_icon' => 'platinum-badge.svg',
                'badge_color' => '#E5E4E2',
                'order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('loyalty_tiers')->insert($tiers);
    }
}
