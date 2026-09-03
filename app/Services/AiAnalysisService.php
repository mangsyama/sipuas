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

        // If no API key configured, use smart heuristic fallback immediately
        if (empty($setting->api_key)) {
            Log::info('Groq API Key belum dikonfigurasi. Menggunakan analisis heuristik cerdas bawaan.');
            return $this->fallbackHeuristicAnalysis($text, $unitName, $targetObject);
        }

        $model = $setting->model_name ?: 'llama-3.3-70b-versatile';
        $url = 'https://api.groq.com/openai/v1/chat/completions';

        $systemInstruction = $setting->system_prompt ?? "Anda adalah AI Triase & Konsultan Manajemen Mutu Pelayanan Publik Rumah Sakit (SIPUAS).
Analisis teks laporan masyarakat secara objektif dan berikan output HANYA dalam format JSON valid dengan struktur:
{
  \"sentiment\": \"POSITIF\" | \"NETRAL\" | \"NEGATIF\",
  \"score\": integer (-10 s/d 10),
  \"confidence\": string (misal \"95%\"),
  \"category\": string (pilihan: Pelayanan Medis, Sikap & Komunikasi Staf, Farmasi & Obat, Sarana & Fasilitas, Waktu Tunggu & Antrean, Administrasi & Keuangan, Pelayanan Umum),
  \"summary\": string (ringkasan kronologi 1-2 kalimat),
  \"urgency\": \"RENDAH\" | \"NORMAL\" | \"TINGGI\" | \"KRITIS\",
  \"mentioned_entities\": array string (nama staf atau fasilitas yang terdeteksi),
  \"action_recommendation\": string (rekomendasi manajerial solutif, mendalam, dan komprehensif 2-4 kalimat untuk Kepala Ruangan/Kasi. PENTING: Jika sentimen NETRAL atau komplain sarana umum, tegaskan bahwa laporan bersifat NETRAL (0 Poin KPI) dan tidak memotong poin staf).
}";
        $prompt = "Informasi Unit: " . ($unitName ?? 'Umum') . "\nObjek/Sasaran: " . ($targetObject ?? 'Tidak ada') . "\nIsi Laporan Pasien:\n\"" . $text . "\"";

        try {
            $response = Http::withToken(trim($setting->api_key))
                ->timeout(8)
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

            if ($response->successful()) {
                $data = $response->json();
                $rawContent = $data['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($rawContent, true);

                if (is_array($parsed) && isset($parsed['sentiment'])) {
                    return $this->normalizeAiResponse($parsed, $text);
                }
            } else {
                $errorMsg = $response->json()['error']['message'] ?? $response->body();
                Log::warning('Groq AI API Warning: ' . $errorMsg . '. Mengalihkan ke analisis heuristik.');
            }
        } catch (\Throwable $e) {
            Log::warning('Groq AI Connection Error: ' . $e->getMessage() . '. Mengalihkan ke analisis heuristik.');
        }

        return $this->fallbackHeuristicAnalysis($text, $unitName, $targetObject);
    }

    /**
     * Smart Rule-Based Heuristic Fallback Analysis for Hospital Complaints/Praise.
     */
    public function fallbackHeuristicAnalysis(string $text, ?string $unitName = null, ?string $targetObject = null): array
    {
        $lower = strtolower($text);

        $positiveWords = [
            'terima kasih', 'makasih', 'puas', 'bagus', 'ramah', 'cepat', 'baik', 'mantap', 
            'rapi', 'bersih', 'sopan', 'senyum', 'sigap', 'profesional', 'hebat', 'nyaman', 
            'terbantu', 'salut', 'jempol', 'berterima kasih', 'senang', 'memuaskan', 'tulus',
            'telaten', 'sabar', 'cekatan', 'membantu'
        ];

        $negativeWords = [
            'kecewa', 'lambat', 'lama', 'rusak', 'kotor', 'kasar', 'buruk', 'antri', 'antre', 
            'parah', 'bocor', 'mati', 'bau', 'cuek', 'marah', 'kesal', 'judes', 'tidak ramah', 
            'jelek', 'keluhan', 'komplain', 'sakit', 'salah', 'terbengkalai', 'diabaikan', 
            'nunggu lama', 'lamban', 'bobrok', 'kurang ajar', 'jutek', 'ogah', 'panas', 'pesing'
        ];

        $posCount = 0;
        foreach ($positiveWords as $w) {
            if (str_contains($lower, $w)) {
                $posCount++;
            }
        }

        $negCount = 0;
        foreach ($negativeWords as $w) {
            if (str_contains($lower, $w)) {
                $negCount++;
            }
        }

        // 1. Category classification first
        if (preg_match('/(obat|resep|racik|farmasi|dosis|apotek)/i', $lower)) {
            $category = 'Farmasi & Obat';
        } elseif (preg_match('/(dokter|perawat|suster|bidan|tindakan|infus|suntik|diagnosa|medis|operasi)/i', $lower)) {
            $category = 'Pelayanan Medis';
        } elseif (preg_match('/(ac|kran|air|toilet|wc|lampu|kursi|kamar|bed|ruangan|pintu|lift|kotor|bau|sarana|fasilitas|panas|rusak|bocor)/i', $lower)) {
            $category = 'Sarana & Fasilitas';
        } elseif (preg_match('/(antre|antri|lama|nunggu|waktu|panggilan|jam|telat|keterlambatan)/i', $lower)) {
            $category = 'Waktu Tunggu & Antrean';
        } elseif (preg_match('/(kasir|biaya|tarif|bpjs|kartu|daftar|pendaftaran|loket|administrasi)/i', $lower)) {
            $category = 'Administrasi & Keuangan';
        } else {
            $category = 'Sikap & Komunikasi Staf';
        }

        $targetObjStr = $targetObject ? "terhadap {$targetObject}" : '';
        $unitStr = $unitName ? "di unit {$unitName}" : 'di unit pelayanan';

        // 2. Sentiment determination & Rich Multi-Sentence Action Recommendation
        if ($negCount > $posCount) {
            $sentiment = 'NEGATIF';
            $score = -5;
            $urgency = ($negCount >= 2 || str_contains($lower, 'darurat') || str_contains($lower, 'parah')) ? 'TINGGI' : 'NORMAL';
            $recommendation = "Laporan menunjukkan keluhan masyarakat {$unitStr} {$targetObjStr} pada aspek {$category}. Disarankan Kepala Ruangan/Kasi segera memanggil dan mengklarifikasi kronologi kejadian bersama staf jaga shift terkait. Tinjau kepatuhan terhadap SOP pelayanan, dan terapkan pemotongan poin pembinaan (- Poin KPI) jika terbukti terjadi kelalaian atau ketidaksigapan petugas.";
        } elseif ($posCount > $negCount) {
            $sentiment = 'POSITIF';
            $score = 5;
            $urgency = 'RENDAH';
            $recommendation = "Apresiasi Pelayanan Prima: Masyarakat menyampaikan kepuasan dan apresiasi atas dedikasi pelayanan {$unitStr} {$targetObjStr} pada aspek {$category}. Direkomendasikan kepada Kepala Ruangan/Kasi untuk memberikan apresiasi formal dan mengalokasikan penambahan poin reward (+ Poin KPI) kepada staf yang bertugas pada shift tersebut guna mempertahankan motivasi keunggulan kerja.";
        } else {
            $sentiment = 'NETRAL';
            $score = 0;
            $urgency = 'NORMAL';
            $recommendation = "Laporan ini bersifat masukan informatif atau terkait sarana/kondisi fisik rumah sakit {$unitStr} tanpa adanya bukti pelanggaran etika langsung oleh petugas. Sesuai ketentuan, laporan ini berstatus TINDAKAN NETRAL (0 Poin KPI) dan TIDAK dikenakan pemotongan maupun penambahan poin staf. Kasi disarankan berkoordinasi dengan instalasi terkait/pemeliharaan sarana untuk tindak lanjut perbaikan.";
        }

        $summary = mb_substr($text, 0, 140);
        if (mb_strlen($text) > 140) {
            $summary .= '...';
        }

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => '92%',
            'category' => $category,
            'summary' => $summary,
            'urgency' => $urgency,
            'mentioned_entities' => array_values(array_filter([$unitName, $targetObject])),
            'action_recommendation' => $recommendation,
            'recommendation' => $recommendation,
            'engine' => 'HEURISTIC_RULE_BASED',
        ];
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
        $summary = $parsed['summary'] ?? mb_substr($originalText, 0, 120) . '...';
        $urgency = strtoupper($parsed['urgency'] ?? ($sentiment === 'NEGATIF' ? 'TINGGI' : 'RENDAH'));
        $mentionedEntities = (array) ($parsed['mentioned_entities'] ?? []);
        
        $actionRecommendation = $parsed['action_recommendation'] ?? ($parsed['recommendation'] ?? '');
        if (empty($actionRecommendation) || strlen($actionRecommendation) < 20) {
            if ($sentiment === 'POSITIF') {
                $actionRecommendation = "Apresiasi Pelayanan Prima: Pasien memberikan tanggapan sangat baik atas mutu layanan pada aspek {$category}. Disarankan Kasi memberikan pengakuan kinerja dan mengalokasikan penambahan poin reward (+ Poin KPI) kepada staf shift bertugas.";
            } elseif ($sentiment === 'NEGATIF') {
                $actionRecommendation = "Tindak Lanjut Evaluasi: Teridentifikasi keluhan masyarakat terkait {$category}. Disarankan Kasi segera menelusuri kronologi bersama staf shift bersangkutan, mengevaluasi standar SOP, dan menerapkan penyesuaian poin pembinaan jika terbukti terdapat ketidaksesuaian prosedur.";
            } else {
                $actionRecommendation = "Laporan Masukan Umum: Informasi ini bersifat masukan fasilitas atau saran operasional. Status ini adalah TINDAKAN NETRAL (0 Poin KPI) dan TIDAK memotong nilai kinerja staf unit.";
            }
        }

        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => $confidence,
            'category' => $category,
            'summary' => $summary,
            'urgency' => $urgency,
            'mentioned_entities' => $mentionedEntities,
            'action_recommendation' => $actionRecommendation,
            'recommendation' => $actionRecommendation,
            'engine' => 'GROQ_AI',
        ];
    }
}
