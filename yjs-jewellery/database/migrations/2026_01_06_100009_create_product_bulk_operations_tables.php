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
        // Product Import Jobs
        Schema::create('product_import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->json('errors')->nullable();
            $table->json('options')->nullable(); // update_existing, skip_errors, etc.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Product Export Jobs
        Schema::create('product_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('all'); // all, selected, filtered
            $table->string('format')->default('xlsx'); // xlsx, csv
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('status')->default('pending');
            $table->string('file_path')->nullable();
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Product SEO Metadata
        if (!Schema::hasTable('product_seo')) {
            Schema::create('product_seo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->string('meta_title', 70)->nullable();
                $table->string('meta_description', 160)->nullable();
                $table->text('meta_keywords')->nullable();
                $table->string('og_title')->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots')->default('index,follow');
                $table->json('schema_markup')->nullable();
                $table->timestamps();

                $table->unique('product_id');
            });
        }

        // Product Status History
        Schema::create('product_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        // Bulk Price Updates
        Schema::create('bulk_price_updates', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // percentage, fixed, formula
            $table->decimal('value', 10, 2)->nullable();
            $table->string('formula')->nullable();
            $table->string('apply_to')->default('all'); // all, category, selected
            $table->json('product_ids')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('products_affected')->default(0);
            $table->json('preview_data')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_price_updates');
        Schema::dropIfExists('product_status_history');
        Schema::dropIfExists('product_seo');
        Schema::dropIfExists('product_export_jobs');
        Schema::dropIfExists('product_import_jobs');
    }
};
