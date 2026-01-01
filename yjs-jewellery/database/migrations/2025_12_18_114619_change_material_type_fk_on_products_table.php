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
        Schema::table('products', function (Blueprint $table) {

            // 🔥 Drop old foreign key
            $table->dropForeign(['material_type_id']);

            // (optional but safe) drop column
            $table->dropColumn('material_type_id');
        });

        Schema::table('products', function (Blueprint $table) {

            // ✅ Add new FK to metal_names
            $table->foreignId('material_type_id')
                  ->nullable()
                  ->constrained('metal_names')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropForeign(['material_type_id']);
            $table->dropColumn('material_type_id');
        });

        Schema::table('products', function (Blueprint $table) {

            // rollback to old table
            $table->foreignId('material_type_id')
                  ->nullable()
                  ->constrained('metal_types')
                  ->nullOnDelete();
        });
    }
    
};
