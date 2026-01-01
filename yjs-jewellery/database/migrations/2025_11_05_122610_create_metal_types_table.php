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
        Schema::create('metal_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metal_name_id')->nullable()->constrained('metal_names')->nullOnDelete();
            $table->string('name')->nullable(); // e.g., Yellow Gold, White Gold
            $table->foreignId('purity_id')->nullable()->constrained('purities')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->decimal('density', 8, 3)->nullable();
            $table->decimal('price_per_gram', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_types');
    }
};
