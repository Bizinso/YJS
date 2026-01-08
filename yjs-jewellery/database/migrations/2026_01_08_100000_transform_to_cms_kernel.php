<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Transform to CMS Kernel architecture with immutable versioning
     */
    public function up(): void
    {
        // 1. Create Content Types table if not exists
        if (!Schema::hasTable('content_types')) {
            Schema::create('content_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->string('icon', 50)->nullable();
                $table->json('fields');
                $table->json('default_widgets')->nullable();
                $table->json('default_seo')->nullable();
                $table->boolean('supports_versioning')->default(true);
                $table->boolean('supports_scheduling')->default(true);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seed default content types
            DB::table('content_types')->insert([
                ['name' => 'Homepage', 'slug' => 'homepage', 'description' => 'Main website homepage', 'icon' => 'bi-house', 'fields' => json_encode([]), 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Landing Page', 'slug' => 'landing', 'description' => 'Marketing landing pages', 'icon' => 'bi-megaphone', 'fields' => json_encode([]), 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Static Page', 'slug' => 'static', 'description' => 'Standard content pages', 'icon' => 'bi-file-text', 'fields' => json_encode([]), 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Create Widgets registry table if not exists
        if (!Schema::hasTable('widgets')) {
            Schema::create('widgets', function (Blueprint $table) {
                $table->id();
                $table->string('identifier', 100)->unique();
                $table->string('name', 100);
                $table->string('category', 50);
                $table->text('description')->nullable();
                $table->string('icon', 50)->nullable();
                $table->json('config_schema');
                $table->json('default_config');
                $table->boolean('supports_data_binding')->default(false);
                $table->json('data_provider_types')->nullable();
                $table->string('admin_component', 255);
                $table->string('frontend_component', 255);
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            // Seed default widgets
            $widgets = [
                ['identifier' => 'hero', 'name' => 'Hero Banner', 'category' => 'layout', 'icon' => 'bi-image', 'supports_data_binding' => false],
                ['identifier' => 'rich-text', 'name' => 'Rich Text', 'category' => 'content', 'icon' => 'bi-text-paragraph', 'supports_data_binding' => false],
                ['identifier' => 'gallery', 'name' => 'Image Gallery', 'category' => 'media', 'icon' => 'bi-images', 'supports_data_binding' => false],
                ['identifier' => 'video', 'name' => 'Video Embed', 'category' => 'media', 'icon' => 'bi-play-circle', 'supports_data_binding' => false],
                ['identifier' => 'product-showcase', 'name' => 'Product Showcase', 'category' => 'ecommerce', 'icon' => 'bi-bag', 'supports_data_binding' => true],
                ['identifier' => 'category-grid', 'name' => 'Category Grid', 'category' => 'ecommerce', 'icon' => 'bi-grid', 'supports_data_binding' => true],
                ['identifier' => 'offer-banner', 'name' => 'Offer Banner', 'category' => 'ecommerce', 'icon' => 'bi-tag', 'supports_data_binding' => true],
                ['identifier' => 'cta', 'name' => 'Call to Action', 'category' => 'conversion', 'icon' => 'bi-cursor', 'supports_data_binding' => false],
                ['identifier' => 'testimonials', 'name' => 'Testimonials', 'category' => 'social', 'icon' => 'bi-chat-quote', 'supports_data_binding' => false],
                ['identifier' => 'contact-form', 'name' => 'Contact Form', 'category' => 'forms', 'icon' => 'bi-envelope', 'supports_data_binding' => false],
                ['identifier' => 'faq', 'name' => 'FAQ Accordion', 'category' => 'content', 'icon' => 'bi-question-circle', 'supports_data_binding' => false],
                ['identifier' => 'custom-html', 'name' => 'Custom HTML', 'category' => 'advanced', 'icon' => 'bi-code-slash', 'supports_data_binding' => false],
                ['identifier' => 'spacer', 'name' => 'Spacer/Divider', 'category' => 'layout', 'icon' => 'bi-distribute-vertical', 'supports_data_binding' => false],
            ];

            foreach ($widgets as $i => $widget) {
                DB::table('widgets')->insert([
                    'identifier' => $widget['identifier'],
                    'name' => $widget['name'],
                    'category' => $widget['category'],
                    'icon' => $widget['icon'],
                    'config_schema' => json_encode([]),
                    'default_config' => json_encode([]),
                    'supports_data_binding' => $widget['supports_data_binding'],
                    'data_provider_types' => $widget['supports_data_binding'] ? json_encode(['products', 'categories', 'offers']) : null,
                    'admin_component' => ucfirst(Str::camel($widget['identifier'])) . 'BlockPreview',
                    'frontend_component' => ucfirst(Str::camel($widget['identifier'])) . 'Block',
                    'is_system' => true,
                    'is_active' => true,
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Create Data Sources table if not exists
        if (!Schema::hasTable('data_sources')) {
            Schema::create('data_sources', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('identifier', 100)->unique();
                $table->string('provider_class', 255);
                $table->json('config');
                $table->integer('cache_ttl')->default(300);
                $table->enum('cache_strategy', ['none', 'tag', 'key'])->default('tag');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('data_sources')->insert([
                ['name' => 'Products', 'identifier' => 'products', 'provider_class' => 'App\\Services\\Cms\\Providers\\ProductProvider', 'config' => json_encode(['model' => 'App\\Models\\Product']), 'cache_ttl' => 300, 'cache_strategy' => 'tag', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Categories', 'identifier' => 'categories', 'provider_class' => 'App\\Services\\Cms\\Providers\\CategoryProvider', 'config' => json_encode(['model' => 'App\\Models\\Category']), 'cache_ttl' => 600, 'cache_strategy' => 'tag', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Offers', 'identifier' => 'offers', 'provider_class' => 'App\\Services\\Cms\\Providers\\OfferProvider', 'config' => json_encode(['model' => 'App\\Models\\Offer']), 'cache_ttl' => 60, 'cache_strategy' => 'tag', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 4. Create Taxonomies tables if not exist
        if (!Schema::hasTable('taxonomies')) {
            Schema::create('taxonomies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 100)->unique();
                $table->text('description')->nullable();
                $table->boolean('hierarchical')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('taxonomy_terms')) {
            Schema::create('taxonomy_terms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('taxonomy_id')->constrained('taxonomies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('taxonomy_terms')->nullOnDelete();
                $table->string('name', 255);
                $table->string('slug', 255);
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['taxonomy_id', 'slug']);
            });
        }

        // 5. Create CMS Audit Log if not exists
        if (!Schema::hasTable('cms_audit_log')) {
            Schema::create('cms_audit_log', function (Blueprint $table) {
                $table->id();
                $table->string('auditable_type', 100);
                $table->unsignedBigInteger('auditable_id');
                $table->string('action', 50);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['auditable_type', 'auditable_id']);
                $table->index('user_id');
                $table->index('created_at');
            });
        }

        // 6. Drop old pages table if exists and create new one
        if (Schema::hasTable('pages')) {
            Schema::drop('pages');
        }

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 255)->unique();
            $table->foreignId('content_type_id')->nullable()->constrained('content_types')->nullOnDelete();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->unsignedBigInteger('draft_version_id')->nullable();
            $table->unsignedBigInteger('scheduled_version_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('header_section_id')->nullable();
            $table->unsignedBigInteger('footer_section_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index('slug');
            $table->index('published_version_id');
            $table->index('scheduled_at');
        });

        // 7. Create new page_versions_kernel table
        Schema::create('page_versions_kernel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title', 255);
            $table->json('widgets');
            $table->json('layout_settings');
            $table->json('data_bindings');
            $table->json('custom_fields')->nullable();
            $table->json('seo');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->char('content_hash', 64);
            $table->unique(['page_id', 'version_number']);
            $table->index('status');
            $table->index('created_at');
        });

        // 8. Update global_sections to use versioning if columns don't exist
        if (!Schema::hasColumn('global_sections', 'published_version_id')) {
            Schema::table('global_sections', function (Blueprint $table) {
                $table->unsignedBigInteger('published_version_id')->nullable()->after('name');
                $table->unsignedBigInteger('draft_version_id')->nullable()->after('published_version_id');
                $table->boolean('is_default')->default(false)->after('is_active');
            });
        }

        // 9. Create global_section_versions table if not exists
        if (!Schema::hasTable('global_section_versions')) {
            Schema::create('global_section_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained('global_sections')->cascadeOnDelete();
                $table->unsignedInteger('version_number');
                $table->json('widgets');
                $table->json('settings');
                $table->text('change_note')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['section_id', 'version_number']);
            });
        }

        // 10. Create page_taxonomy_terms pivot table if not exists
        if (!Schema::hasTable('page_taxonomy_terms')) {
            Schema::create('page_taxonomy_terms', function (Blueprint $table) {
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->foreignId('term_id')->constrained('taxonomy_terms')->cascadeOnDelete();
                $table->primary(['page_id', 'term_id']);
            });
        }

        // 11. Update form_submissions to reference version_id if column doesn't exist
        if (Schema::hasTable('form_submissions') && !Schema::hasColumn('form_submissions', 'version_id')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('version_id')->nullable()->after('page_id');
            });
        }

        // 12. Update page_analytics to reference version_id if column doesn't exist
        if (Schema::hasTable('page_analytics') && !Schema::hasColumn('page_analytics', 'version_id')) {
            Schema::table('page_analytics', function (Blueprint $table) {
                $table->unsignedBigInteger('version_id')->nullable()->after('page_id');
            });
        }

        // 13. Migrate existing data from cms_pages to new structure
        $this->migrateExistingData();

        // 14. Add foreign keys for version pointers
        Schema::table('pages', function (Blueprint $table) {
            $table->foreign('published_version_id')->references('id')->on('page_versions_kernel')->nullOnDelete();
            $table->foreign('draft_version_id')->references('id')->on('page_versions_kernel')->nullOnDelete();
            $table->foreign('scheduled_version_id')->references('id')->on('page_versions_kernel')->nullOnDelete();
        });
    }

    /**
     * Migrate existing data from cms_pages to new structure
     */
    protected function migrateExistingData(): void
    {
        if (!Schema::hasTable('cms_pages')) {
            return;
        }

        $contentTypes = DB::table('content_types')->pluck('id', 'slug')->toArray();
        $existingPages = DB::table('cms_pages')->get();

        foreach ($existingPages as $oldPage) {
            $contentTypeSlug = match ($oldPage->type ?? 'static') {
                'homepage' => 'homepage',
                'landing' => 'landing',
                default => 'static'
            };
            $contentTypeId = $contentTypes[$contentTypeSlug] ?? $contentTypes['static'] ?? null;

            $pageId = DB::table('pages')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'slug' => $oldPage->slug,
                'content_type_id' => $contentTypeId,
                'created_by' => $oldPage->created_by ?? 1,
                'created_at' => $oldPage->created_at,
                'updated_at' => $oldPage->updated_at,
                'deleted_at' => $oldPage->deleted_at ?? null,
            ]);

            $content = [
                'title' => $oldPage->title,
                'widgets' => $oldPage->blocks,
                'layout_settings' => $oldPage->page_settings,
                'seo' => $oldPage->seo,
            ];
            $contentHash = hash('sha256', json_encode($content));

            $versionId = DB::table('page_versions_kernel')->insertGetId([
                'page_id' => $pageId,
                'version_number' => 1,
                'title' => $oldPage->title,
                'widgets' => $oldPage->blocks ?? json_encode([]),
                'layout_settings' => $oldPage->page_settings ?? json_encode([]),
                'data_bindings' => json_encode([]),
                'custom_fields' => json_encode([]),
                'seo' => $oldPage->seo ?? json_encode([]),
                'status' => ($oldPage->status ?? 'draft') === 'published' ? 'published' : 'draft',
                'change_note' => 'Migrated from legacy CMS',
                'created_by' => $oldPage->created_by ?? 1,
                'created_at' => $oldPage->created_at,
                'content_hash' => $contentHash,
            ]);

            DB::table('pages')->where('id', $pageId)->update([
                'draft_version_id' => $versionId,
                'published_version_id' => ($oldPage->status ?? 'draft') === 'published' ? $versionId : null,
            ]);
        }

        // Migrate global sections
        if (Schema::hasTable('global_sections') && Schema::hasTable('global_section_versions')) {
            $globalSections = DB::table('global_sections')->get();
            foreach ($globalSections as $section) {
                if ($section->published_version_id === null) {
                    $versionId = DB::table('global_section_versions')->insertGetId([
                        'section_id' => $section->id,
                        'version_number' => 1,
                        'widgets' => $section->blocks ?? json_encode([]),
                        'settings' => $section->settings ?? json_encode([]),
                        'change_note' => 'Migrated from legacy',
                        'created_by' => 1,
                        'created_at' => $section->created_at ?? now(),
                    ]);

                    DB::table('global_sections')->where('id', $section->id)->update([
                        'published_version_id' => $section->is_active ? $versionId : null,
                        'draft_version_id' => $versionId,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['published_version_id']);
            $table->dropForeign(['draft_version_id']);
            $table->dropForeign(['scheduled_version_id']);
        });

        if (Schema::hasColumn('form_submissions', 'version_id')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropColumn('version_id');
            });
        }

        if (Schema::hasColumn('page_analytics', 'version_id')) {
            Schema::table('page_analytics', function (Blueprint $table) {
                $table->dropColumn('version_id');
            });
        }

        if (Schema::hasColumn('global_sections', 'published_version_id')) {
            Schema::table('global_sections', function (Blueprint $table) {
                $table->dropColumn(['published_version_id', 'draft_version_id', 'is_default']);
            });
        }

        Schema::dropIfExists('page_taxonomy_terms');
        Schema::dropIfExists('global_section_versions');
        Schema::dropIfExists('page_versions_kernel');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('cms_audit_log');
        Schema::dropIfExists('taxonomy_terms');
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('data_sources');
        Schema::dropIfExists('widgets');
        Schema::dropIfExists('content_types');
    }
};
