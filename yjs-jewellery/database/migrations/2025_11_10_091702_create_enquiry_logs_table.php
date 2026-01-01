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
        Schema::create('enquiry_logs', function (Blueprint $table) {
            $table->foreignId('enquiry_id')
                ->constrained('enquiries')
                ->onDelete('cascade');
            $table->string('action', 255);
            $table->integer('action_by', 255);
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry_logs');
    }
};
