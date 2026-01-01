<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('attribute_options')->truncate();

        $data = [
            [
                'name' => 'Dropdown',
                'slug' => 'dropdown',
                'status' => 'A'
            ],
            [
                'name' => 'Visual Swatch',
                'slug' => 'visual-swatch',
                'status' => 'A'
            ],
            [
                'name' => 'Text Swatch',
                'slug' => 'text-swatch',
                'status' => 'A'
            ],
        ];

        foreach ($data as $item) {
            DB::table('attribute_options')->updateOrInsert(
                ['slug' => $item['slug']], 
                [ 
                    'name' => $item['name'],
                    'status' => $item['status'],
                ]
            );
        }
    
    }
}
