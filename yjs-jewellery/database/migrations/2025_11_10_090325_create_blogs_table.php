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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->string('image')->nullable();
            $table->char('status', 1)
                ->default('A')
                ->comment('A = Active, I = Inactive, D = Draft');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('seo_master_table_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category_id');
            $table->index('user_id');
            $table->index('subcategory_id');

            // Foreign Keys
            $table->foreign('category_id')
                ->references('id')
                ->on('blog_categories')
                ->onDelete('cascade');

            $table->foreign('subcategory_id')
                ->references('id')
                ->on('blog_categories')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
