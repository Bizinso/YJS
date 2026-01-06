<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HsnCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hsnCodes = [
            // Gold Jewellery
            [
                'code' => '7113',
                'description' => 'Articles of jewellery and parts thereof, of precious metal or of metal clad with precious metal',
                'category' => 'Gold Jewellery',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '71131100',
                'description' => 'Of silver, whether or not plated or clad with other precious metal',
                'category' => 'Silver Jewellery',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '71131911',
                'description' => 'Jewellery studded with diamond',
                'category' => 'Diamond Jewellery',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '71131919',
                'description' => 'Other gold jewellery',
                'category' => 'Gold Jewellery',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '71131920',
                'description' => 'Jewellery of platinum',
                'category' => 'Platinum Jewellery',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            // Precious Stones
            [
                'code' => '7102',
                'description' => 'Diamonds, whether or not worked, but not mounted or set',
                'category' => 'Diamonds',
                'default_rate' => 0.25,
                'is_active' => true,
            ],
            [
                'code' => '7103',
                'description' => 'Precious stones (other than diamonds) and semi-precious stones',
                'category' => 'Gemstones',
                'default_rate' => 0.25,
                'is_active' => true,
            ],
            // Gold & Precious Metals
            [
                'code' => '7108',
                'description' => 'Gold (including gold plated with platinum) unwrought or in semi-manufactured forms',
                'category' => 'Gold',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '7106',
                'description' => 'Silver (including silver plated with gold or platinum) unwrought or in semi-manufactured forms',
                'category' => 'Silver',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            [
                'code' => '7110',
                'description' => 'Platinum, unwrought or in semi-manufactured forms',
                'category' => 'Platinum',
                'default_rate' => 3.00,
                'is_active' => true,
            ],
            // Imitation Jewellery
            [
                'code' => '7117',
                'description' => 'Imitation jewellery',
                'category' => 'Imitation Jewellery',
                'default_rate' => 12.00,
                'is_active' => true,
            ],
            // Watches
            [
                'code' => '9101',
                'description' => 'Wrist-watches, pocket-watches with case of precious metal',
                'category' => 'Watches',
                'default_rate' => 18.00,
                'is_active' => true,
            ],
        ];

        foreach ($hsnCodes as $hsn) {
            DB::table('hsn_codes')->updateOrInsert(
                ['code' => $hsn['code']],
                array_merge($hsn, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
