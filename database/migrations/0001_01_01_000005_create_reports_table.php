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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->default(DB::raw(DB::getDriverName() === 'sqlsrv' ? 'NEWID()' : 'NULL'))->unique();
            $table->string('ticket_number', 50)->unique(); // e.g. LP-2026-08-001
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('target_object', 255)->nullable(); // Nama staf/petugas/fasilitas spesifik
            $table->text('isi_laporan');
            $table->string('ai_sentiment', 30)->default('NETRAL'); // POSITIF, NEGATIF, NETRAL
            $table->string('ai_category', 100)->nullable(); // Pelayanan Ramah, Waktu Tunggu, Sarana & Prasarana, etc.
            $table->integer('ai_score')->default(0); // +5, -5, 0
            $table->string('ai_confidence', 20)->nullable(); // e.g. 96%
            $table->json('ai_metadata')->nullable(); // JSON response / token metadata
            $table->string('shift_info', 100)->nullable(); // Shift Pagi / Siang, Shift Malam
            $table->string('reporter_name', 150)->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('status', 30)->default('PENDING'); // PENDING, VERIFIED, IN_PROGRESS, RESOLVED, REJECTED
            $table->string('priority', 30)->default('NORMAL'); // LOW, NORMAL, HIGH, URGENT
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
