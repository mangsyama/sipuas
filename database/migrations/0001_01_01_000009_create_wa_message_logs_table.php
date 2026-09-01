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
        Schema::create('wa_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone', 30);
            $table->string('message_type', 50)->default('REPORT_RECEIVED'); // REPORT_RECEIVED, VERIFICATION_ALERT, RESOLVED_NOTIFICATION, TEST
            $table->text('content');
            $table->string('status', 30)->default('SENT'); // SENT, PENDING, FAILED
            $table->text('response_payload')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_message_logs');
    }
};
