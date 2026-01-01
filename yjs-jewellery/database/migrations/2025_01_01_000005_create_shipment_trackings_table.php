<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CREATE NEW shipment_trackings table
 * Stores detailed Shiprocket tracking data
 * 
 * Note: Basic shipping info is stored in orders table (awb_number, courier_name, etc.)
 * This table stores extended tracking details and activity history
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('shiprocket_order_id', 50)->nullable();
            $table->string('shipment_id', 50)->nullable();
            $table->string('awb_code', 50)->nullable()->index();
            $table->integer('courier_company_id')->nullable();
            $table->string('courier_name', 100)->nullable();
            $table->string('current_status', 100)->nullable();
            $table->integer('current_status_id')->nullable();
            $table->string('current_location')->nullable();
            $table->json('activities')->nullable();
            $table->string('etd', 50)->nullable(); // Estimated delivery
            $table->boolean('is_delivered')->default(false);
            $table->boolean('is_rto')->default(false);
            $table->date('pickup_scheduled_date')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_trackings');
    }
};
