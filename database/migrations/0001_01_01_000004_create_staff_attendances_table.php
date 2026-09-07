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
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->date('duty_date');
            $table->string('shift_name', 50)->default('PAGI'); // PAGI, SIANG, MALAM
            $table->dateTimeTz('check_in_at');
            $table->dateTimeTz('check_out_at')->nullable();
            $table->string('status', 30)->default('ON_DUTY'); // ON_DUTY, COMPLETED
            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index(['duty_date', 'room_id', 'status']);
            $table->index(['user_id', 'duty_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
