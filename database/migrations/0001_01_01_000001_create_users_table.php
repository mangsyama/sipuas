<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'NEWID()' : 'NULL'))->unique();
            $table->foreignId('role_id')->default(5)->constrained('roles');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('nip', 50)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('name', 150);
            $table->string('email', 150);
            $table->dateTimeTz('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->string('profile_photo_path', 255)->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('telegram_chat_id', 50)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_on_duty')->default(false);
            $table->integer('total_points')->default(100);
            $table->integer('praise_count')->default(0);
            $table->integer('complaint_count')->default(0);
            $table->dateTimeTz('last_point_update_at')->nullable();
            $table->text('page_permissions')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('no action');
            $table->dateTimeTz('approved_at')->nullable();
            $table->boolean('system_notify_enabled')->default(true);
            $table->boolean('wa_notify_enabled')->default(true);
            $table->dateTimeTz('created_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('updated_at')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'SYSDATETIMEOFFSET()' : 'CURRENT_TIMESTAMP'));
            $table->dateTimeTz('deleted_at')->nullable();
        });

        // Add filtered unique indexes for SQL Server, or standard unique indexes for SQLite / MySQL
        if (DB::getDriverName() === 'sqlsrv') {
            DB::statement('CREATE UNIQUE INDEX uq_users_nip ON users(nip) WHERE deleted_at IS NULL AND nip IS NOT NULL');
            DB::statement('CREATE UNIQUE INDEX uq_users_email ON users(email) WHERE deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX uq_users_username ON users(username) WHERE deleted_at IS NULL AND username IS NOT NULL');
            DB::statement('CREATE INDEX idx_users_nip ON users(nip)');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email', 'uq_users_email');
                $table->unique('username', 'uq_users_username');
                $table->index('nip', 'idx_users_nip');
            });
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->dateTimeTz('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
