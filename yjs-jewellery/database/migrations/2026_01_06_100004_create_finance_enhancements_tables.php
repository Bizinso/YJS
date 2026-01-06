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
        // Refunds table enhancement - for better refund workflow
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->string('refund_code', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('refund_type', 30); // full, partial
            $table->decimal('original_amount', 12, 2);
            $table->decimal('refund_amount', 12, 2);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->text('deduction_reason')->nullable();
            $table->string('status', 30)->default('pending'); // pending, under_review, approved, rejected, processing, completed, failed
            $table->string('source', 30); // return, cancellation, complaint, manual
            $table->foreignId('source_id')->nullable(); // ID of return_request, cancellation_request, etc.
            $table->string('source_type')->nullable(); // return_request, cancellation_request, etc.
            $table->string('refund_method', 30)->nullable(); // original_payment, bank_transfer, store_credit
            $table->string('reason_code', 50);
            $table->text('reason_description')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->string('payment_gateway', 50)->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->string('gateway_status')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });

        // Credit Notes
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number', 50)->unique();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('refund_id')->nullable()->constrained('refund_requests')->onDelete('set null');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('used_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->string('status', 20)->default('active'); // active, partially_used, exhausted, expired, cancelled
            $table->string('reason_code', 50);
            $table->text('reason_description')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        // Credit Note Usage History
        Schema::create('credit_note_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_used', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->timestamps();

            $table->index(['credit_note_id', 'order_id']);
        });

        // Payment Settlements (for reconciliation)
        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_id', 100)->unique();
            $table->string('payment_gateway', 50);
            $table->date('settlement_date');
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('fees', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->integer('transaction_count')->default(0);
            $table->string('status', 30)->default('pending'); // pending, reconciled, discrepancy
            $table->decimal('system_amount', 14, 2)->nullable(); // Amount according to our records
            $table->decimal('discrepancy', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('settlement_data')->nullable(); // Raw data from gateway
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['payment_gateway', 'settlement_date']);
            $table->index('status');
        });

        // Settlement Transactions (link settlements to orders)
        Schema::create('settlement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('payment_settlements')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable(); // Reference to order_payments
            $table->string('transaction_id', 100);
            $table->string('type', 30); // payment, refund
            $table->decimal('amount', 12, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->string('status', 30)->default('pending'); // pending, matched, unmatched
            $table->timestamp('transaction_date');
            $table->timestamps();

            $table->index(['settlement_id', 'status']);
            $table->index('transaction_id');
        });

        // Outstanding Payments (for tracking pending/failed payments)
        Schema::create('outstanding_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_outstanding', 12, 2);
            $table->string('status', 30)->default('pending'); // pending, partial, paid, overdue, written_off
            $table->date('due_date');
            $table->integer('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('due_date');
        });

        // Refund Status History
        Schema::create('refund_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained('refund_requests')->onDelete('cascade');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['refund_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_status_history');
        Schema::dropIfExists('outstanding_payments');
        Schema::dropIfExists('settlement_transactions');
        Schema::dropIfExists('payment_settlements');
        Schema::dropIfExists('credit_note_usages');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('refund_requests');
    }
};
