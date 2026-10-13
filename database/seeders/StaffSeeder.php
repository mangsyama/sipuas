<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Staff;
use App\Models\Room;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = Room::where('is_active', true)->get();
        if ($rooms->isEmpty()) {
            return;
        }

        $room1 = $rooms->firstWhere('id', 1) ?? $rooms->first();
        $room2 = $rooms->firstWhere('id', 2) ?? $rooms->skip(1)->first() ?? $room1;
        $room5 = $rooms->firstWhere('id', 5) ?? $rooms->skip(2)->first() ?? $room1;

        $sampleStaff = [
            [
                'name' => 'Ns. Siti Rahmah, S.Kep',
                'position' => 'Perawat Pelaksana',
                'nip' => '199203152018012001',
                'phone_number' => '081234567890',
                'is_active' => true,
                'total_points' => 100,
                'rooms' => array_unique([$room1->id, $room5->id]),
            ],
            [
                'name' => 'dr. Hendra Pratama, Sp.B',
                'position' => 'Dokter Spesialis Bedah',
                'nip' => '198506202012121002',
                'phone_number' => '081345678901',
                'is_active' => true,
                'total_points' => 105,
                'rooms' => array_unique([$room1->id, $room2->id]),
            ],
            [
                'name' => 'Ns. Ahmad Fauzi, S.Kep',
                'position' => 'Perawat Pelaksana',
                'nip' => '199408102020121003',
                'phone_number' => '081456789012',
                'is_active' => true,
                'total_points' => 98,
                'rooms' => [$room5->id],
            ],
            [
                'name' => 'Sutrisno',
                'position' => 'Tenaga Kebersihan (CS)',
                'nip' => '199805142022031005',
                'phone_number' => '081567890123',
                'is_active' => true,
                'total_points' => 100,
                'rooms' => array_unique([$room1->id, $room2->id, $room5->id]),
            ],
            [
                'name' => 'Dewi Anggraini, A.Md.Keb',
                'position' => 'Bidan Pelaksana',
                'nip' => '199511052021022004',
                'phone_number' => '081678901234',
                'is_active' => true,
                'total_points' => 102,
                'rooms' => [$room5->id],
            ],
            [
                'name' => 'Agus Supriyanto',
                'position' => 'Petugas Keamanan (Satpam)',
                'nip' => '199607212021031006',
                'phone_number' => '081789012345',
                'is_active' => true,
                'total_points' => 100,
                'rooms' => array_unique([$room1->id, $room5->id]),
            ],
        ];

        foreach ($sampleStaff as $data) {
            $roomIds = $data['rooms'];
            unset($data['rooms']);

            $staff = Staff::firstOrCreate(
                ['nip' => $data['nip']],
                $data
            );

            $staff->rooms()->sync($roomIds);
        }
    }
}
