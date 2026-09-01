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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('category', 50)->default('MEDIK'); // MEDIK, NON_MEDIK
            $table->string('risk_status', 30)->default('LOW_RISK'); // LOW_RISK, MEDIUM_RISK, HIGH_RISK
            $table->string('pic_name', 150)->nullable();
            $table->string('phone_contact', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
