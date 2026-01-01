<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADD Razorpay fields to EXISTING order_payments table
 * 
 * EXISTING fields preserved (NOT touched):
 *   - order_id, payment_mode, transaction_id, amount, status
 * 
 * NEW fields added for Razorpay integration
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            // Razorpay identifiers
            $table->string('razorpay_order_id', 50)->nullable()->after('status')->index();
            $table->string('razorpay_payment_id', 50)->nullable()->after('razorpay_order_id')->index();
            $table->string('currency', 10)->default('INR')->after('amount');

            // Payment method details (supplements existing payment_mode)
            $table->string('method', 50)->nullable()->comment('Razorpay method: upi, card, netbanking, wallet');
            $table->string('bank', 100)->nullable();
            $table->string('wallet', 50)->nullable();
            $table->string('vpa', 100)->nullable()->comment('UPI VPA');
            $table->string('card_last4', 4)->nullable();
            $table->string('card_network', 50)->nullable();

            // Fees
            $table->decimal('fee', 10, 2)->default(0)->comment('Razorpay fee in rupees');
            $table->decimal('tax', 10, 2)->default(0)->comment('GST on fee in rupees');

            // Error tracking
            $table->string('error_code', 100)->nullable();
            $table->text('error_description')->nullable();

            // Response storage
            $table->json('gateway_response')->nullable();

            // Timestamps
            $table->timestamp('captured_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_order_id',
                'razorpay_payment_id',
                'currency',
                'method',
                'bank',
                'wallet',
                'vpa',
                'card_last4',
                'card_network',
                'fee',
                'tax',
                'error_code',
                'error_description',
                'gateway_response',
                'captured_at',
            ]);
        });
    }
};
