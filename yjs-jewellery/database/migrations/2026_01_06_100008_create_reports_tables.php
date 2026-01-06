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
        // Saved Reports
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // sales, orders, inventory, customers, products, finance
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('format')->default('table'); // table, chart, pivot
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['type', 'created_by']);
        });

        // Scheduled Reports
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('frequency'); // daily, weekly, monthly
            $table->string('day_of_week')->nullable(); // for weekly
            $table->unsignedTinyInteger('day_of_month')->nullable(); // for monthly
            $table->time('time_of_day')->default('08:00:00');
            $table->json('recipients'); // email addresses
            $table->string('export_format')->default('xlsx'); // xlsx, csv, pdf
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });

        // Report Exports History
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('format'); // xlsx, csv, pdf
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->unsignedInteger('row_count')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('scheduled_report_id')->nullable()->constrained('scheduled_reports')->onDelete('set null');
            $table->timestamps();

            $table->index(['created_by', 'created_at']);
        });

        // Daily Snapshots for trend analysis
        Schema::create('daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->unsignedInteger('new_customers')->default(0);
            $table->unsignedInteger('new_partners')->default(0);
            $table->unsignedInteger('products_sold')->default(0);
            $table->decimal('average_order_value', 12, 2)->default(0);
            $table->unsignedInteger('returns_initiated')->default(0);
            $table->unsignedInteger('cancellations')->default(0);
            $table->decimal('refunds_processed', 15, 2)->default(0);
            $table->unsignedInteger('support_tickets')->default(0);
            $table->json('top_products')->nullable();
            $table->json('sales_by_category')->nullable();
            $table->json('additional_metrics')->nullable();
            $table->timestamps();

            $table->index('date');
        });

        // Audit Log (enhanced system audit)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event'); // create, update, delete, login, export, etc.
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_type')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('daily_snapshots');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('saved_reports');
    }
};
