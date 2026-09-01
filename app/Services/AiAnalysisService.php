<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    /**
     * Analyze patient report text using pure Groq AI.
     *
     * @param string $text
     * @param string|null $unitName
     * @param string|null $targetObject
     * @return array
     */
    public function analyzeReport(string $text, ?string $unitName = null, ?string $targetObject = null): array
    {
        $setting = AiSetting::getActiveSetting();

        if (empty($setting->api_key)) {
            throw new \Exception('API Key Groq belum dikonfigurasi. Silakan isi API Key di menu Integrasi AI.');
        }

        $model = $setting->model_name ?: 'openai/gpt-oss-120b';
        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $systemInstruction = $setting->system_prompt ?? "Anda adalah AI Triase Pelayanan Pasien Rumah Sakit (SIPUAS). Analisis teks laporan secara objektif dan berikan output HANYA dalam format JSON valid.";
        $prompt = "Informasi Unit: " . ($unitName ?? 'Umum') . "\nObjek/Sasaran: " . ($targetObject ?? 'Tidak ada') . "\nIsi Laporan Pasien:\n\"" . $text . "\"";

        $response = Http::withToken(trim($setting->api_key))
            ->timeout(15)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => (float) $setting->temperature,
                'max_tokens' => (int) $setting->max_tokens,
            ]);

        if (!$response->successful()) {
            $errorMsg = $response->json()['error']['message'] ?? $response->body();
            Log::error('Groq AI API Error: ' . $errorMsg);
            throw new \Exception('Gagal menghubungi Groq AI: ' . $errorMsg);
        }

        $data = $response->json();
        $rawContent = $data['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($rawContent, true);

        if (!is_array($parsed) || !isset($parsed['sentiment'])) {
            throw new \Exception('Format respon dari Groq AI tidak valid atau kosong.');
        }

        return $this->normalizeAiResponse($parsed, $text);
    }

    /**
     * Normalize Groq AI Output structure.
     */
    private function normalizeAiResponse(array $parsed, string $originalText): array
    {
        $sentiment = strtoupper($parsed['sentiment'] ?? 'NETRAL');
        if (!in_array($sentiment, ['POSITIF', 'NEGATIF', 'NETRAL'])) {
            $sentiment = str_contains($sentiment, 'POS') ? 'POSITIF' : (str_contains($sentiment, 'NEG') ? 'NEGATIF' : 'NETRAL');
        }

        $score = isset($parsed['score']) ? (int) $parsed['score'] : ($sentiment === 'POSITIF' ? 5 : ($sentiment === 'NEGATIF' ? -5 : 0));
        $category = $parsed['category'] ?? 'Pelayanan Umum';
        $confidence = $parsed['confidence'] ?? '98%';
        $summary = $parsed['summary'] ?? mb_substr($originalText, 0, 100) . '...';
        $urgency = strtoupper($parsed['urgency'] ?? ($sentiment === 'NEGATIF' ? 'TINGGI' : 'RENDAH'));
        $mentionedEntities = (array) ($parsed['mentioned_entities'] ?? []);
        $actionRecommendation = $parsed['action_recommendation'] ?? 'Lakukan evaluasi dan tindak lanjut standar unit.';

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => $confidence,
            'category' => $category,
            'summary' => $summary,
            'urgency' => $urgency,
            'mentioned_entities' => $mentionedEntities,
            'action_recommendation' => $actionRecommendation,
            'engine' => 'GROQ_AI',
        ];
    }
}
