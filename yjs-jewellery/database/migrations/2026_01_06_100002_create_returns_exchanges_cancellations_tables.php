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
        // Return Policy Settings
        Schema::create('return_policy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('policy_type')->default('standard'); // standard, extended, no_returns
            $table->integer('return_window_days')->default(7);
            $table->integer('exchange_window_days')->default(15);
            $table->integer('cancellation_window_hours')->default(24);
            $table->boolean('allow_partial_returns')->default(true);
            $table->boolean('require_images')->default(true);
            $table->boolean('require_reason')->default(true);
            $table->boolean('auto_approve_cancellations')->default(false);
            $table->decimal('restocking_fee_percent', 5, 2)->default(0);
            $table->json('non_returnable_categories')->nullable();
            $table->json('return_reasons')->nullable();
            $table->json('exchange_reasons')->nullable();
            $table->json('cancellation_reasons')->nullable();
            $table->text('return_instructions')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Return Requests
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->string('return_code', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'pickup_scheduled',
                'picked_up',
                'received',
                'inspected',
                'refund_initiated',
                'refund_completed',
                'closed'
            ])->default('pending');
            $table->enum('return_type', ['refund', 'store_credit'])->default('refund');
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('images')->nullable();

            // Admin fields
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            // Pickup details
            $table->string('pickup_address')->nullable();
            $table->timestamp('pickup_scheduled_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->string('pickup_tracking_number')->nullable();
            $table->string('pickup_courier')->nullable();

            // Inspection
            $table->enum('inspection_result', ['passed', 'failed', 'partial'])->nullable();
            $table->text('inspection_notes')->nullable();
            $table->timestamp('inspected_at')->nullable();

            // Refund details
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->decimal('restocking_fee', 12, 2)->default(0);
            $table->decimal('shipping_deduction', 12, 2)->default(0);
            $table->decimal('final_refund_amount', 12, 2)->nullable();
            $table->string('refund_method')->nullable(); // original_payment, store_credit, bank_transfer
            $table->string('refund_reference')->nullable();
            $table->timestamp('refund_initiated_at')->nullable();
            $table->timestamp('refund_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
        });

        // Return Request Items
        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->json('images')->nullable();
            $table->enum('condition', ['unopened', 'opened', 'damaged', 'defective', 'wrong_item'])->nullable();
            $table->enum('item_status', ['pending', 'approved', 'rejected', 'received', 'inspected'])->default('pending');
            $table->text('inspection_notes')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['return_request_id', 'product_id']);
        });

        // Exchange Requests
        Schema::create('exchange_requests', function (Blueprint $table) {
            $table->id();
            $table->string('exchange_code', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('return_request_id')->nullable()->constrained('return_requests')->onDelete('set null');
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'awaiting_return',
                'return_received',
                'processing',
                'shipped',
                'delivered',
                'closed'
            ])->default('pending');
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('images')->nullable();

            // Admin fields
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();

            // Price adjustment
            $table->decimal('original_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2)->nullable();
            $table->decimal('price_difference', 12, 2)->default(0);
            $table->enum('adjustment_type', ['none', 'pay_extra', 'refund_difference'])->default('none');
            $table->boolean('adjustment_paid')->default(false);

            // New order reference
            $table->foreignId('new_order_id')->nullable()->constrained('orders')->onDelete('set null');

            // Shipping
            $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->onDelete('set null');
            $table->string('tracking_number')->nullable();
            $table->string('courier_name')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
        });

        // Exchange Request Items
        Schema::create('exchange_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_request_id')->constrained('exchange_requests')->onDelete('cascade');
            $table->foreignId('original_order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->foreignId('original_product_id')->constrained('products')->onDelete('cascade');
            $table->integer('original_quantity');
            $table->foreignId('new_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('new_variant_id')->nullable()->constrained('products')->onDelete('set null');
            $table->integer('new_quantity')->nullable();
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->enum('item_status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending');
            $table->decimal('price_difference', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['exchange_request_id']);
        });

        // Cancellation Requests
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('cancellation_code', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', [
                'pending',
                'under_review',
                'approved',
                'rejected',
                'refund_initiated',
                'refund_completed',
                'closed'
            ])->default('pending');
            $table->enum('cancellation_type', ['full', 'partial'])->default('full');
            $table->string('reason_code')->nullable();
            $table->text('reason_description')->nullable();
            $table->text('customer_notes')->nullable();

            // Admin fields
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('auto_approved')->default(false);

            // Refund details
            $table->decimal('order_amount', 12, 2)->nullable();
            $table->decimal('cancellation_fee', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('refund_method')->nullable();
            $table->string('refund_reference')->nullable();
            $table->timestamp('refund_initiated_at')->nullable();
            $table->timestamp('refund_completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['order_id']);
            $table->index(['status', 'created_at']);
        });

        // Cancellation Request Items (for partial cancellations)
        Schema::create('cancellation_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_request_id')->constrained('cancellation_requests')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_products')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('item_amount', 12, 2)->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->enum('item_status', ['pending', 'approved', 'rejected', 'refunded'])->default('pending');
            $table->timestamps();

            $table->index(['cancellation_request_id']);
        });

        // Status History (unified for all request types)
        Schema::create('request_status_history', function (Blueprint $table) {
            $table->id();
            $table->string('request_type'); // return, exchange, cancellation
            $table->unsignedBigInteger('request_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['request_type', 'request_id']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_status_history');
        Schema::dropIfExists('cancellation_request_items');
        Schema::dropIfExists('cancellation_requests');
        Schema::dropIfExists('exchange_request_items');
        Schema::dropIfExists('exchange_requests');
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('return_policy_settings');
    }
};
