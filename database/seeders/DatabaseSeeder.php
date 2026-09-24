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
        // 15 Ruangan Poli Rumah Sakit yang Aktif Resmi sesuai instruksi:
        // 1. Bedah, 2. Orthopaedi, 3. Jantung, 4. MCU, 5. Interna, 6. Anak,
        // 7. Kulit, 8. Obgyn, 9. THT, 10. Fisioterapi, 11. Rehab Medik,
        // 12. Saraf, 13. Jiwa, 14. VCT, 15. TBC.
        
        // Selaraskan nama ruangan lama ke nama standar baru
        $legacyAliases = [
            'Poli Jantung & Pembuluh Darah' => 'Poli Jantung',
            'Poli Penyakit Dalam' => 'Poli Interna',
            'Poli Kulit & Kelamin' => 'Poli Kulit',
            'Poli Kebidanan & Kandungan (Obgyn)' => 'Poli Obgyn',
            'Poli Fisioterapi & Rehab Medik' => 'Poli Fisioterapi',
            'Poli Saraf / Neurologi' => 'Poli Saraf',
            'Poli Jiwa / Psikiatri' => 'Poli Jiwa',
        ];
        foreach ($legacyAliases as $oldName => $newName) {
            Room::where('name', $oldName)->update(['name' => $newName]);
        }

        $activePoliRooms = [
            [
                'name' => 'Poli Bedah',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Orthopaedi',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Jantung',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli MCU',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Interna',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Anak',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Kulit',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Obgyn',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli THT',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Fisioterapi',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Rehab Medik',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Saraf',
                'building_name' => 'Gedung A',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli Jiwa',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 2',
                'is_active' => true,
            ],
            [
                'name' => 'Poli VCT',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
            [
                'name' => 'Poli TBC',
                'building_name' => 'Gedung B',
                'location_floor' => 'Lantai 1',
                'is_active' => true,
            ],
        ];

        foreach ($activePoliRooms as $roomData) {
            Room::updateOrCreate(
                ['name' => $roomData['name']],
                $roomData
            );
        }

        // Ruangan pendukung lainnya dinonaktifkan (tersedia di arsip bila diperlukan)
        $inactiveRooms = [
            ['name' => 'Area Publik & Ruang Tunggu Utama', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Farmasi', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'HCU (High Care Unit)', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'ICU (Intensive Care Unit)', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2', 'is_active' => false],
            ['name' => 'IGD (Instalasi Gawat Darurat)', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'Kamar Bedah (OK / IBS)', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'Kamar Bersalin (VK)', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'Laboratorium', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'Loket Kasir & Pembayaran', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Loket Pendaftaran & Registrasi', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Perinatologi', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'PICU / NICU', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Poli Gigi & Mulut', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Poli Mata', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Poli Paru', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Poli Umum', 'building_name' => '-', 'location_floor' => '-', 'is_active' => false],
            ['name' => 'Radiologi', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1', 'is_active' => false],
            ['name' => 'Rawat Inap Cendrawasih', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 2', 'is_active' => false],
            ['name' => 'Rawat Inap Kasuari', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 3', 'is_active' => false],
            ['name' => 'Rawat Inap Merpati', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3', 'is_active' => false],
        ];

        foreach ($inactiveRooms as $roomData) {
            Room::updateOrCreate(
                ['name' => $roomData['name']],
                $roomData
            );
        }

        // Pastikan hanya 15 ruangan poli resmi tersebut yang berstatus aktif (is_active = true)
        $activeNames = array_column($activePoliRooms, 'name');
        Room::whereNotIn('name', $activeNames)->update(['is_active' => false]);
        Room::whereIn('name', $activeNames)->update(['is_active' => true]);

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
