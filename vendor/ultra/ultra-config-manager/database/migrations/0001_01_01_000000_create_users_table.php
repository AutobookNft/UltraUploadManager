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
        // Check if table already exists to avoid errors if loadLaravelMigrations worked partially
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

         // Add password_reset_tokens table if needed by other tests or dependencies
         if (!Schema::hasTable('password_reset_tokens')) {
             Schema::create('password_reset_tokens', function (Blueprint $table) {
                 $table->string('email')->primary();
                 $table->string('token');
                 $table->timestamp('created_at')->nullable();
             });
         }

         // Add jobs/failed_jobs if any package dependency requires them in tests
        // if (!Schema::hasTable('jobs')) { ... }
        // if (!Schema::hasTable('failed_jobs')) { ... }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        // Schema::dropIfExists('failed_jobs');
        // Schema::dropIfExists('jobs');
    }
};