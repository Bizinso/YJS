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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->boolean('is_free_product')->default(false);
            $table->integer('quantity')->default(0);

            $table->string('courier_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('courier_charges')->nullable();
            $table->string('cart_total')->nullable();

            $table->json('applied_offers')->nullable();
            $table->json('selected_free_products')->nullable();

            $table->decimal('total_discount', 15, 2)->default(0.00);
            $table->decimal('charges_total', 15, 2)->default(0.00);
            $table->decimal('tax_total', 15, 2)->default(0.00);
            $table->decimal('final_price', 15, 2)->default(0.00);

            $table->date('estimated_delivery_date')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
