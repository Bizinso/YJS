<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CREATE NEW order_refunds table
 * Tracks all refund transactions via Razorpay API
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('payment_id');
            $table->string('refund_code', 50)->unique();
            $table->string('razorpay_refund_id', 50)->nullable()->index();
            $table->enum('type', ['full', 'partial']);
            $table->decimal('amount', 15, 2); // in rupees
            $table->string('reason', 100);
            $table->enum('status', ['initiated', 'pending', 'processed', 'failed'])->default('initiated');
            $table->string('failure_reason')->nullable();
            $table->json('gateway_response')->nullable();
            $table->unsignedBigInteger('initiated_by')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('order_payments')->onDelete('cascade');
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
    }
};
