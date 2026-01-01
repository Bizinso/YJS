<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;

class menuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('menus')->truncate();

        // Helper to create main menu + submenus with order
        $createMenu = function ($title, $slug = null, $icon = null, $order = 1, $children = []) use (&$createMenu) {

            $menu = Menu::create([
                'title' => $title,
                'slug'  => $slug,
                'icon'  => $icon,
                'order' => $order,
            ]);

            foreach ($children as $index => $child) {

                // Create child
                $childMenu = Menu::create([
                    'parent_id' => $menu->id,
                    'title'     => $child['title'],
                    'slug'      => $child['slug'] ?? null,
                    'icon'      => $child['icon'] ?? null,
                    'order'     => $child['order'] ?? ($index + 1),
                ]);

                // If nested child has more children → recurse
                if (isset($child['children']) && is_array($child['children'])) {
                    foreach ($child['children'] as $i => $subChild) {
                        Menu::create([
                            'parent_id' => $childMenu->id,
                            'title'     => $subChild['title'],
                            'slug'      => $subChild['slug'] ?? null,
                            'icon'      => $subChild['icon'] ?? null,
                            'order'     => $subChild['order'] ?? ($i + 1),
                        ]);
                    }
                }
            }

            return $menu;
        };


        // ------------------------------------
        // MAIN MENUS WITH ORDER
        // ------------------------------------

        $createMenu('Dashboard', 'admin.dashboard', 'fa-solid fa-chart-line', 1);

        $createMenu('Products', 'admin.products', 'fa-solid fa-box', 2);

        $createMenu('Orders', 'admin.orders', 'fa-solid fa-cart-shopping', 3);

        $createMenu('Offers', 'admin.offers', 'fa-solid fa-gift', 4);

        $createMenu('Customers', 'admin.customers', 'fa-solid fa-user-group', 5);

        $createMenu('Partners', 'admin.partners', 'fa-solid fa-handshake', 6);

        $createMenu('Inquiries', 'admin.inquiries', 'fa-solid fa-envelope', 7);

        // Access Control
        $createMenu('Access Control', 'admin.access-control', 'fa-solid fa-shield-halved', 8, [
            ['title' => 'Users', 'slug' => 'admin.access.users', 'order' => 1],
            ['title' => 'Permissions', 'slug' => 'admin.access.permissions', 'order' => 3],
        ]);

        // CMS
        $createMenu('CMS', 'admin.cms', 'fa-solid fa-note-sticky', 9, [
            ['title' => 'Pages', 'slug' => 'admin.cms.pages', 'order' => 1],
            ['title' => 'Sliders', 'slug' => 'admin.cms.sliders', 'order' => 2],
        ]);

        // Blog
        $createMenu('Blog', 'admin.blog', 'fa-solid fa-blog', 10, [
            ['title' => 'Blog List', 'slug' => 'admin.blog.index', 'order' => 1],
            ['title' => 'Blog Categories', 'slug' => 'admin.blog.categories', 'order' => 2],
        ]);

        // Events
        $createMenu('Events', 'admin.events', 'fa-solid fa-calendar', 11);

        // Exhibition
        $createMenu('Exhibitions', 'admin.exhibitions', 'fa-solid fa-building', 12);

        // Brochures
        $createMenu('Brochures', 'admin.brochures', 'fa-solid fa-book-open', 13);

        // Feedback
        $createMenu('Feedback', 'admin.feedback', 'fa-solid fa-comment-dots', 14);


        // Settings
        $createMenu('Settings', 'admin.settings', 'fa-solid fa-gear', 15, [

            // -------------------------
            // General Settings Section
            // -------------------------
            ['title' => 'General Settings', 'slug' => 'admin.settings.general', 'order' => 1],
            ['title' => 'Email Settings', 'slug' => 'admin.settings.email', 'order' => 2],
            ['title' => 'SMS Settings', 'slug' => 'admin.settings.sms', 'order' => 3],

            // -------------------------
            // Organization Structure
            // -------------------------
            [
                'title' => 'Organization Structure',
                'slug'  => 'admin.org',
                'order' => 4,
                'children' => [
                    ['title' => 'Branches', 'slug' => 'admin.org.branches', 'order' => 1],
                    ['title' => 'Departments', 'slug' => 'admin.org.departments', 'order' => 2],
                    ['title' => 'Roles', 'slug' => 'admin.org.roles', 'order' => 3],
                    ['title' => 'Employees', 'slug' => 'admin.org.employees', 'order' => 4],
                ]
            ],

            // -------------------------
            // Product Masters
            // -------------------------
            [
                'title' => 'Product Masters',
                'slug'  => 'admin.masters',
                'order' => 5,
                'children' => [
                    ['title' => 'Categories', 'slug' => 'admin.masters.categories', 'order' => 1],
                    ['title' => 'Sub Categories', 'slug' => 'admin.masters.subcategories', 'order' => 2],
                    ['title' => 'Tags', 'slug' => 'admin.masters.tags', 'order' => 3],
                    ['title' => 'Material / Metal Types', 'slug' => 'admin.masters.materialtypes', 'order' => 4],
                    ['title' => 'Attributes', 'slug' => 'admin.masters.attributes', 'order' => 5],
                    ['title' => 'Additional Charges', 'slug' => 'admin.masters.charges', 'order' => 7],
                    ['title' => 'Tax', 'slug' => 'admin.masters.tax', 'order' => 8],
                    ['title' => 'Product Type', 'slug' => 'admin.masters.producttype', 'order' => 9],
                ]
            ],

            // -------------------------
            // SEO
            // -------------------------

            [
                'title' => 'SEO Masters',
                'slug'  => 'admin.seo',
                'order' => 6,
                'children' => [
                    ['title' => 'Meta Tags', 'slug' => 'admin.seo.meta', 'order' => 1],
                    ['title' => 'Sitemap', 'slug' => 'admin.seo.sitemap', 'order' => 2]
                ]
            ],
    
            // -------------------------
            // Logs
            // -------------------------

            [
                'title' => 'Logs',
                'slug'  => 'admin.logs',
                'order' => 7,
                'children' => [
                    ['title' => 'Activity Logs', 'slug' => 'admin.logs.activity', 'order' => 1],
                    ['title' => 'OTP Logs', 'slug' => 'admin.logs.otp', 'order' => 2],
                    ['title' => 'Email Logs', 'slug' => 'admin.logs.email', 'order' => 3],
                ]
            ],

        ]);


    }
}
