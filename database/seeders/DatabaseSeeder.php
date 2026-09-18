<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Master Roles (5 Peran Jabatan Resmi SIPUAS)
        $rolesData = [
            [
                'id' => Role::ADMINISTRATOR,
                'name' => 'ADMINISTRATOR',
                'page_permissions' => [
                    'dashboard',
                    'staff.attendance', 'staff.dashboard',
                    'kasi.dashboard', 'kasi.feed', 'kasi.verify', 'kasi.logbook',
                    'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard', 'reports.index',
                    'users.approvals', 'users.index', 'rooms.index',
                    'admin.ai-settings.index', 'admin.wa-gateway.index', 'admin.qr-generator.index',
                    'report.create',
                ],
            ],
            [
                'id' => Role::DIREKTUR,
                'name' => 'DIREKTUR',
                'page_permissions' => [
                    'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard', 'reports.index',
                ],
            ],
            [
                'id' => Role::KEPALA_BIDANG,
                'name' => 'KEPALA BIDANG',
                'page_permissions' => [
                    'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard', 'reports.index',
                ],
            ],
            [
                'id' => Role::KEPALA_SEKSI,
                'name' => 'KEPALA SEKSI',
                'page_permissions' => [
                    'kasi.dashboard', 'kasi.feed', 'kasi.verify', 'kasi.logbook',
                ],
            ],
            [
                'id' => Role::STAFF,
                'name' => 'STAFF',
                'page_permissions' => [
                    'staff.attendance', 'staff.dashboard',
                ],
            ],
        ];

        foreach ($rolesData as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['page_permissions' => $role['page_permissions']]
            );
        }

        // 2. Master Ruangan Rumah Sakit Khusus Bidang Pelayanan Pasien (SIPUAS)
        // Data gedung dan lantai diselaraskan dengan master data riil sistem PESU PELUH
        // Ruangan yang belum ada padanannya di PESU PELUH diset '-' (strip)
        $pelayananRooms = [
            [
                'name' => 'Area Publik & Ruang Tunggu Utama',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Farmasi',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'HCU (High Care Unit)',
                'building_name' => 'Gedung C',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'ICU (Intensive Care Unit)',
                'building_name' => 'Gedung C',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'IGD (Instalasi Gawat Darurat)',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Kamar Bedah (OK / IBS)',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Kamar Bersalin (VK)',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Laboratorium',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Loket Kasir & Pembayaran',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Loket Pendaftaran & Registrasi',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Perinatologi',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'PICU / NICU',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Anak',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Bedah',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Fisioterapi & Rehab Medik',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Gigi & Mulut',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Jantung & Pembuluh Darah',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Jiwa / Psikiatri',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Kebidanan & Kandungan (Obgyn)',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Kulit & Kelamin',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Mata',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Paru',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Penyakit Dalam',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Saraf / Neurologi',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli THT',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Umum',
                'building_name' => '-',
                'location_floor' => '-',
                'is_active' => true,
            ],
            [
                'name' => 'Radiologi',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Rawat Inap Cendrawasih',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Rawat Inap Kasuari',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 3',
                'is_active' => true,
            ],
            [
                'name' => 'Rawat Inap Merpati',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 3',
                'is_active' => true,
            ],
        ];

        foreach ($pelayananRooms as $roomData) {
            Room::updateOrCreate(
                ['name' => $roomData['name']],
                $roomData
            );
        }

        // 3. Single Admin Account (Password: 12345678)
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'nip' => '198501012010011001',
                'email' => 'admin@sipuas.local',
                'phone_number' => '081200000001',
                'password' => Hash::make('12345678'),
                'role_id' => Role::ADMINISTRATOR,
                'room_id' => null,
                'is_active' => true,
                'total_points' => 100,
                'praise_count' => 0,
                'complaint_count' => 0,
            ]
        );

        // 4. Groq AI Integration Setting
        AiSetting::updateOrCreate(
            ['provider' => 'groq'],
            [
                'api_key' => env('GROQ_API_KEY', null),
                'model_name' => 'openai/gpt-oss-120b',
                'system_prompt' => "Anda adalah AI Analisis Pelayanan Pasien Rumah Sakit (SIPUAS). Tugas Anda adalah menganalisis teks laporan atau keluhan dari pasien/keluarga pasien secara objektif.\n\nBerikan output HANYA dalam format JSON valid tanpa markdown dengan struktur berikut:\n{\n  \"sentiment\": \"POSITIF\" atau \"NEGATIF\" atau \"NETRAL\",\n  \"score\": 5 (jika pujian) atau -5 (jika keluhan) atau 0 (jika netral),\n  \"confidence\": \"persentase keyakinan misal 95%\",\n  \"category\": \"Kategori spesifik (contoh: Waktu Tunggu & Pelayanan, Sarana & Prasarana, Sikap & Keramahan Staf, Komunikasi Efektif, Kebersihan, Administrasi & Kasir)\",\n  \"summary\": \"Ringkasan inti keluhan/pujian dalam 1 kalimat padat dan jelas\",\n  \"urgency\": \"RENDAH\" atau \"SEDANG\" atau \"TINGGI\" atau \"KRITIS\",\n  \"mentioned_entities\": [\"daftar nama staf, dokter, perawat, atau nomor loket/ruangan yang disebut di teks\"],\n  \"action_recommendation\": \"Saran tindakan perbaikan nyata untuk pihak manajemen/Kepala Seksi unit terkait\"\n}",
                'is_active' => true,
                'temperature' => 0.2,
                'max_tokens' => 1000,
                'last_tested_at' => Carbon::now(),
                'last_test_status' => 'CONNECTED',
            ]
        );

        // 5. Seed Dummy Data (Disabled for clean production/initial state)
        // if (!app()->runningUnitTests()) {
        //     $this->call(DummyDataSeeder::class);
        // }
    }
}
