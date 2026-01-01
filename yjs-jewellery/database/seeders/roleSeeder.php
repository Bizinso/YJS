<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class roleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        $roles = [
            [
                'name' => 'Admin',
                'slug' => Str::slug('Admin'),
                'description' => 'Full access to all modules and system settings.',
                'status' => 'A',
                'department_id' => 1, // Sales
            ],
            [
                'name' => 'Manager',
                'slug' => Str::slug('Manager'),
                'description' => 'Manages team operations and approves workflows.',
                'status' => 'A',
                'department_id' => 2, // Marketing
            ],
            [
                'name' => 'Employee',
                'slug' => Str::slug('Employee'),
                'description' => 'Performs assigned tasks and reports to managers.',
                'status' => 'A',
                'department_id' => 3, // HR
            ],
            [
                'name' => 'Intern',
                'slug' => Str::slug('Intern'),
                'description' => 'Assists with daily operations under supervision.',
                'status' => 'A',
                'department_id' => 4, // IT
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}
