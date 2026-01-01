<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            countrySeeder::class,
            stateSeeder::class,
            citySeeder::class,
            branchSeeder::class,
            departmentSeeder::class,
            roleSeeder::class,
            menuSeeder::class,
            permissionSeeder::class,
            AttributeOptionSeeder::class,
        ]);

    }
}
