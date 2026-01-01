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
        Schema::create('additional_charges', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('type_id');

            $table->enum('charges_type', ['Percent', 'Amount'])
                ->default('Amount');

            $table->decimal('amount', 9, 3);

            $table->string('status')->default('active');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->softDeletes();

            // Foreign key
            $table->foreign('type_id')
                ->references('id')
                ->on('additional_charge_types')
                ->onDelete('cascade');  // optional: can change to restrict
      
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_charges');
    }
};
