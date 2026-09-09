<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Models\AiSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
                    'dashboard', 'staff.attendance', 'staff.dashboard', 'attendance.status',
                    'kasi.dashboard', 'kasi.verify', 'kasi.logbook',
                    'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard',
                    'rooms.index', 'users.approvals', 'users.index',
                    'admin.ai-settings.index', 'admin.wa-gateway.index', 'settings.index',
                ],
            ],
            [
                'id' => Role::DIREKTUR,
                'name' => 'DIREKTUR',
                'page_permissions' => [
                    'dashboard', 'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard',
                    'kasi.dashboard', 'kasi.logbook', 'rooms.index', 'settings.index',
                ],
            ],
            [
                'id' => Role::KEPALA_BIDANG,
                'name' => 'KEPALA BIDANG',
                'page_permissions' => [
                    'dashboard', 'executive.dashboard', 'executive.kasi-responsiveness', 'executive.leaderboard',
                    'kasi.dashboard', 'kasi.logbook', 'rooms.index', 'settings.index',
                ],
            ],
            [
                'id' => Role::KEPALA_SEKSI,
                'name' => 'KEPALA SEKSI',
                'page_permissions' => [
                    'dashboard', 'kasi.dashboard', 'kasi.verify', 'kasi.logbook',
                    'staff.dashboard', 'rooms.index', 'settings.index',
                ],
            ],
            [
                'id' => Role::STAFF,
                'name' => 'STAFF',
                'page_permissions' => [
                    'staff.dashboard', 'attendance.status', 'settings.index',
                ],
            ],
        ];

        foreach ($rolesData as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['page_permissions' => $role['page_permissions']]
            );
        }

        // 2. Master Ruangan Rumah Sakit (Sinkron dengan Basis Data Pesu Peluh)
        try {
            $pesupeluhRooms = DB::connection('pesupeluh')->table('rooms')->whereNull('deleted_at')->get();
            if ($pesupeluhRooms->isNotEmpty()) {
                foreach ($pesupeluhRooms as $pRoom) {
                    Room::updateOrCreate(
                        ['name' => $pRoom->name],
                        [
                            'building_name' => $pRoom->building_name ?? 'Gedung Utama',
                            'location_floor' => $pRoom->location_floor ?? 'Lantai 1',
                            'is_active' => true,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Fallback list of 27 standard hospital rooms
            $fallbackRooms = [
                ['name' => 'UGD', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'VK/PERINA', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'FARMASI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'LABORATORIUM', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'RADIOLOGI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'CLEANING SERVICEE', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 1'],
                ['name' => 'POLI KLINIK', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 2'],
                ['name' => 'FISIOTERAPI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 2'],
                ['name' => 'RAWAT INAP KASWUARI', 'building_name' => 'Gedung A', 'location_floor' => 'Lantai 3'],
                ['name' => 'KAMAR BEDAH', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 1'],
                ['name' => 'RAWAT INAP CENDRAWASIH', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 2'],
                ['name' => 'RAWAT INAP MERPATI', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'IPSRS', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'KESLING', 'building_name' => 'Gedung B', 'location_floor' => 'Lantai 3'],
                ['name' => 'RUANG KABID PENUNJANG', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'LAUNDRY', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'GIZI', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'HCU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'CSSD', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 1'],
                ['name' => 'ICU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'KEPEGAWAIAN/KEUANGAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG ADMINISTRASI PELAYANAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG DIREKTUR', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG KABAG TU', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'DALOP', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'RUANG SUB BAGIAN KEUANGAN', 'building_name' => 'Gedung C', 'location_floor' => 'Lantai 2'],
                ['name' => 'SECURITY', 'building_name' => 'Halaman Depan', 'location_floor' => '-'],
            ];
            foreach ($fallbackRooms as $r) {
                Room::updateOrCreate(['name' => $r['name']], array_merge($r, ['is_active' => true]));
            }
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
    }
}
