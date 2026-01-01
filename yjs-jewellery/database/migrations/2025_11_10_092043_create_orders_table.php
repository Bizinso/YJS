<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('custom_order_code')->unique();
            $table->date('order_date')->nullable();
            $table->enum('customer_type', ['new', 'existing']);
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('country_code')->nullable();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();

            $table->string('shipping_method')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('shipping_charges', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->enum('order_status', [
                'pending', 'confirmed', 'processing', 'shipped',
                'pickup_generated', 'picked_up', 'delivered',
                'cancelled', 'returned'
            ])->default('pending');

            $table->enum('payment_status', ['pending', 'paid', 'unpaid', 'failed'])->default('pending');
            $table->string('gst_applied')->nullable();
            $table->decimal('gst_percentage', 5, 2)->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('total_summary', 10, 2)->nullable();
            $table->boolean('add_to_cart')->default(0);

            $table->string('shipment_id')->nullable();
            $table->string('shiprocket_order_id')->nullable();
            $table->string('shipping_status')->nullable();
            $table->string('awb_number')->nullable();
            $table->dateTime('pickup_scheduled_date')->nullable();
            $table->string('courier_id')->nullable();
            $table->string('courier_charges')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('payment_method')->nullable();

            $table->decimal('total_charges', 15, 2)->default(0.00);
            $table->decimal('total_taxes', 15, 2)->default(0.00);
            $table->decimal('order_subtotal', 15, 2)->default(0.00);
            $table->decimal('order_total', 15, 2)->default(0.00);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
