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
        // The legacy Sena LPD database already owns this table. The guard
        // lets Laravel safely baseline an existing installation.
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('parent_id')->nullable()->index();
                $table->string('school_id', 20)->unique();
                $table->string('password_hash');
                $table->string('display_name', 150);
                $table->string('school_name')->nullable();
                $table->string('teacher_name', 200)->nullable();
                $table->string('position', 150)->nullable();
                $table->string('address_line')->nullable();
                $table->string('subdistrict', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('postal_code', 10)->nullable();
                $table->string('phone', 30)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('photo_path')->nullable();
                $table->string('role', 50)->default('subdistrict_admin');
                $table->string('status', 20)->default('active');
                $table->timestamps();
                $table->index(['role', 'status']);
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('school_id', 20)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        // Never drop users here: this migration can baseline the production
        // table that is shared with the current PHP application.
    }
};
