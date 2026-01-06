<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse - Mumbai',
                'code' => 'WH-MUM-001',
                'description' => 'Primary warehouse in Mumbai',
                'type' => 'warehouse',
                'address_line1' => 'Plot No. 45, MIDC Industrial Area',
                'address_line2' => 'Andheri East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400093',
                'country' => 'IN',
                'phone' => '+91-22-12345678',
                'email' => 'warehouse.mumbai@yjsjewellery.com',
                'is_active' => true,
                'is_default' => true,
                'accepts_returns' => true,
                'allows_pickup' => false,
                'priority' => 1,
            ],
            [
                'name' => 'Showroom Stock - Mumbai',
                'code' => 'SR-MUM-001',
                'description' => 'Mumbai showroom stock',
                'type' => 'store',
                'address_line1' => 'Ground Floor, Diamond Tower',
                'address_line2' => 'BKC',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400051',
                'country' => 'IN',
                'phone' => '+91-22-23456789',
                'email' => 'showroom.mumbai@yjsjewellery.com',
                'is_active' => true,
                'is_default' => false,
                'accepts_returns' => true,
                'allows_pickup' => true,
                'priority' => 2,
            ],
            [
                'name' => 'Regional Warehouse - Delhi',
                'code' => 'WH-DEL-001',
                'description' => 'Delhi regional warehouse',
                'type' => 'warehouse',
                'address_line1' => 'Sector 18',
                'address_line2' => 'Noida',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '201301',
                'country' => 'IN',
                'phone' => '+91-11-34567890',
                'email' => 'warehouse.delhi@yjsjewellery.com',
                'is_active' => true,
                'is_default' => false,
                'accepts_returns' => true,
                'allows_pickup' => false,
                'priority' => 3,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            DB::table('warehouses')->updateOrInsert(
                ['code' => $warehouse['code']],
                array_merge($warehouse, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
