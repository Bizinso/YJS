<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD payment timestamp to EXISTING orders table
 * 
 * NOTE: Shiprocket fields ALREADY EXIST in orders table:
 *   - shiprocket_order_id ✅
 *   - shipment_id ✅
 *   - awb_number ✅
 *   - courier_name ✅
 *   - courier_id ✅
 *   - shipping_status ✅
 *   - pickup_scheduled_date ✅
 *   - delivery_date ✅
 * 
 * Only adding: paid_at timestamp for payment tracking
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Payment timestamp
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'paid_at')) {
                $table->dropColumn('paid_at');
            }
        });
    }
};
