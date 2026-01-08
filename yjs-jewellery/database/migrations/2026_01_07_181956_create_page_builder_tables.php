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
        // Page Templates (must be created first for FK reference)
        Schema::create('page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('thumbnail')->nullable();
            $table->json('blocks')->nullable();
            $table->json('settings')->nullable();
            $table->enum('category', ['homepage', 'landing', 'static', 'general'])->default('general');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // CMS Pages - main page builder table
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['homepage', 'static', 'landing', 'category'])->default('static');
            $table->json('blocks')->nullable();
            $table->json('page_settings')->nullable();
            $table->json('seo')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('page_templates')->nullOnDelete();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['slug', 'status']);
            $table->index(['type', 'status']);
        });

        // Page Versions - version history
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->integer('version');
            $table->json('blocks')->nullable();
            $table->json('page_settings')->nullable();
            $table->string('change_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['page_id', 'version']);
        });

        // Page Block Assets - media files used in blocks
        Schema::create('page_block_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('cms_pages')->cascadeOnDelete();
            $table->string('type'); // image, video, document
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->integer('file_size');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Global Sections - header/footer
        Schema::create('global_sections', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['header', 'footer']);
            $table->string('name');
            $table->json('blocks')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Form Submissions - contact form submissions
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->nullable()->constrained('cms_pages')->nullOnDelete();
            $table->string('block_id')->nullable(); // UUID of the form block
            $table->string('form_name')->nullable();
            $table->json('form_data');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'is_read']);
            $table->index('created_at');
        });

        // URL Redirects
        Schema::create('url_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_url')->unique();
            $table->string('new_url');
            $table->enum('type', ['301', '302'])->default('301');
            $table->unsignedInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['old_url', 'is_active']);
        });

        // Page Analytics - simple page view tracking
        Schema::create('page_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->decimal('avg_time_on_page', 8, 2)->nullable(); // seconds
            $table->decimal('bounce_rate', 5, 2)->nullable(); // percentage
            $table->timestamps();

            $table->unique(['page_id', 'date']);
            $table->index('date');
        });

        // SEO Audits - SEO analysis results
        Schema::create('seo_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('cms_pages')->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0); // 0-100
            $table->json('issues')->nullable(); // Array of issues found
            $table->json('suggestions')->nullable(); // Array of improvement suggestions
            $table->json('analysis_data')->nullable(); // Detailed analysis results
            $table->timestamp('last_audit_at')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('page_analytics');
        Schema::dropIfExists('url_redirects');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('global_sections');
        Schema::dropIfExists('page_block_assets');
        Schema::dropIfExists('page_versions');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('page_templates');
    }
};
