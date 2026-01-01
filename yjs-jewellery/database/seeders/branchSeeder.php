<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class branchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('branches')->truncate();
        $branches = [
            [
                'name' => 'Head Office',
                'slug' => Str::slug('Head Office'),
                'address' => '123 Business Street, Andheri East',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400069',
                'status' => 'A',
            ],
            [
                'name' => 'Delhi Branch',
                'slug' => Str::slug('Delhi Branch'),
                'address' => '45 Connaught Place',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'country' => 'India',
                'pincode' => '110001',
                'status' => 'A',
            ],
            [
                'name' => 'Bangalore Branch',
                'slug' => Str::slug('Bangalore Branch'),
                'address' => '78 MG Road',
                'city' => 'Bangalore',
                'state' => 'Karnataka',
                'country' => 'India',
                'pincode' => '560001',
                'status' => 'A',
            ],
            [
                'name' => 'Pune Branch',
                'slug' => Str::slug('Pune Branch'),
                'address' => '12 FC Road',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '411004',
                'status' => 'A',
            ],
        ];

        DB::table('branches')->insert($branches);
    }
}
