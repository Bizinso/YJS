<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Category;
use App\Models\Product;
use App\Models\offers;
use App\Models\offerTypeMaster;
use App\Models\Order;
use App\Models\orderProduct;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Categories...');
        $this->createCategories();

        $this->command->info('Creating Products...');
        $this->createProducts();

        $this->command->info('Creating Additional Customers...');
        $this->createCustomers();

        $this->command->info('Creating Additional Partners...');
        $this->createPartners();

        $this->command->info('Creating Offers...');
        $this->createOffers();

        $this->command->info('Creating Orders...');
        $this->createOrders();

        $this->command->info('Test data seeded successfully!');
    }

    private function createCategories(): void
    {
        $categories = [
            ['name' => 'Gold Jewellery', 'slug' => 'gold-jewellery', 'description' => 'Exquisite gold jewellery collection', 'status' => 'A'],
            ['name' => 'Diamond Jewellery', 'slug' => 'diamond-jewellery', 'description' => 'Stunning diamond jewellery pieces', 'status' => 'A'],
            ['name' => 'Silver Jewellery', 'slug' => 'silver-jewellery', 'description' => 'Elegant silver jewellery collection', 'status' => 'A'],
            ['name' => 'Platinum Jewellery', 'slug' => 'platinum-jewellery', 'description' => 'Premium platinum jewellery', 'status' => 'A'],
            ['name' => 'Gemstone Jewellery', 'slug' => 'gemstone-jewellery', 'description' => 'Beautiful gemstone studded jewellery', 'status' => 'A'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }

        // Create subcategories
        $goldCat = Category::where('slug', 'gold-jewellery')->first();
        $diamondCat = Category::where('slug', 'diamond-jewellery')->first();
        $silverCat = Category::where('slug', 'silver-jewellery')->first();

        $subcategories = [
            ['name' => 'Gold Necklaces', 'slug' => 'gold-necklaces', 'parent_id' => $goldCat?->id, 'status' => 'A'],
            ['name' => 'Gold Bangles', 'slug' => 'gold-bangles', 'parent_id' => $goldCat?->id, 'status' => 'A'],
            ['name' => 'Gold Earrings', 'slug' => 'gold-earrings', 'parent_id' => $goldCat?->id, 'status' => 'A'],
            ['name' => 'Gold Chains', 'slug' => 'gold-chains', 'parent_id' => $goldCat?->id, 'status' => 'A'],
            ['name' => 'Gold Rings', 'slug' => 'gold-rings', 'parent_id' => $goldCat?->id, 'status' => 'A'],
            ['name' => 'Diamond Rings', 'slug' => 'diamond-rings', 'parent_id' => $diamondCat?->id, 'status' => 'A'],
            ['name' => 'Diamond Earrings', 'slug' => 'diamond-earrings', 'parent_id' => $diamondCat?->id, 'status' => 'A'],
            ['name' => 'Diamond Pendants', 'slug' => 'diamond-pendants', 'parent_id' => $diamondCat?->id, 'status' => 'A'],
            ['name' => 'Diamond Bracelets', 'slug' => 'diamond-bracelets', 'parent_id' => $diamondCat?->id, 'status' => 'A'],
            ['name' => 'Silver Anklets', 'slug' => 'silver-anklets', 'parent_id' => $silverCat?->id, 'status' => 'A'],
            ['name' => 'Silver Necklaces', 'slug' => 'silver-necklaces', 'parent_id' => $silverCat?->id, 'status' => 'A'],
        ];

        foreach ($subcategories as $sub) {
            if ($sub['parent_id']) {
                Category::updateOrCreate(['slug' => $sub['slug']], $sub);
            }
        }
    }

    private function createProducts(): void
    {
        $categories = Category::whereNull('parent_id')->get();
        $subcategories = Category::whereNotNull('parent_id')->get();

        $products = [
            // Gold Jewellery
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-necklaces', 'name' => 'Traditional Gold Necklace', 'sku' => 'GN-001', 'desc' => 'Beautiful traditional gold necklace with intricate design. Perfect for weddings.', 'price' => 125000, 'sale' => 118750, 'weight' => 25.5, 'stock' => 10],
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-bangles', 'name' => 'Gold Bangles Set (4 Pieces)', 'sku' => 'GB-001', 'desc' => 'Elegant set of 4 gold bangles with traditional motifs.', 'price' => 85000, 'sale' => null, 'weight' => 40, 'stock' => 15],
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-earrings', 'name' => 'Gold Earrings - Jhumka Style', 'sku' => 'GE-001', 'desc' => 'Classic jhumka style gold earrings with meenakari work.', 'price' => 35000, 'sale' => 32000, 'weight' => 12, 'stock' => 20],
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-chains', 'name' => 'Gold Chain - 24 inch', 'sku' => 'GC-001', 'desc' => 'Solid gold chain with secure clasp. 24 inch length.', 'price' => 65000, 'sale' => null, 'weight' => 15, 'stock' => 25],
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-rings', 'name' => 'Gold Wedding Ring', 'sku' => 'GR-001', 'desc' => 'Classic gold wedding ring for men.', 'price' => 28000, 'sale' => 26600, 'weight' => 8, 'stock' => 30],
            ['cat' => 'gold-jewellery', 'subcat' => 'gold-necklaces', 'name' => 'Kundan Bridal Necklace Set', 'sku' => 'GN-002', 'desc' => 'Stunning kundan bridal set with necklace and earrings.', 'price' => 245000, 'sale' => 232750, 'weight' => 55, 'stock' => 5],

            // Diamond Jewellery
            ['cat' => 'diamond-jewellery', 'subcat' => 'diamond-rings', 'name' => 'Diamond Solitaire Ring', 'sku' => 'DR-001', 'desc' => '1 Carat VVS1 diamond solitaire ring in 18K white gold.', 'price' => 250000, 'sale' => 237500, 'weight' => 5, 'stock' => 5],
            ['cat' => 'diamond-jewellery', 'subcat' => 'diamond-bracelets', 'name' => 'Diamond Tennis Bracelet', 'sku' => 'DB-001', 'desc' => 'Tennis bracelet with 3 carats of round brilliant diamonds.', 'price' => 350000, 'sale' => null, 'weight' => 15, 'stock' => 3],
            ['cat' => 'diamond-jewellery', 'subcat' => 'diamond-earrings', 'name' => 'Diamond Stud Earrings', 'sku' => 'DE-001', 'desc' => 'Classic diamond stud earrings, 0.5 carat each.', 'price' => 85000, 'sale' => 80750, 'weight' => 3, 'stock' => 12],
            ['cat' => 'diamond-jewellery', 'subcat' => 'diamond-pendants', 'name' => 'Diamond Pendant Necklace', 'sku' => 'DN-001', 'desc' => 'Elegant diamond pendant with 18 inch chain.', 'price' => 125000, 'sale' => null, 'weight' => 8, 'stock' => 8],
            ['cat' => 'diamond-jewellery', 'subcat' => 'diamond-rings', 'name' => 'Diamond Engagement Ring', 'sku' => 'DR-002', 'desc' => 'Brilliant cut diamond engagement ring in platinum.', 'price' => 175000, 'sale' => 166250, 'weight' => 4, 'stock' => 7],

            // Silver Jewellery
            ['cat' => 'silver-jewellery', 'subcat' => 'silver-anklets', 'name' => 'Silver Anklet Pair', 'sku' => 'SA-001', 'desc' => 'Traditional silver anklets with bells.', 'price' => 4500, 'sale' => 4050, 'weight' => 50, 'stock' => 30],
            ['cat' => 'silver-jewellery', 'subcat' => 'silver-necklaces', 'name' => 'Silver Oxidized Necklace', 'sku' => 'SN-001', 'desc' => 'Bohemian style oxidized silver necklace.', 'price' => 8500, 'sale' => 7650, 'weight' => 35, 'stock' => 20],
            ['cat' => 'silver-jewellery', 'subcat' => 'silver-anklets', 'name' => 'Silver Toe Rings Set', 'sku' => 'ST-001', 'desc' => 'Set of 4 adjustable silver toe rings.', 'price' => 1500, 'sale' => null, 'weight' => 10, 'stock' => 50],

            // Platinum
            ['cat' => 'platinum-jewellery', 'subcat' => null, 'name' => 'Platinum Wedding Band - Men', 'sku' => 'PM-001', 'desc' => 'Classic platinum wedding band for men. 6mm width.', 'price' => 75000, 'sale' => null, 'weight' => 12, 'stock' => 10],
            ['cat' => 'platinum-jewellery', 'subcat' => null, 'name' => 'Platinum Wedding Band - Women', 'sku' => 'PW-001', 'desc' => 'Elegant platinum wedding band with diamond accents.', 'price' => 65000, 'sale' => 61750, 'weight' => 8, 'stock' => 10],

            // Gemstone
            ['cat' => 'gemstone-jewellery', 'subcat' => null, 'name' => 'Ruby and Diamond Ring', 'sku' => 'GEM-001', 'desc' => 'Natural Burma ruby surrounded by diamonds.', 'price' => 185000, 'sale' => 175750, 'weight' => 6, 'stock' => 4],
            ['cat' => 'gemstone-jewellery', 'subcat' => null, 'name' => 'Emerald Pendant', 'sku' => 'GEM-002', 'desc' => 'Colombian emerald pendant with diamond halo.', 'price' => 145000, 'sale' => null, 'weight' => 5, 'stock' => 6],
            ['cat' => 'gemstone-jewellery', 'subcat' => null, 'name' => 'Blue Sapphire Earrings', 'sku' => 'GEM-003', 'desc' => 'Ceylon blue sapphire drop earrings.', 'price' => 125000, 'sale' => 112500, 'weight' => 7, 'stock' => 5],
        ];

        foreach ($products as $p) {
            $category = $categories->where('slug', $p['cat'])->first();
            $subcategory = $subcategories->where('slug', $p['subcat'])->first();

            if (!$category) continue;

            Product::updateOrCreate(
                ['sku' => $p['sku']],
                [
                    'category_id' => $category->id,
                    'sub_category_id' => $subcategory?->id,
                    'name' => $p['name'],
                    'slug' => Str::slug($p['name']),
                    'description' => $p['desc'],
                    'base_price' => $p['price'],
                    'final_price' => $p['sale'] ?? $p['price'],
                    'unit_price' => $p['sale'] ?? $p['price'],
                    'metal_weight' => $p['weight'],
                    'weight' => $p['weight'],
                    'initial_stock' => $p['stock'],
                    'available_stock' => $p['stock'],
                    'total_stock' => $p['stock'],
                    'is_featured' => rand(0, 1),
                    'visibility' => 1,
                    'status' => 'active',
                ]
            );
        }
    }

    private function createCustomers(): void
    {
        $customers = [
            ['first_name' => 'Priya', 'last_name' => 'Sharma', 'email' => 'priya.sharma@example.com', 'phone' => '9876543210'],
            ['first_name' => 'Rahul', 'last_name' => 'Verma', 'email' => 'rahul.verma@example.com', 'phone' => '9876543211'],
            ['first_name' => 'Anjali', 'last_name' => 'Patel', 'email' => 'anjali.patel@example.com', 'phone' => '9876543212'],
            ['first_name' => 'Vikram', 'last_name' => 'Singh', 'email' => 'vikram.singh@example.com', 'phone' => '9876543213'],
            ['first_name' => 'Neha', 'last_name' => 'Gupta', 'email' => 'neha.gupta@example.com', 'phone' => '9876543214'],
        ];

        foreach ($customers as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'first_name' => $c['first_name'],
                    'last_name' => $c['last_name'],
                    'mobile_code' => '+91',
                    'phone' => $c['phone'],
                    'password' => Hash::make('Customer@123'),
                    'user_type' => 'customer',
                    'status' => 'A',
                ]
            );

            Customer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'dob' => Carbon::now()->subYears(rand(25, 45))->format('Y-m-d'),
                ]
            );
        }
    }

    private function createPartners(): void
    {
        $partners = [
            ['first_name' => 'Rajesh', 'last_name' => 'Jewellers', 'email' => 'rajesh.jewellers@example.com', 'phone' => '9988776655', 'business_name' => 'Rajesh Gold & Diamonds', 'gst' => '27AABCU9603R1ZM'],
            ['first_name' => 'Shree', 'last_name' => 'Ornaments', 'email' => 'shree.ornaments@example.com', 'phone' => '9988776656', 'business_name' => 'Shree Ornaments Pvt Ltd', 'gst' => '29AABCU9603R1ZN'],
            ['first_name' => 'Golden', 'last_name' => 'Touch', 'email' => 'golden.touch@example.com', 'phone' => '9988776657', 'business_name' => 'Golden Touch Jewellers', 'gst' => '33AABCU9603R1ZP'],
        ];

        foreach ($partners as $p) {
            $user = User::updateOrCreate(
                ['email' => $p['email']],
                [
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'mobile_code' => '+91',
                    'phone' => $p['phone'],
                    'password' => Hash::make('Partner@123'),
                    'user_type' => 'partner',
                    'status' => 'A',
                ]
            );

            Partner::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $p['business_name'],
                    'business_type' => 'Retailer',
                    'phone_number' => $p['phone'],
                    'gst_number' => $p['gst'],
                    'address' => rand(1, 100) . ', Business District',
                    'city' => ['Mumbai', 'Surat', 'Jaipur'][rand(0, 2)],
                    'state' => ['Maharashtra', 'Gujarat', 'Rajasthan'][rand(0, 2)],
                    'status' => 'approved',
                ]
            );
        }
    }

    private function createOffers(): void
    {
        // Get existing offer types
        $percentType = offerTypeMaster::where('offer_type', 'percentage_discount')->first();
        $flatType = offerTypeMaster::where('offer_type', 'flat_discount')->first();
        $bogoType = offerTypeMaster::where('offer_type', 'bogo')->first();
        $freeShipType = offerTypeMaster::where('offer_type', 'free_shipping')->first();
        $flashType = offerTypeMaster::where('offer_type', 'flash_sale')->first();
        $couponType = offerTypeMaster::where('offer_type', 'public_coupon')->first();

        $goldCat = Category::where('slug', 'gold-jewellery')->first();
        $diamondCat = Category::where('slug', 'diamond-jewellery')->first();
        $silverCat = Category::where('slug', 'silver-jewellery')->first();

        $offers = [
            [
                'title' => 'Summer Gold Sale - 10% Off',
                'description' => 'Get 10% off on all gold jewellery this summer!',
                'offer_type_id' => $percentType?->id,
                'discount_percent' => 10,
                'apply_on' => 'category',
                'apply_on_value' => json_encode([$goldCat?->id]),
                'valid_from' => Carbon::now()->subDays(5),
                'valid_to' => Carbon::now()->addDays(25),
                'status' => 'active',
            ],
            [
                'title' => 'Diamond Dazzle - Flat Rs 5000 Off',
                'description' => 'Flat Rs 5000 off on diamond jewellery above Rs 50,000',
                'offer_type_id' => $flatType?->id,
                'discount_amount' => 5000,
                'apply_on' => 'category',
                'apply_on_value' => json_encode([$diamondCat?->id]),
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(30),
                'status' => 'active',
            ],
            [
                'title' => 'Buy 2 Get 1 Free - Silver',
                'description' => 'Buy any 2 silver items and get 1 free!',
                'offer_type_id' => $bogoType?->id,
                'buy_quantity' => 2,
                'get_quantity' => 1,
                'get_discount_percent' => 100,
                'apply_on' => 'category',
                'apply_on_value' => json_encode([$silverCat?->id]),
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(15),
                'status' => 'active',
            ],
            [
                'title' => 'Wedding Season - 15% Off',
                'description' => 'Special wedding season discount',
                'offer_type_id' => $percentType?->id,
                'discount_percent' => 15,
                'max_discount_amount' => 25000,
                'apply_on' => 'all',
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(60),
                'status' => 'active',
            ],
            [
                'title' => 'Free Shipping Above Rs 10,000',
                'description' => 'Free insured shipping on orders above Rs 10,000',
                'offer_type_id' => $freeShipType?->id,
                'apply_on' => 'all',
                'valid_from' => Carbon::now()->subDays(30),
                'valid_to' => Carbon::now()->addDays(365),
                'status' => 'active',
            ],
            [
                'title' => 'WELCOME10 - New Customer',
                'description' => 'Welcome offer for new customers - 10% off',
                'offer_type_id' => $couponType?->id ?? $percentType?->id,
                'discount_percent' => 10,
                'coupon_code' => 'WELCOME10',
                'apply_on' => 'all',
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addDays(365),
                'status' => 'active',
            ],
            [
                'title' => 'Flash Sale - 20% Off Everything',
                'description' => 'Limited time flash sale on selected products!',
                'offer_type_id' => $flashType?->id,
                'discount_percent' => 20,
                'is_flash_sale' => 1,
                'flash_stock_limit' => 50,
                'apply_on' => 'all',
                'valid_from' => Carbon::now(),
                'valid_to' => Carbon::now()->addHours(24),
                'status' => 'active',
            ],
        ];

        foreach ($offers as $offer) {
            if ($offer['offer_type_id']) {
                offers::updateOrCreate(['title' => $offer['title']], $offer);
            }
        }
    }

    private function createOrders(): void
    {
        $customers = Customer::with('user')->get();
        $products = Product::all();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No customers or products. Skipping orders.');
            return;
        }

        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
        $paymentStatuses = ['pending', 'paid', 'failed'];

        for ($i = 0; $i < 15; $i++) {
            $customer = $customers->random();
            $orderProducts = $products->random(rand(1, 3));
            $subtotal = 0;

            $orderCode = 'YJS-' . date('Ymd') . '-' . str_pad(Order::count() + $i + 1, 4, '0', STR_PAD_LEFT);

            $order = Order::create([
                'custom_order_code' => $orderCode,
                'order_date' => Carbon::now()->subDays(rand(0, 30)),
                'customer_type' => 'existing',
                'customer_id' => $customer->id,
                'email' => $customer->user->email,
                'order_status' => $statuses[rand(0, 4)],
                'payment_status' => $paymentStatuses[rand(0, 2)],
                'payment_method' => ['razorpay', 'cod', 'bank_transfer'][rand(0, 2)],
                'order_subtotal' => 0,
                'total_taxes' => 0,
                'shipping_charges' => rand(0, 1) ? 0 : 500,
                'total_charges' => 0,
                'order_total' => 0,
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'notes' => 'Test order ' . ($i + 1),
            ]);

            foreach ($orderProducts as $product) {
                $qty = rand(1, 2);
                $price = $product->final_price ?? $product->base_price ?? 10000;
                $itemTotal = $price * $qty;
                $subtotal += $itemTotal;

                orderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'discount' => 0,
                    'tax' => round($price * $qty * 0.03, 2),
                    'total' => $itemTotal,
                ]);
            }

            $tax = round($subtotal * 0.03, 2);
            $total = $subtotal + $tax + ($order->shipping_charges ?? 0);

            $order->update([
                'order_subtotal' => $subtotal,
                'total_taxes' => $tax,
                'order_total' => $total,
            ]);
        }
    }
}
