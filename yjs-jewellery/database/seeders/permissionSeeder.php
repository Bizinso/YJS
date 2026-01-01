<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\Permission;

class permissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();

        // Standard permission actions for each menu
        $actions = [
            'access', 'view', 'list', 'add', 'edit', 'delete', 'export', 'import'
        ];

        // Fetch all menus (parent + child)
        $menus = Menu::orderBy('id')->get();

        foreach ($menus as $menu) {

            // Skip menus without slug (like parent without route)
            if (!$menu->slug) {
                continue;
            }

            foreach ($actions as $action) {

                Permission::create([
                    'menu_id' => $menu->id,
                    'name' => ucfirst($action) . ' ' . $menu->title,
                    'slug' => $action . '_' . $menu->slug,  // e.g. access_admin.dashboard
                ]);
            }
        }
    }
}
