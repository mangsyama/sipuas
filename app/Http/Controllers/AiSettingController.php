<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Services\AiAnalysisService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingController extends Controller
{
    protected AiAnalysisService $aiService;

    public function __construct(AiAnalysisService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Display Groq AI Integration settings page.
     */
    public function index(Request $request): Response
    {
        $setting = AiSetting::getActiveSetting();

        $maskedKey = '';
        if (!empty($setting->api_key)) {
            $len = strlen($setting->api_key);
            $maskedKey = $len > 8 ? substr($setting->api_key, 0, 4) . str_repeat('•', max(0, $len - 8)) . substr($setting->api_key, -4) : '••••••••';
        }

        $groqModels = [
            [
                'id' => 'openai/gpt-oss-120b',
                'name' => 'GPT-OSS 120B (Flagship - Paling Cerdas & Akurat)',
            ],
            [
                'id' => 'qwen/qwen3.8-27b',
                'name' => 'Qwen 27B (Bahasa Indonesia Sangat Bagus & Cepat)',
            ],
            [
                'id' => 'openai/gpt-oss-20b',
                'name' => 'GPT-OSS 20B (Ultra Fast & Ringan)',
            ],
            [
                'id' => 'groq/compound',
                'name' => 'Groq Compound (Sistem Gabungan)',
            ],
        ];

        return Inertia::render('AiIntegration/Index', [
            'setting' => [
                'id' => $setting->id,
                'provider' => 'groq',
                'has_api_key' => !empty($setting->api_key),
                'masked_api_key' => $maskedKey,
                'api_key' => $setting->api_key ?: '',
                'model_name' => $setting->model_name ?: 'openai/gpt-oss-120b',
                'system_prompt' => $setting->system_prompt,
                'is_active' => (bool) $setting->is_active,
                'temperature' => (float) $setting->temperature,
                'last_tested_at' => $setting->last_tested_at ? $setting->last_tested_at->format('Y-m-d H:i:s') : null,
                'last_test_status' => $setting->last_test_status,
            ],
            'groqModels' => $groqModels,
        ]);
    }

    /**
     * Update Groq AI Integration settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string',
            'model_name' => 'required|string|max:100',
            'system_prompt' => 'nullable|string',
            'is_active' => 'required|boolean',
            'temperature' => 'required|numeric|min:0|max:1',
        ]);

        $setting = AiSetting::getActiveSetting();

        $updateData = [
            'provider' => 'groq',
            'model_name' => $validated['model_name'],
            'system_prompt' => $validated['system_prompt'],
            'is_active' => $validated['is_active'],
            'temperature' => $validated['temperature'],
        ];

        if (!empty($validated['api_key'])) {
            $updateData['api_key'] = trim($validated['api_key']);
        }

        $setting->update($updateData);

        return redirect()->back()->with('success', 'Konfigurasi Groq AI berhasil disimpan.');
    }

    /**
     * Test connection and run live analysis playground.
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'test_text' => 'required|string|min:5|max:1500',
            'unit_name' => 'nullable|string',
            'target_object' => 'nullable|string',
        ]);

        $setting = AiSetting::getActiveSetting();

        try {
            $startTime = microtime(true);
            $result = $this->aiService->analyzeReport(
                $validated['test_text'],
                $validated['unit_name'] ?? 'Instalasi Farmasi',
                $validated['target_object'] ?? 'Loket Penyerahan Obat'
            );
            $executionMs = round((microtime(true) - $startTime) * 1000);

            $setting->update([
                'last_tested_at' => Carbon::now(),
                'last_test_status' => 'CONNECTED',
            ]);

            return response()->json([
                'success' => true,
                'execution_time_ms' => $executionMs,
                'engine_used' => 'GROQ_AI',
                'analysis' => $result,
            ]);
        } catch (\Throwable $e) {
            $setting->update([
                'last_tested_at' => Carbon::now(),
                'last_test_status' => 'ERROR',
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
