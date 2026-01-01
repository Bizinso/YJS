<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CREATE NEW offer tracking tables
 * - offer_usages: Tracks who used which offer when
 * - order_offers: Snapshots offer details at order time (immutable)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Track offer usage for limits enforcement
        Schema::create('offer_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->decimal('discount_amount', 15, 2); // in rupees
            $table->timestamp('used_at');
            $table->boolean('reversed')->default(false);
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['offer_id', 'customer_id']);
        });

        // Snapshot offer at order time (immutable record)
        Schema::create('order_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->string('offer_code', 50)->nullable();
            $table->string('offer_title', 200); // Uses existing 'title' field name
            $table->unsignedBigInteger('offer_type_id')->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('discount_percent', 10, 2)->nullable();
            $table->decimal('applied_discount', 15, 2); // Actual discount applied
            $table->string('coupon_code_used', 50)->nullable();
            $table->json('offer_snapshot'); // Full offer details at time of order
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('set null');
            $table->foreign('offer_type_id')->references('id')->on('offer_type_masters')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_offers');
        Schema::dropIfExists('offer_usages');
    }
};
