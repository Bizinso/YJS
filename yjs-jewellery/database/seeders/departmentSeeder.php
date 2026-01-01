<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class departmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('departments')->truncate();
        $departments = [
            [
                'name' => 'Sales',
                'slug' => Str::slug('Sales'),
                'description' => 'Handles customer relationships and manages sales activities.',
                'status' => 'A',
                'branch_id' => 1, // Head Office
            ],
            [
                'name' => 'Marketing',
                'slug' => Str::slug('Marketing'),
                'description' => 'Responsible for promotions, branding, and advertising strategies.',
                'status' => 'A',
                'branch_id' => 2, // Delhi Branch
            ],
            [
                'name' => 'HR',
                'slug' => Str::slug('HR'),
                'description' => 'Manages employee relations, payroll, and recruitment.',
                'status' => 'A',
                'branch_id' => 3, // Bangalore Branch
            ],
            [
                'name' => 'IT',
                'slug' => Str::slug('IT'),
                'description' => 'Maintains systems, infrastructure, and technical support.',
                'status' => 'A',
                'branch_id' => 4, // Pune Branch
            ],
        ];

        DB::table('departments')->insert($departments);
    }
}
