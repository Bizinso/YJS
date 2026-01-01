<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CREATE NEW order_cancellations table
 * Tracks all order cancellations with reasons and refund status
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('cancelled_by', ['customer', 'admin', 'system']);
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->string('reason_code', 50);
            $table->text('reason_text')->nullable();
            $table->string('order_status_at_cancel', 50);
            $table->unsignedBigInteger('refund_id')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable(); // in rupees
            $table->string('refund_status', 50)->nullable();
            $table->timestamp('cancelled_at');
            $table->timestamps();

            $table->foreign('cancelled_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('refund_id')->references('id')->on('order_refunds')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellations');
    }
};
