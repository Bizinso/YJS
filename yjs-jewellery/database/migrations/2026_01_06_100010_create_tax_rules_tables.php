<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tax Zones - geographical regions with specific tax rules
        Schema::create('tax_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->json('countries')->nullable(); // Array of country codes
            $table->json('states')->nullable(); // Array of state codes
            $table->json('pincodes')->nullable(); // Array of pincode patterns
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0); // Higher priority zones match first
            $table->timestamps();
        });

        // Tax Rules - specific tax calculations
        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('tax_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tax_type', ['gst', 'igst', 'cgst_sgst', 'vat', 'custom'])->default('gst');
            $table->decimal('rate', 5, 2); // Tax rate percentage
            $table->decimal('cgst_rate', 5, 2)->nullable();
            $table->decimal('sgst_rate', 5, 2)->nullable();
            $table->decimal('igst_rate', 5, 2)->nullable();
            $table->enum('apply_to', ['all', 'category', 'product', 'tag'])->default('all');
            $table->json('apply_to_ids')->nullable(); // IDs of categories/products/tags
            $table->decimal('min_amount', 12, 2)->nullable(); // Minimum order amount
            $table->decimal('max_amount', 12, 2)->nullable(); // Maximum order amount
            $table->enum('calculation_type', ['percentage', 'fixed'])->default('percentage');
            $table->boolean('is_inclusive')->default(false); // Tax included in price?
            $table->boolean('is_compound')->default(false); // Apply on top of other taxes?
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->index(['tax_zone_id', 'is_active']);
        });

        // Tax Exemptions - customers/products exempt from taxes
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->nullable();
            $table->enum('exemption_type', ['customer', 'product', 'category'])->default('customer');
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_rule_id')->nullable()->constrained()->nullOnDelete(); // If null, exempt from all
            $table->string('reason');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->json('documents')->nullable(); // Uploaded exemption documents
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['exemption_type', 'status']);
            $table->index(['customer_id', 'status']);
        });

        // Tax Rate History - audit trail of rate changes
        Schema::create('tax_rate_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_rule_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_rate', 5, 2)->nullable();
            $table->decimal('new_rate', 5, 2);
            $table->decimal('old_cgst', 5, 2)->nullable();
            $table->decimal('new_cgst', 5, 2)->nullable();
            $table->decimal('old_sgst', 5, 2)->nullable();
            $table->decimal('new_sgst', 5, 2)->nullable();
            $table->decimal('old_igst', 5, 2)->nullable();
            $table->decimal('new_igst', 5, 2)->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('effective_from');
            $table->timestamps();

            $table->index(['tax_rule_id', 'effective_from']);
        });

        // HSN/SAC Code mappings for GST compliance
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description');
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('cgst_rate', 5, 2)->nullable();
            $table->decimal('sgst_rate', 5, 2)->nullable();
            $table->decimal('igst_rate', 5, 2)->nullable();
            $table->decimal('cess_rate', 5, 2)->nullable();
            $table->enum('type', ['goods', 'services'])->default('goods');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Product HSN mappings
        Schema::create('product_hsn_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hsn_code_id')->constrained('hsn_codes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'hsn_code_id']);
        });

        // Tax Calculations Log - for order tax breakdown
        Schema::create('order_tax_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tax_name');
            $table->string('tax_code')->nullable();
            $table->enum('tax_type', ['gst', 'igst', 'cgst', 'sgst', 'vat', 'cess', 'custom']);
            $table->decimal('taxable_amount', 12, 2);
            $table->decimal('rate', 5, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->string('hsn_code')->nullable();
            $table->boolean('is_exempt')->default(false);
            $table->string('exemption_reason')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'tax_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tax_details');
        Schema::dropIfExists('product_hsn_mappings');
        Schema::dropIfExists('hsn_codes');
        Schema::dropIfExists('tax_rate_history');
        Schema::dropIfExists('tax_exemptions');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('tax_zones');
    }
};
