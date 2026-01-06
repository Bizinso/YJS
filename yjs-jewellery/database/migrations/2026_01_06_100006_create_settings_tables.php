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
        // Unified Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50); // store, payment, shipping, email, sms, currency, tax
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string'); // string, integer, boolean, json, encrypted
            $table->text('description')->nullable();
            $table->boolean('is_sensitive')->default(false); // For credentials
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
        });

        // Settings History (audit trail)
        Schema::create('settings_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained('system_settings')->onDelete('cascade');
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['group', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings_history');
        Schema::dropIfExists('system_settings');
    }
};
