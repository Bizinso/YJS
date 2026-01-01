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
        Schema::create('metal_rate_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metal_type_id')->constrained('metal_types');
            $table->decimal('rate_per_gram', 10, 2);
            $table->date('effective_date_from');
            $table->date('effective_date_to');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metal_rate_masters');
    }
};
