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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->default('gemini'); // gemini, groq, openai, custom
            $table->text('api_key')->nullable();
            $table->string('model_name', 100)->default('gemini-1.5-flash');
            $table->text('system_prompt')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('temperature', 3, 2)->default(0.2);
            $table->integer('max_tokens')->default(1000);
            $table->timestampTz('last_tested_at')->nullable();
            $table->string('last_test_status', 50)->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
