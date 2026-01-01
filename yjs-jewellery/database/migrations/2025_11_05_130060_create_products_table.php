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
        Schema::create('products', function (Blueprint $table) {
             $table->id();

            // Basic details
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();

            // Relations
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->integer('product_type_id')->nullable();
            $table->foreignId('purity_karat_id')->nullable()->constrained('purities')->nullOnDelete();
            $table->foreignId('material_type_id')->nullable()->constrained('metal_types')->nullOnDelete();

            // Pricing and material details
            $table->decimal('metal_weight', 10, 3)->nullable()->comment('In grams');
            $table->decimal('metal_rate', 10, 2)->nullable()->comment('Rate per gram');
            $table->decimal('metal_value', 12, 2)->nullable()->comment('Calculated metal value');


            // Final pricing
            $table->string('unit_price')->nullable();
            $table->decimal('base_price', 12, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable()->comment('Before tax and additional charges');
            $table->decimal('additional_charges', 12, 2)->nullable();
            $table->decimal('taxes', 12, 2)->nullable();
            $table->decimal('discount', 12, 2)->nullable();
            $table->decimal('final_price', 12, 2)->nullable()->comment('After applying taxes and charges');

            // Stock & inventory
            $table->integer('initial_stock')->default(0);
            $table->integer('available_stock')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('visibility')->default(true);
            $table->bigInteger('views_count')->default(0);

            // Collection & optional data
            $table->string('collection')->nullable();
            $table->json('tags_id')->nullable(); // store tag IDs if not pivoted
            $table->json('attributes')->nullable(); // for dynamic attributes like size, weight, etc.
            $table->json('variant_options')->nullable(); 
            $table->json('variants')->nullable();

            $table->string('weight')->nullable();
            $table->string('dimensions')->nullable();
            $table->integer('total_stock')->nullable(true);
            $table->integer('low_stock')->nullable(true);

            $table->string('unit')->nullable(true);
            $table->decimal('length', 8, 2)->nullable(true);
            $table->decimal('width', 8, 2)->nullable(true);
            $table->decimal('height', 8, 2)->nullable(true);
            // Images & media
            $table->string('main_image')->nullable();
            $table->json('other_images')->nullable();
            $table->longText('video_url')->nullable();

            $table->json('related_product_ids')->nullable();
            $table->json('you_may_like_product_ids')->nullable();

            // SEO
            $table->string('seo_title')->nullable();
            $table->longText('meta_description')->nullable();
            $table->string('meta_slug_url')->nullable();

            // Status
            $table->enum('status', ['active', 'inactive','draft'])->default('active');
            $table->integer('parent_id')->default(0);
            $table->json('selected_attributes')->nullable();

            $table->enum('visible_to', ['customer', 'partner', 'both'])->default('customer');
            $table->json('visible_partner_ids')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
