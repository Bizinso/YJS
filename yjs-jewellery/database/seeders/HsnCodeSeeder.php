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
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '71131100',
                'description' => 'Of silver, whether or not plated or clad with other precious metal',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '71131911',
                'description' => 'Jewellery studded with diamond',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '71131919',
                'description' => 'Other gold jewellery',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '71131920',
                'description' => 'Jewellery of platinum',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            // Precious Stones
            [
                'code' => '7102',
                'description' => 'Diamonds, whether or not worked, but not mounted or set',
                'gst_rate' => 0.25,
                'cgst_rate' => 0.125,
                'sgst_rate' => 0.125,
                'igst_rate' => 0.25,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '7103',
                'description' => 'Precious stones (other than diamonds) and semi-precious stones',
                'gst_rate' => 0.25,
                'cgst_rate' => 0.125,
                'sgst_rate' => 0.125,
                'igst_rate' => 0.25,
                'type' => 'goods',
                'is_active' => true,
            ],
            // Gold & Precious Metals
            [
                'code' => '7108',
                'description' => 'Gold (including gold plated with platinum) unwrought or in semi-manufactured forms',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '7106',
                'description' => 'Silver (including silver plated with gold or platinum) unwrought or in semi-manufactured forms',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            [
                'code' => '7110',
                'description' => 'Platinum, unwrought or in semi-manufactured forms',
                'gst_rate' => 3.00,
                'cgst_rate' => 1.50,
                'sgst_rate' => 1.50,
                'igst_rate' => 3.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            // Imitation Jewellery
            [
                'code' => '7117',
                'description' => 'Imitation jewellery',
                'gst_rate' => 12.00,
                'cgst_rate' => 6.00,
                'sgst_rate' => 6.00,
                'igst_rate' => 12.00,
                'type' => 'goods',
                'is_active' => true,
            ],
            // Watches
            [
                'code' => '9101',
                'description' => 'Wrist-watches, pocket-watches with case of precious metal',
                'gst_rate' => 18.00,
                'cgst_rate' => 9.00,
                'sgst_rate' => 9.00,
                'igst_rate' => 18.00,
                'type' => 'goods',
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
