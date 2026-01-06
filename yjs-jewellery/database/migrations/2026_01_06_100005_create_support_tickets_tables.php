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
        // Support Tickets
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('subject');
            $table->text('description');
            $table->string('category', 50); // order_issue, product_inquiry, payment_issue, shipping, return_exchange, other
            $table->string('priority', 20)->default('normal'); // low, normal, high, urgent
            $table->string('status', 30)->default('open'); // open, assigned, in_progress, pending_customer, resolved, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->integer('rating')->nullable(); // Customer satisfaction 1-5
            $table->text('rating_feedback')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('status');
            $table->index('priority');
            $table->index('category');
        });

        // Ticket Messages (conversation thread)
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_type', 20); // customer, admin
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal_note')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        // Canned Responses
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('shortcode', 50)->unique();
            $table->text('content');
            $table->string('category', 50)->nullable();
            $table->json('variables')->nullable(); // {customer_name}, {order_number}, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Email Templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->string('category', 50); // order, payment, shipping, support, marketing, notification
            $table->json('variables')->nullable(); // Available variables
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System templates can't be deleted
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Communication Log (unified log for all customer communications)
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('channel', 30); // email, sms, whatsapp, push, ticket
            $table->string('type', 50); // order_confirmation, shipping_update, payment_reminder, etc.
            $table->string('direction', 10)->default('outbound'); // inbound, outbound
            $table->string('subject')->nullable();
            $table->text('content');
            $table->string('recipient')->nullable(); // email/phone
            $table->string('status', 30)->default('sent'); // pending, sent, delivered, failed, read
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('error_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'channel']);
            $table->index(['order_id', 'channel']);
            $table->index(['channel', 'status']);
        });

        // Ticket Status History
        Schema::create('ticket_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->onDelete('cascade');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_status_history');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('canned_responses');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
