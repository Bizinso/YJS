<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advanced Offer Types Migration
 *
 * Adds support for:
 * - BOGO (Buy One Get One)
 * - Buy X Get Y offers
 * - Combo/Bundle offers
 * - Payment method offers
 * - Flash sales
 * - Free gift with purchase
 * - Tiered/slab discounts
 * - Birthday/occasion offers
 * - Referral rewards
 * - Loyalty points
 * - Offer stacking
 */
return new class extends Migration
{
    public function up(): void
    {
        // Extend offers table with advanced fields
        Schema::table('offers', function (Blueprint $table) {
            // Offer classification
            $table->string('offer_category')->nullable()->after('offer_type_option')
                ->comment('promotional, quantity_based, audience_based, coupon');

            // Quantity-based offers (BOGO, Buy X Get Y)
            $table->integer('buy_quantity')->nullable()->after('max_discount_amount')
                ->comment('Buy X quantity');
            $table->integer('get_quantity')->nullable()->after('buy_quantity')
                ->comment('Get Y quantity free/discounted');
            $table->decimal('get_discount_percent', 5, 2)->nullable()->after('get_quantity')
                ->comment('Discount on Y (100 = free)');
            $table->json('get_products')->nullable()->after('get_discount_percent')
                ->comment('Specific product IDs for Y (null = same as X)');

            // Combo/Bundle offers
            $table->json('combo_products')->nullable()
                ->comment('Required product IDs for combo');
            $table->decimal('combo_price', 15, 2)->nullable()
                ->comment('Fixed combo price');

            // Payment method offers
            $table->json('payment_methods')->nullable()
                ->comment('Eligible payment methods: upi, card, netbanking, cod');
            $table->json('card_networks')->nullable()
                ->comment('Specific card networks: visa, mastercard, rupay');
            $table->json('banks')->nullable()
                ->comment('Specific banks for offers');

            // Flash sale configuration
            $table->boolean('is_flash_sale')->default(false);
            $table->integer('flash_stock_limit')->nullable()
                ->comment('Limited quantity for flash sale');
            $table->integer('flash_sold_count')->default(0)
                ->comment('Items sold in flash sale');

            // Free gift configuration
            $table->json('free_gift_products')->nullable()
                ->comment('Product IDs given as free gifts');
            $table->integer('free_gift_quantity')->default(1);

            // Tiered/Slab discounts
            $table->json('tier_rules')->nullable()
                ->comment('JSON array of {min_value, max_value, discount_type, discount_value}');

            // Audience targeting
            $table->json('target_user_ids')->nullable()
                ->comment('Specific user IDs eligible');
            $table->json('target_user_tags')->nullable()
                ->comment('User tags like birthday_month, anniversary');
            $table->boolean('is_birthday_offer')->default(false);
            $table->integer('birthday_days_before')->default(0);
            $table->integer('birthday_days_after')->default(7);

            // Referral configuration
            $table->boolean('is_referral_offer')->default(false);
            $table->decimal('referrer_reward', 15, 2)->nullable()
                ->comment('Reward for person who referred');
            $table->decimal('referee_discount', 15, 2)->nullable()
                ->comment('Discount for new user');

            // Stacking rules
            $table->boolean('is_stackable')->default(false)
                ->comment('Can be combined with other offers');
            $table->json('stackable_with')->nullable()
                ->comment('Offer IDs this can stack with');
            $table->json('exclusive_with')->nullable()
                ->comment('Offer IDs this cannot combine with');
            $table->integer('stack_priority')->default(0)
                ->comment('Priority when stacking (higher = applied first)');

            // Nth item discount
            $table->integer('nth_item_number')->nullable()
                ->comment('Every Nth item gets discount');
            $table->decimal('nth_item_discount_percent', 5, 2)->nullable();

            // Display and UI
            $table->string('badge_text', 50)->nullable()
                ->comment('Badge shown on products: SALE, NEW, etc');
            $table->string('badge_color', 20)->nullable();
            $table->string('banner_image')->nullable();
            $table->text('terms_conditions')->nullable();

            // Analytics
            $table->integer('view_count')->default(0);
            $table->integer('usage_count')->default(0);
            $table->decimal('total_discount_given', 15, 2)->default(0);
        });

        // Create loyalty points system
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points_balance')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->integer('redeemed_points')->default(0);
            $table->string('tier')->default('bronze')
                ->comment('bronze, silver, gold, platinum');
            $table->timestamp('tier_updated_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        // Loyalty points transactions
        Schema::create('loyalty_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['earn', 'redeem', 'expire', 'bonus', 'adjustment']);
            $table->integer('points');
            $table->integer('balance_after');
            $table->string('description');
            $table->string('reference_type')->nullable()
                ->comment('order, referral, birthday, signup, etc');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        // Loyalty tiers configuration
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('min_points')->default(0);
            $table->integer('max_points')->nullable();
            $table->decimal('points_multiplier', 3, 2)->default(1.00)
                ->comment('Earn rate multiplier');
            $table->json('benefits')->nullable()
                ->comment('JSON array of tier benefits');
            $table->string('badge_icon')->nullable();
            $table->string('badge_color')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Referral system
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referee_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code');
            $table->enum('status', ['pending', 'completed', 'expired', 'cancelled'])->default('pending');
            $table->decimal('referrer_reward', 15, 2)->nullable();
            $table->decimal('referee_discount', 15, 2)->nullable();
            $table->boolean('referrer_rewarded')->default(false);
            $table->boolean('referee_used_discount')->default(false);
            $table->foreignId('referee_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('referral_code');
            $table->index(['referrer_id', 'status']);
            $table->unique(['referrer_id', 'referee_id']);
        });

        // User referral codes
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 20)->nullable()->unique()->after('email');
            $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code');
            $table->date('date_of_birth')->nullable()->after('referred_by');
            $table->date('anniversary_date')->nullable()->after('date_of_birth');

            $table->foreign('referred_by')->references('id')->on('users')->onDelete('set null');
        });

        // Flash sale inventory tracking
        Schema::create('flash_sale_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('allocated_stock');
            $table->integer('sold_stock')->default(0);
            $table->integer('reserved_stock')->default(0);
            $table->timestamps();

            $table->unique(['offer_id', 'product_id']);
        });

        // Offer bundle/combo tracking
        Schema::create('offer_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->string('bundle_name');
            $table->json('product_ids');
            $table->json('required_quantities')->nullable();
            $table->decimal('bundle_price', 15, 2)->nullable()
                ->comment('Fixed bundle price');
            $table->decimal('bundle_discount_percent', 5, 2)->nullable()
                ->comment('Percent off total');
            $table->integer('max_bundles_per_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Free gifts given with orders
        Schema::create('order_free_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('gift_value', 15, 2)->default(0);
            $table->timestamps();
        });

        // Wishlist price drop notifications
        Schema::create('price_drop_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('original_price', 15, 2);
            $table->decimal('target_price', 15, 2)->nullable()
                ->comment('Alert when price drops below this');
            $table->decimal('alert_percent', 5, 2)->default(10)
                ->comment('Alert when price drops by this percent');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_drop_alerts');
        Schema::dropIfExists('order_free_gifts');
        Schema::dropIfExists('offer_bundles');
        Schema::dropIfExists('flash_sale_inventory');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn(['referral_code', 'referred_by', 'date_of_birth', 'anniversary_date']);
        });

        Schema::dropIfExists('referrals');
        Schema::dropIfExists('loyalty_tiers');
        Schema::dropIfExists('loyalty_point_transactions');
        Schema::dropIfExists('loyalty_points');

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn([
                'offer_category',
                'buy_quantity', 'get_quantity', 'get_discount_percent', 'get_products',
                'combo_products', 'combo_price',
                'payment_methods', 'card_networks', 'banks',
                'is_flash_sale', 'flash_stock_limit', 'flash_sold_count',
                'free_gift_products', 'free_gift_quantity',
                'tier_rules',
                'target_user_ids', 'target_user_tags',
                'is_birthday_offer', 'birthday_days_before', 'birthday_days_after',
                'is_referral_offer', 'referrer_reward', 'referee_discount',
                'is_stackable', 'stackable_with', 'exclusive_with', 'stack_priority',
                'nth_item_number', 'nth_item_discount_percent',
                'badge_text', 'badge_color', 'banner_image', 'terms_conditions',
                'view_count', 'usage_count', 'total_discount_given',
            ]);
        });
    }
};
