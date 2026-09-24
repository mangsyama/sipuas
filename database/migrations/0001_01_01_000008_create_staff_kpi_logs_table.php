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
        Schema::create('staff_kpi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->string('action_type', 30); // PENAMBAHAN, PEMOTONGAN, NETRAL, DIBATALKAN
            $table->string('kpi_category', 50)->nullable(); // KERAMAHAN, KEDISIPLINAN, SOP_PELAYANAN, INTEGRITAS
            $table->string('severity_level', 50)->nullable(); // RINGAN, SEDANG, BERAT, APRESIASI, CUSTOM
            $table->integer('points'); // e.g. 5 or -5
            $table->text('note')->nullable();
            $table->timestampTz('logged_at')->useCurrent();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_kpi_logs');
    }
};
