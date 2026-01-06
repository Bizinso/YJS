<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Orders table indexes
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('orders'))->pluck('name')->toArray();
                if (!in_array('idx_orders_customer_id', $indexes)) {
                    $table->index('customer_id', 'idx_orders_customer_id');
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'status')) {
            Schema::table('orders', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('orders'))->pluck('name')->toArray();
                if (!in_array('idx_orders_status', $indexes)) {
                    $table->index('status', 'idx_orders_status');
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('orders'))->pluck('name')->toArray();
                if (!in_array('idx_orders_payment_status', $indexes)) {
                    $table->index('payment_status', 'idx_orders_payment_status');
                }
            });
        }

        // Products table indexes
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('products'))->pluck('name')->toArray();
                if (!in_array('idx_products_category_id', $indexes)) {
                    $table->index('category_id', 'idx_products_category_id');
                }
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'status')) {
            Schema::table('products', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('products'))->pluck('name')->toArray();
                if (!in_array('idx_products_status', $indexes)) {
                    $table->index('status', 'idx_products_status');
                }
            });
        }

        // Order items table indexes
        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'order_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('order_items'))->pluck('name')->toArray();
                if (!in_array('idx_order_items_order_id', $indexes)) {
                    $table->index('order_id', 'idx_order_items_order_id');
                }
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'product_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('order_items'))->pluck('name')->toArray();
                if (!in_array('idx_order_items_product_id', $indexes)) {
                    $table->index('product_id', 'idx_order_items_product_id');
                }
            });
        }

        // Customers table indexes
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'phone')) {
            Schema::table('customers', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('customers'))->pluck('name')->toArray();
                if (!in_array('idx_customers_phone', $indexes)) {
                    $table->index('phone', 'idx_customers_phone');
                }
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'email')) {
            Schema::table('customers', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('customers'))->pluck('name')->toArray();
                if (!in_array('idx_customers_email', $indexes)) {
                    $table->index('email', 'idx_customers_email');
                }
            });
        }

        // Partners table indexes
        if (Schema::hasTable('partners') && Schema::hasColumn('partners', 'phone')) {
            Schema::table('partners', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('partners'))->pluck('name')->toArray();
                if (!in_array('idx_partners_phone', $indexes)) {
                    $table->index('phone', 'idx_partners_phone');
                }
            });
        }

        if (Schema::hasTable('partners') && Schema::hasColumn('partners', 'status')) {
            Schema::table('partners', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('partners'))->pluck('name')->toArray();
                if (!in_array('idx_partners_status', $indexes)) {
                    $table->index('status', 'idx_partners_status');
                }
            });
        }

        // Inventory table indexes
        if (Schema::hasTable('inventory') && Schema::hasColumn('inventory', 'product_id')) {
            Schema::table('inventory', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('inventory'))->pluck('name')->toArray();
                if (!in_array('idx_inventory_product_id', $indexes)) {
                    $table->index('product_id', 'idx_inventory_product_id');
                }
            });
        }

        // Cart items table indexes
        if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'customer_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('cart_items'))->pluck('name')->toArray();
                if (!in_array('idx_cart_items_customer_id', $indexes)) {
                    $table->index('customer_id', 'idx_cart_items_customer_id');
                }
            });
        }

        // Payments table indexes
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'order_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('payments'))->pluck('name')->toArray();
                if (!in_array('idx_payments_order_id', $indexes)) {
                    $table->index('order_id', 'idx_payments_order_id');
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'status')) {
            Schema::table('payments', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('payments'))->pluck('name')->toArray();
                if (!in_array('idx_payments_status', $indexes)) {
                    $table->index('status', 'idx_payments_status');
                }
            });
        }

        // Shipments table indexes
        if (Schema::hasTable('shipments') && Schema::hasColumn('shipments', 'order_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('shipments'))->pluck('name')->toArray();
                if (!in_array('idx_shipments_order_id', $indexes)) {
                    $table->index('order_id', 'idx_shipments_order_id');
                }
            });
        }

        // Offers table indexes
        if (Schema::hasTable('offers') && Schema::hasColumn('offers', 'status')) {
            Schema::table('offers', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('offers'))->pluck('name')->toArray();
                if (!in_array('idx_offers_status', $indexes)) {
                    $table->index('status', 'idx_offers_status');
                }
            });
        }

        // Partner inquiries table indexes
        if (Schema::hasTable('partner_inquiries') && Schema::hasColumn('partner_inquiries', 'partner_id')) {
            Schema::table('partner_inquiries', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('partner_inquiries'))->pluck('name')->toArray();
                if (!in_array('idx_partner_inquiries_partner_id', $indexes)) {
                    $table->index('partner_id', 'idx_partner_inquiries_partner_id');
                }
            });
        }

        // Loyalty points table indexes
        if (Schema::hasTable('loyalty_points') && Schema::hasColumn('loyalty_points', 'customer_id')) {
            Schema::table('loyalty_points', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('loyalty_points'))->pluck('name')->toArray();
                if (!in_array('idx_loyalty_points_customer_id', $indexes)) {
                    $table->index('customer_id', 'idx_loyalty_points_customer_id');
                }
            });
        }

        // Referrals table indexes
        if (Schema::hasTable('referrals') && Schema::hasColumn('referrals', 'referrer_id')) {
            Schema::table('referrals', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('referrals'))->pluck('name')->toArray();
                if (!in_array('idx_referrals_referrer_id', $indexes)) {
                    $table->index('referrer_id', 'idx_referrals_referrer_id');
                }
            });
        }

        // Reviews table indexes
        if (Schema::hasTable('reviews') && Schema::hasColumn('reviews', 'product_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('reviews'))->pluck('name')->toArray();
                if (!in_array('idx_reviews_product_id', $indexes)) {
                    $table->index('product_id', 'idx_reviews_product_id');
                }
            });
        }

        // Wishlists table indexes
        if (Schema::hasTable('wishlists') && Schema::hasColumn('wishlists', 'customer_id')) {
            Schema::table('wishlists', function (Blueprint $table) {
                $indexes = collect(Schema::getIndexes('wishlists'))->pluck('name')->toArray();
                if (!in_array('idx_wishlists_customer_id', $indexes)) {
                    $table->index('customer_id', 'idx_wishlists_customer_id');
                }
            });
        }
    }

    public function down(): void
    {
        $dropIndexes = [
            'orders' => ['idx_orders_customer_id', 'idx_orders_status', 'idx_orders_payment_status'],
            'products' => ['idx_products_category_id', 'idx_products_status'],
            'order_items' => ['idx_order_items_order_id', 'idx_order_items_product_id'],
            'customers' => ['idx_customers_phone', 'idx_customers_email'],
            'partners' => ['idx_partners_phone', 'idx_partners_status'],
            'inventory' => ['idx_inventory_product_id'],
            'cart_items' => ['idx_cart_items_customer_id'],
            'payments' => ['idx_payments_order_id', 'idx_payments_status'],
            'shipments' => ['idx_shipments_order_id'],
            'offers' => ['idx_offers_status'],
            'partner_inquiries' => ['idx_partner_inquiries_partner_id'],
            'loyalty_points' => ['idx_loyalty_points_customer_id'],
            'referrals' => ['idx_referrals_referrer_id'],
            'reviews' => ['idx_reviews_product_id'],
            'wishlists' => ['idx_wishlists_customer_id'],
        ];

        foreach ($dropIndexes as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                    $existingIndexes = collect(Schema::getIndexes($tableName))->pluck('name')->toArray();
                    foreach ($indexes as $index) {
                        if (in_array($index, $existingIndexes)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }
};
