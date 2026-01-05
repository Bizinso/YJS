<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * B2B Partner Inquiry System:
     * - Partners browse products (no pricing)
     * - Partners create bulk order inquiries
     * - Admin reviews and processes inquiries offline
     * - System maintains records for tracking
     */
    public function up(): void
    {
        // Partner Inquiries (Bulk Order Requests)
        Schema::create('partner_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_code', 50)->unique();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Inquiry Status
            $table->enum('status', [
                'pending',      // Initial submission
                'under_review', // Admin is reviewing
                'quoted',       // Admin has provided quote
                'accepted',     // Partner accepted quote
                'rejected',     // Admin rejected inquiry
                'cancelled',    // Partner cancelled
                'processing',   // Order is being processed
                'shipped',      // Order shipped
                'delivered',    // Order delivered
                'completed'     // Fully completed
            ])->default('pending');

            // Inquiry Details
            $table->text('notes')->nullable(); // Partner's notes/requirements
            $table->text('admin_notes')->nullable(); // Internal admin notes
            $table->text('rejection_reason')->nullable();

            // Quote Details (filled by admin)
            $table->decimal('quoted_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('shipping_charges', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->nullable();
            $table->timestamp('quote_valid_until')->nullable();
            $table->timestamp('quoted_at')->nullable();

            // Partner Response
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('partner_response_notes')->nullable();

            // Delivery Details
            $table->foreignId('shipping_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->string('delivery_method')->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('courier_name')->nullable();
            $table->json('tracking_history')->nullable();

            // Payment (offline tracking)
            $table->enum('payment_status', ['pending', 'partial', 'paid', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // cash, bank_transfer, cheque, etc.
            $table->string('payment_reference')->nullable();
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('payment_date')->nullable();

            // Admin handling
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            // Priority
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['partner_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('inquiry_code');
        });

        // Partner Inquiry Items (Products in the inquiry)
        Schema::create('partner_inquiry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('partner_inquiries')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('products')->nullOnDelete();

            // Quantity
            $table->integer('quantity')->default(1);
            $table->string('unit')->default('pcs'); // pcs, kg, gram, etc.

            // Partner's Requirements
            $table->text('specifications')->nullable(); // Custom requirements
            $table->text('notes')->nullable();

            // Quote Details (filled by admin)
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0);

            // Fulfillment
            $table->integer('quantity_fulfilled')->default(0);
            $table->enum('item_status', [
                'pending',
                'confirmed',
                'out_of_stock',
                'partially_available',
                'ready',
                'shipped',
                'delivered'
            ])->default('pending');

            $table->timestamps();

            // Indexes
            $table->index(['inquiry_id', 'product_id']);
        });

        // Partner Inquiry Status History
        Schema::create('partner_inquiry_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('partner_inquiries')->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inquiry_id', 'created_at']);
        });

        // Partner Inquiry Communications
        Schema::create('partner_inquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('partner_inquiries')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('sender_type', ['partner', 'admin']);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['inquiry_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_inquiry_messages');
        Schema::dropIfExists('partner_inquiry_status_history');
        Schema::dropIfExists('partner_inquiry_items');
        Schema::dropIfExists('partner_inquiries');
    }
};
