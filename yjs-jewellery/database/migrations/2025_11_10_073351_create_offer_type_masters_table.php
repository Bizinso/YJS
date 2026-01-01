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
        Schema::create('offer_type_masters', function (Blueprint $table) {
            $table->id();
            $table->string('offer_type');
            $table->json('offer_type_option');
            $table->string('description')->nullable();
            $table->string('apply_to')->nullable();
            $table->string('apply_to_option')->nullable();
            $table->enum('status', ['A', 'I'])->default('A');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_type_masters');
    }
};
