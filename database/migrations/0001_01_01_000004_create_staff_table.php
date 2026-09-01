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
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nip', 50)->nullable()->index();
            $table->string('name', 150);
            $table->string('role', 100); // e.g. Apoteker Pelaksana, Perawat Jaga, etc.
            $table->integer('total_points')->default(100);
            $table->integer('praise_count')->default(0);
            $table->integer('complaint_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_point_update_at')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
