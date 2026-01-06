<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default tax zones for India
        $zones = [
            [
                'name' => 'Maharashtra',
                'code' => 'MH',
                'description' => 'Maharashtra state tax zone',
                'countries' => json_encode(['IN']),
                'states' => json_encode(['MH']),
                'is_default' => true,
                'is_active' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Gujarat',
                'code' => 'GJ',
                'description' => 'Gujarat state tax zone',
                'countries' => json_encode(['IN']),
                'states' => json_encode(['GJ']),
                'is_default' => false,
                'is_active' => true,
                'priority' => 5,
            ],
            [
                'name' => 'Karnataka',
                'code' => 'KA',
                'description' => 'Karnataka state tax zone',
                'countries' => json_encode(['IN']),
                'states' => json_encode(['KA']),
                'is_default' => false,
                'is_active' => true,
                'priority' => 5,
            ],
            [
                'name' => 'Delhi',
                'code' => 'DL',
                'description' => 'Delhi state tax zone',
                'countries' => json_encode(['IN']),
                'states' => json_encode(['DL']),
                'is_default' => false,
                'is_active' => true,
                'priority' => 5,
            ],
        ];

        foreach ($zones as $zone) {
            DB::table('tax_zones')->updateOrInsert(
                ['code' => $zone['code']],
                array_merge($zone, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Create default tax rules for jewellery
        $defaultZone = DB::table('tax_zones')->where('is_default', true)->first();

        if ($defaultZone) {
            $taxRules = [
                [
                    'name' => 'GST 3% - Gold/Silver Jewellery',
                    'code' => 'GST-GOLD-3',
                    'description' => 'GST for gold and silver jewellery',
                    'tax_zone_id' => $defaultZone->id,
                    'tax_type' => 'gst',
                    'rate' => 3.00,
                    'cgst_rate' => 1.50,
                    'sgst_rate' => 1.50,
                    'igst_rate' => 3.00,
                    'apply_to' => 'all',
                    'calculation_type' => 'percentage',
                    'is_compound' => false,
                    'is_inclusive' => false,
                    'is_active' => true,
                    'priority' => 10,
                ],
                [
                    'name' => 'GST 0.25% - Diamonds & Precious Stones',
                    'code' => 'GST-DIAMOND-025',
                    'description' => 'GST for diamonds and precious stones',
                    'tax_zone_id' => $defaultZone->id,
                    'tax_type' => 'gst',
                    'rate' => 0.25,
                    'cgst_rate' => 0.125,
                    'sgst_rate' => 0.125,
                    'igst_rate' => 0.25,
                    'apply_to' => 'all',
                    'calculation_type' => 'percentage',
                    'is_compound' => false,
                    'is_inclusive' => false,
                    'is_active' => true,
                    'priority' => 9,
                ],
            ];

            foreach ($taxRules as $rule) {
                DB::table('tax_rules')->updateOrInsert(
                    ['code' => $rule['code']],
                    array_merge($rule, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
