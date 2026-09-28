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
        Schema::table('users', function (Blueprint $table) {
            $table->dateTimeTz('activation_requested_at')->nullable()->after('approved_at');
        });

        // Backfill: if there are any inactive users who already selected a room, mark their request time as their created_at
        DB::table('users')
            ->where('is_active', false)
            ->whereNotNull('room_id')
            ->update(['activation_requested_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activation_requested_at');
        });
    }
};
