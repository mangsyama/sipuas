<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use App\Models\AiSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Master Units Rumah Sakit
        $unitsData = [
            [
                'code' => 'FARMASI',
                'name' => 'Instalasi Farmasi',
                'category' => 'MEDIK',
                'risk_status' => 'HIGH_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'IGD',
                'name' => 'Instalasi Gawat Darurat (IGD)',
                'category' => 'MEDIK',
                'risk_status' => 'MEDIUM_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'POLIKLINIK',
                'name' => 'Poliklinik Rawat Jalan',
                'category' => 'MEDIK',
                'risk_status' => 'LOW_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'RAWAT_INAP',
                'name' => 'Ruang Rawat Inap',
                'category' => 'MEDIK',
                'risk_status' => 'LOW_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'LABORATORIUM',
                'name' => 'Laboratorium Utama',
                'category' => 'MEDIK',
                'risk_status' => 'LOW_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'RADIOLOGI',
                'name' => 'Instalasi Radiologi',
                'category' => 'MEDIK',
                'risk_status' => 'LOW_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'KASIR',
                'name' => 'Kasir & Pendaftaran',
                'category' => 'NON_MEDIK',
                'risk_status' => 'MEDIUM_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
            [
                'code' => 'IPSRS',
                'name' => 'Pemeliharaan Sarpras (IPSRS)',
                'category' => 'NON_MEDIK',
                'risk_status' => 'LOW_RISK',
                'pic_name' => null,
                'phone_contact' => null,
            ],
        ];

        foreach ($unitsData as $data) {
            Unit::create($data);
        }

        // 2. Single Admin Account (Password: 12345678)
        User::create([
            'name' => 'Administrator',
            'username' => 'admin',
            'nip' => '19850101 201001 1 001',
            'email' => 'admin@sipuas.local',
            'phone_number' => '081200000001',
            'password' => Hash::make('12345678'),
            'role' => 'SUPERADMIN',
            'unit_id' => null,
            'is_active' => true,
        ]);

        // 3. Groq AI Integration Setting
        AiSetting::create([
            'provider' => 'groq',
            'api_key' => env('GROQ_API_KEY', null),
            'model_name' => 'openai/gpt-oss-120b',
            'system_prompt' => "Anda adalah AI Analisis Pelayanan Pasien Rumah Sakit (SIPUAS). Tugas Anda adalah menganalisis teks laporan atau keluhan dari pasien/keluarga pasien secara objektif.\n\nBerikan output HANYA dalam format JSON valid tanpa markdown dengan struktur berikut:\n{\n  \"sentiment\": \"POSITIF\" atau \"NEGATIF\" atau \"NETRAL\",\n  \"score\": 5 (jika pujian) atau -5 (jika keluhan) atau 0 (jika netral),\n  \"confidence\": \"persentase keyakinan misal 95%\",\n  \"category\": \"Kategori spesifik (contoh: Waktu Tunggu & Pelayanan, Sarana & Prasarana, Sikap & Keramahan Staf, Komunikasi Efektif, Kebersihan, Administrasi & Kasir)\",\n  \"summary\": \"Ringkasan inti keluhan/pujian dalam 1 kalimat padat dan jelas\",\n  \"urgency\": \"RENDAH\" atau \"SEDANG\" atau \"TINGGI\" atau \"KRITIS\",\n  \"mentioned_entities\": [\"daftar nama staf, dokter, perawat, atau nomor loket/ruangan yang disebut di teks\"],\n  \"action_recommendation\": \"Saran tindakan perbaikan nyata untuk pihak manajemen/Kepala Seksi unit terkait\"\n}",
            'is_active' => true,
            'temperature' => 0.2,
            'max_tokens' => 1000,
            'last_tested_at' => Carbon::now(),
            'last_test_status' => 'CONNECTED',
        ]);
    }
}
