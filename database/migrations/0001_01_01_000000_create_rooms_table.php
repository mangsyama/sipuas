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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150); // Nama Ruangan
            $table->string('building_name', 150)->nullable(); // Gedung (e.g. Gedung A, Gedung B)
            $table->string('location_floor', 100)->nullable(); // Lantai / Lokasi (e.g. Lantai 1, Lantai 2)
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
