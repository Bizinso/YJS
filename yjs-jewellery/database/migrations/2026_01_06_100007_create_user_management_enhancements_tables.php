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
        // Login Attempts tracking
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_type')->nullable(); // customer, partner, employee
            $table->timestamps();

            $table->index(['email', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        // User Sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token_id')->unique(); // Sanctum token ID
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable(); // web, mobile, tablet
            $table->string('browser')->nullable();
            $table->string('platform')->nullable(); // Windows, iOS, Android
            $table->string('location')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index('last_activity_at');
        });

        // Role Permissions pivot (enhanced)
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->timestamps();

                $table->unique(['role_id', 'permission_id']);
            });
        }

        // Add enhancements to users table
        if (!Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
                $table->timestamp('email_verified_at')->nullable()->change();
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
                $table->boolean('is_locked')->default(false)->after('status');
                $table->timestamp('locked_until')->nullable()->after('is_locked');
                $table->string('lock_reason')->nullable()->after('locked_until');
                $table->integer('failed_login_attempts')->default(0)->after('lock_reason');
            });
        }

        // Add enhancements to roles table
        if (!Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('status');
                $table->integer('level')->default(0)->after('is_system'); // For hierarchy
            });
        }

        // User Notes (admin notes about users)
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->text('note');
            $table->string('type')->default('general'); // general, warning, important, support
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // User Verifications
        Schema::create('user_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // email, phone, document, kyc
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->json('data')->nullable(); // Verification data
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
        Schema::dropIfExists('user_notes');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('login_attempts');

        if (Schema::hasTable('role_permissions')) {
            Schema::dropIfExists('role_permissions');
        }

        if (Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'last_login_at', 'last_login_ip', 'phone_verified_at',
                    'is_locked', 'locked_until', 'lock_reason', 'failed_login_attempts'
                ]);
            });
        }

        if (Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['is_system', 'level']);
            });
        }
    }
};
