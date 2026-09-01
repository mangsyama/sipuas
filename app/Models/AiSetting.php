<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'api_key',
        'model_name',
        'system_prompt',
        'is_active',
        'temperature',
        'max_tokens',
        'last_tested_at',
        'last_test_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'temperature' => 'float',
            'max_tokens' => 'integer',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * Get or create singleton active AI setting.
     */
    public static function getActiveSetting(): self
    {
        $setting = self::first();
        if (!$setting) {
            $defaultPrompt = "Anda adalah AI Triase Pelayanan Pasien Rumah Sakit (SIPUAS). Tugas Anda adalah menganalisis teks laporan atau keluhan dari pasien/keluarga pasien secara objektif.\n\nBerikan output HANYA dalam format JSON valid tanpa markdown dengan struktur berikut:\n{\n  \"sentiment\": \"POSITIF\" atau \"NEGATIF\" atau \"NETRAL\",\n  \"score\": 5 (jika pujian) atau -5 (jika keluhan) atau 0 (jika netral),\n  \"confidence\": \"persentase keyakinan misal 95%\",\n  \"category\": \"Kategori spesifik (contoh: Waktu Tunggu & Pelayanan, Sarana & Prasarana, Sikap & Keramahan Staf, Komunikasi Efektif, Kebersihan, Administrasi & Kasir)\",\n  \"summary\": \"Ringkasan inti keluhan/pujian dalam 1 kalimat padat dan jelas\",\n  \"urgency\": \"RENDAH\" atau \"SEDANG\" atau \"TINGGI\" atau \"KRITIS\",\n  \"mentioned_entities\": [\"daftar nama staf, dokter, perawat, atau nomor loket/ruangan yang disebut di teks\"],\n  \"action_recommendation\": \"Saran tindakan perbaikan nyata untuk pihak manajemen/Kepala Seksi unit terkait\"\n}";

            $setting = self::create([
                'provider' => 'gemini',
                'api_key' => env('GEMINI_API_KEY', ''),
                'model_name' => 'gemini-1.5-flash',
                'system_prompt' => $defaultPrompt,
                'is_active' => true,
                'temperature' => 0.2,
                'max_tokens' => 1000,
            ]);
        }
        return $setting;
    }
}
