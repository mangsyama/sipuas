<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Role;
use App\Models\Room;
use App\Models\StaffAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $today = Carbon::today();

        // Get Room mappings
        $roomFarmasi = Room::where('name', 'like', '%Farmasi%')->first() ?: Room::first();
        $roomIgd = Room::where('name', 'like', '%IGD%')->first() ?: Room::first();
        $roomLab = Room::where('name', 'like', '%Laboratorium%')->first() ?: Room::first();
        $roomPendaftaran = Room::where('name', 'like', '%Pendaftaran%')->first() ?: Room::first();
        $roomKasir = Room::where('name', 'like', '%Kasir%')->first() ?: Room::first();
        $roomRanap = Room::where('name', 'like', '%Cendrawasih%')->first() ?: Room::first();
        $roomPoliUmum = Room::where('name', 'like', '%Poli Umum%')->first() ?: Room::first();
        $roomAreaPublik = Room::where('name', 'like', '%Area Publik%')->first() ?: Room::first();

        // Bersihkan data presensi lama agar tidak terjadi duplikasi saat re-seed
        StaffAttendance::query()->delete();

        // 1. Staff Users for all rooms (All in Active ON_DUTY / Clock In State)
        $staffUsersData = [
            // Ruang yang sudah ada sebelumnya
            [
                'username' => 'staff_igd',
                'name' => 'Ns. Ni Kadek Devi, S.Kep',
                'nip' => '199205122018012001',
                'email' => 'devi.igd@sipuas.local',
                'phone_number' => '081234560001',
                'room_id' => $roomIgd->id,
                'total_points' => 125,
                'praise_count' => 5,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_farmasi',
                'name' => 'Apt. Putu Arya Pratama, S.Farm',
                'nip' => '199108202017011002',
                'email' => 'arya.farmasi@sipuas.local',
                'phone_number' => '081234560002',
                'room_id' => $roomFarmasi->id,
                'total_points' => 110,
                'praise_count' => 3,
                'complaint_count' => 1,
            ],
            [
                'username' => 'staff_lab',
                'name' => 'Ni Made Wulandari, A.Md.AK',
                'nip' => '199403152019012003',
                'email' => 'wulan.lab@sipuas.local',
                'phone_number' => '081234560003',
                'room_id' => $roomLab->id,
                'total_points' => 105,
                'praise_count' => 2,
                'complaint_count' => 1,
            ],
            [
                'username' => 'staff_loket',
                'name' => 'I Gede Budiarta',
                'nip' => '199009102016011004',
                'email' => 'budi.loket@sipuas.local',
                'phone_number' => '081234560004',
                'room_id' => $roomPendaftaran->id,
                'total_points' => 115,
                'praise_count' => 4,
                'complaint_count' => 1,
            ],
            [
                'username' => 'staff_ranap',
                'name' => 'Ns. I Wayan Suartana, S.Kep',
                'nip' => '198904222015011005',
                'email' => 'wayan.ranap@sipuas.local',
                'phone_number' => '081234560005',
                'room_id' => $roomRanap->id,
                'total_points' => 130,
                'praise_count' => 6,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_kasir',
                'name' => 'Ni Luh Ayu Megawati',
                'nip' => '199511082020012006',
                'email' => 'ayu.kasir@sipuas.local',
                'phone_number' => '081234560006',
                'room_id' => $roomKasir->id,
                'total_points' => 120,
                'praise_count' => 4,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_poli',
                'name' => 'Ns. Desak Made Rai, S.Kep',
                'nip' => '199307182018012007',
                'email' => 'desak.poli@sipuas.local',
                'phone_number' => '081234560007',
                'room_id' => $roomPoliUmum->id,
                'total_points' => 115,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            // Penambahan staf untuk seluruh ruangan RS lainnya
            [
                'username' => 'staff_areapublik',
                'name' => 'I Made Suardana, S.Sos',
                'nip' => '199102142016011008',
                'email' => 'suardana.publik@sipuas.local',
                'phone_number' => '081234560008',
                'room_id' => 1, // Area Publik
                'total_points' => 105,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_hcu',
                'name' => 'Ns. Putu Hendra Wijaya, S.Kep',
                'nip' => '199208192017011009',
                'email' => 'hendra.hcu@sipuas.local',
                'phone_number' => '081234560009',
                'room_id' => 3, // HCU
                'total_points' => 110,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_icu',
                'name' => 'Ns. Ketut Agus Sanjaya, S.Kep',
                'nip' => '199011252015011010',
                'email' => 'agus.icu@sipuas.local',
                'phone_number' => '081234560013',
                'room_id' => 4, // ICU
                'total_points' => 125,
                'praise_count' => 5,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_bedah',
                'name' => 'Ns. Made Suryawan, S.Kep',
                'nip' => '198806122014011011',
                'email' => 'surya.bedah@sipuas.local',
                'phone_number' => '081234560014',
                'room_id' => 6, // Kamar Bedah
                'total_points' => 120,
                'praise_count' => 4,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_bersalin',
                'name' => 'Bd. Ni Wayan Sukartini, S.Tr.Keb',
                'nip' => '199304172018012012',
                'email' => 'sukartini.vk@sipuas.local',
                'phone_number' => '081234560015',
                'room_id' => 7, // Kamar Bersalin (VK)
                'total_points' => 130,
                'praise_count' => 6,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_perinatologi',
                'name' => 'Bd. Ni Kadek Ari Susanti, A.Md.Keb',
                'nip' => '199409222019012013',
                'email' => 'ari.peri@sipuas.local',
                'phone_number' => '081234560016',
                'room_id' => 11, // Perinatologi
                'total_points' => 115,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_nicu',
                'name' => 'Ns. Luh Made Riantini, S.Kep',
                'nip' => '199212042017012014',
                'email' => 'riantini.nicu@sipuas.local',
                'phone_number' => '081234560017',
                'room_id' => 12, // PICU / NICU
                'total_points' => 110,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_poli_anak',
                'name' => 'Ns. Ni Made Ariani, S.Kep',
                'nip' => '199303102018012015',
                'email' => 'ariani.anak@sipuas.local',
                'phone_number' => '081234560018',
                'room_id' => 13, // Poli Anak
                'total_points' => 120,
                'praise_count' => 4,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_poli_bedah',
                'name' => 'Ns. I Putu Agus Wirawan, S.Kep',
                'nip' => '199105282016011016',
                'email' => 'agus.polibedah@sipuas.local',
                'phone_number' => '081234560019',
                'room_id' => 14, // Poli Bedah
                'total_points' => 105,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_fisioterapi',
                'name' => 'I Nyoman Triadi, A.Md.Ft',
                'nip' => '199008152015011017',
                'email' => 'triadi.fisio@sipuas.local',
                'phone_number' => '081234560021',
                'room_id' => 15, // Poli Fisioterapi
                'total_points' => 115,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_gigi',
                'name' => 'Ni Wayan Eka Lestari, A.Md.KG',
                'nip' => '199501302020012018',
                'email' => 'eka.gigi@sipuas.local',
                'phone_number' => '081234560022',
                'room_id' => 16, // Poli Gigi
                'total_points' => 125,
                'praise_count' => 5,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_jantung',
                'name' => 'Ns. Luh Putu Dian Pertiwi, S.Kep',
                'nip' => '199207112017012019',
                'email' => 'dian.jantung@sipuas.local',
                'phone_number' => '081234560023',
                'room_id' => 17, // Poli Jantung
                'total_points' => 130,
                'praise_count' => 6,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_jiwa',
                'name' => 'Ns. I Wayan Surya Darma, S.Kep',
                'nip' => '198910052014011020',
                'email' => 'surya.jiwa@sipuas.local',
                'phone_number' => '081234560024',
                'room_id' => 18, // Poli Jiwa
                'total_points' => 110,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_obgyn',
                'name' => 'Bd. Ni Komang Sri Damayanti, S.Tr.Keb',
                'nip' => '199402182019012021',
                'email' => 'damayanti.obgyn@sipuas.local',
                'phone_number' => '081234560025',
                'room_id' => 19, // Poli Kebidanan (Obgyn)
                'total_points' => 120,
                'praise_count' => 4,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_kulit',
                'name' => 'Ns. Ni Putu Ratna Pradnya, S.Kep',
                'nip' => '199306232018012022',
                'email' => 'ratna.kulit@sipuas.local',
                'phone_number' => '081234560026',
                'room_id' => 20, // Poli Kulit
                'total_points' => 115,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_mata',
                'name' => 'Ns. Ida Bagus Yoga, S.Kep',
                'nip' => '199003142015011023',
                'email' => 'yoga.mata@sipuas.local',
                'phone_number' => '081234560027',
                'room_id' => 21, // Poli Mata
                'total_points' => 125,
                'praise_count' => 5,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_paru',
                'name' => 'Ns. I Gede Yudha Pratama, S.Kep',
                'nip' => '199109092016011024',
                'email' => 'yudha.paru@sipuas.local',
                'phone_number' => '081234560028',
                'room_id' => 22, // Poli Paru
                'total_points' => 110,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_penyakit_dalam',
                'name' => 'Ns. I Komang Artawan, S.Kep',
                'nip' => '199201202017011025',
                'email' => 'artawan.pd@sipuas.local',
                'phone_number' => '081234560029',
                'room_id' => 23, // Poli Penyakit Dalam
                'total_points' => 135,
                'praise_count' => 7,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_saraf',
                'name' => 'Ns. Ni Made Dwi Kusuma, S.Kep',
                'nip' => '199407152019012026',
                'email' => 'kusuma.saraf@sipuas.local',
                'phone_number' => '081234560030',
                'room_id' => 24, // Poli Saraf
                'total_points' => 115,
                'praise_count' => 3,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_tht',
                'name' => 'Ns. I Putu Sujana Putra, S.Kep',
                'nip' => '199311022018011027',
                'email' => 'sujana.tht@sipuas.local',
                'phone_number' => '081234560031',
                'room_id' => 25, // Poli THT
                'total_points' => 120,
                'praise_count' => 4,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_radiologi',
                'name' => 'I Made Danu Tirta, A.Md.Rad',
                'nip' => '199104052016011028',
                'email' => 'danu.rad@sipuas.local',
                'phone_number' => '081234560032',
                'room_id' => 27, // Radiologi
                'total_points' => 125,
                'praise_count' => 5,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_ranap_kasuari',
                'name' => 'Ns. Ni Luh Sri Wahyuni, S.Kep',
                'nip' => '199209182017012029',
                'email' => 'wahyuni.kasuari@sipuas.local',
                'phone_number' => '081234560033',
                'room_id' => 29, // Rawat Inap Kasuari
                'total_points' => 110,
                'praise_count' => 2,
                'complaint_count' => 0,
            ],
            [
                'username' => 'staff_ranap_merpati',
                'name' => 'Ns. Kadek Dwi Antara, S.Kep',
                'nip' => '199005082015011030',
                'email' => 'antara.merpati@sipuas.local',
                'phone_number' => '081234560034',
                'room_id' => 30, // Rawat Inap Merpati
                'total_points' => 130,
                'praise_count' => 6,
                'complaint_count' => 0,
            ],
        ];

        $createdStaffUsers = [];
        foreach ($staffUsersData as $idx => $staffData) {
            $user = User::updateOrCreate(
                ['username' => $staffData['username']],
                [
                    'name' => $staffData['name'],
                    'nip' => $staffData['nip'],
                    'email' => $staffData['email'],
                    'phone_number' => $staffData['phone_number'],
                    'password' => Hash::make('12345678'),
                    'role_id' => Role::STAFF,
                    'room_id' => $staffData['room_id'],
                    'is_active' => true,
                    'is_on_duty' => true, // SEDANG CLOCK IN
                    'total_points' => $staffData['total_points'],
                    'praise_count' => $staffData['praise_count'],
                    'complaint_count' => $staffData['complaint_count'],
                ]
            );
            $createdStaffUsers[] = $user;

            // Pastikan Presensi Hari Ini aktif (ON_DUTY / Clock In)
            $checkInTime = $today->copy()->setHour(7)->setMinute(15 + ($idx % 35));
            $attToday = StaffAttendance::where('user_id', $user->id)
                ->whereDate('duty_date', $today)
                ->first();

            if (!$attToday) {
                $attToday = new StaffAttendance();
                $attToday->user_id = $user->id;
                $attToday->duty_date = $today->toDateString();
            }

            $attToday->room_id = $user->room_id;
            $attToday->shift_name = 'PAGI';
            $attToday->check_in_at = $checkInTime;
            $attToday->check_out_at = null; // Masih bertugas (Clock In aktif)
            $attToday->status = 'ON_DUTY';
            $attToday->notes = 'Presensi dinas shift pagi hadir tepat waktu.';
            $attToday->save();

            // Buat riwayat presensi 5 hari lalu yang telah selesai (COMPLETED)
            for ($d = 1; $d <= 5; $d++) {
                $pastDate = $today->copy()->subDays($d);
                $pastAtt = StaffAttendance::where('user_id', $user->id)
                    ->whereDate('duty_date', $pastDate)
                    ->first();

                if (!$pastAtt) {
                    $pastAtt = new StaffAttendance();
                    $pastAtt->user_id = $user->id;
                    $pastAtt->duty_date = $pastDate->toDateString();
                }

                $pastAtt->room_id = $user->room_id;
                $pastAtt->shift_name = 'PAGI';
                $pastAtt->check_in_at = $pastDate->copy()->setHour(7)->setMinute(15);
                $pastAtt->check_out_at = $pastDate->copy()->setHour(14)->setMinute(30);
                $pastAtt->status = 'COMPLETED';
                $pastAtt->notes = 'Shift selesai dengan baik.';
                $pastAtt->save();
            }
        }

        // 2. Kepala Seksi (Kasi) Accounts (Password: 12345678)
        $kasiUsersData = [
            ['username' => 'kasi_areapublik', 'name' => 'I Wayan Sudarma, S.AP', 'nip' => '198205142008011002', 'phone_number' => '081200000001', 'email' => 'kasi.areapublik@sipuas.local', 'room_id' => 1],
            ['username' => 'kasi_farmasi', 'name' => 'Apt. Ni Nyoman Sariani, S.Si', 'nip' => '198302142009012004', 'phone_number' => '081200000002', 'email' => 'kasi.farmasi@sipuas.local', 'room_id' => 2],
            ['username' => 'kasi_hcu', 'name' => 'Ns. Ni Made Rai Widiastuti, S.Kep', 'nip' => '198507192010012003', 'phone_number' => '081200000003', 'email' => 'kasi.hcu@sipuas.local', 'room_id' => 3],
            ['username' => 'kasi_icu', 'name' => 'dr. I Putu Gede Sanjaya, Sp.An', 'nip' => '198003252006041002', 'phone_number' => '081200000004', 'email' => 'kasi.icu@sipuas.local', 'room_id' => 4],
            ['username' => 'kasi_igd', 'name' => 'dr. I Ketut Widiana, Sp.B', 'nip' => '198104102008011003', 'phone_number' => '081200000005', 'email' => 'kasi.igd@sipuas.local', 'room_id' => 5],
            ['username' => 'kasi_bedah', 'name' => 'dr. I Nyoman Sastrawan, Sp.B', 'nip' => '197911122005011004', 'phone_number' => '081200000006', 'email' => 'kasi.bedah@sipuas.local', 'room_id' => 6],
            ['username' => 'kasi_bersalin', 'name' => 'Bd. Ni Luh Putu Mirah, S.Tr.Keb', 'nip' => '198409082008012005', 'phone_number' => '081200000007', 'email' => 'kasi.bersalin@sipuas.local', 'room_id' => 7],
            ['username' => 'kasi_lab', 'name' => 'dr. Ni Kadek Dwipayani, Sp.PK', 'nip' => '198206172009022003', 'phone_number' => '081200000008', 'email' => 'kasi.lab@sipuas.local', 'room_id' => 8],
            ['username' => 'kasi_kasir', 'name' => 'I Gusti Ayu Mas Trisna, SE', 'nip' => '198612032010012004', 'phone_number' => '081200000009', 'email' => 'kasi.kasir@sipuas.local', 'room_id' => 9],
            ['username' => 'kasi_pendaftaran', 'name' => 'I Gede Yudiartawan, S.Kom', 'nip' => '198801152011011003', 'phone_number' => '081200000010', 'email' => 'kasi.pendaftaran@sipuas.local', 'room_id' => 10],
            ['username' => 'kasi_perinatologi', 'name' => 'Bd. Ni Ketut Supartini, S.Tr.Keb', 'nip' => '198304202007012004', 'phone_number' => '081200000011', 'email' => 'kasi.perinatologi@sipuas.local', 'room_id' => 11],
            ['username' => 'kasi_picu', 'name' => 'dr. I Made Sukadana, Sp.A', 'nip' => '198108162007011002', 'phone_number' => '081200000012', 'email' => 'kasi.picu@sipuas.local', 'room_id' => 12],
            ['username' => 'kasi_polianak', 'name' => 'dr. Ni Wayan Murti, Sp.A', 'nip' => '198203112008012003', 'phone_number' => '081200000013', 'email' => 'kasi.polianak@sipuas.local', 'room_id' => 13],
            ['username' => 'kasi_polibedah', 'name' => 'dr. I Ketut Agus Darmayasa, Sp.B', 'nip' => '198010052006041003', 'phone_number' => '081200000014', 'email' => 'kasi.polibedah@sipuas.local', 'room_id' => 14],
            ['username' => 'kasi_rehabmedik', 'name' => 'dr. I Dewa Gede Alit, Sp.KFR', 'nip' => '198306282009031002', 'phone_number' => '081200000015', 'email' => 'kasi.rehabmedik@sipuas.local', 'room_id' => 15],
            ['username' => 'kasi_poligigi', 'name' => 'drg. Ni Made Anggreni, Sp.KG', 'nip' => '198411092010012005', 'phone_number' => '081200000016', 'email' => 'kasi.poligigi@sipuas.local', 'room_id' => 16],
            ['username' => 'kasi_polijantung', 'name' => 'dr. I Wayan Wita, Sp.JP(K)', 'nip' => '197804142003121001', 'phone_number' => '081200000017', 'email' => 'kasi.polijantung@sipuas.local', 'room_id' => 17],
            ['username' => 'kasi_polijiwa', 'name' => 'dr. Ni Luh Sukmawati, Sp.KJ', 'nip' => '198502182010012004', 'phone_number' => '081200000018', 'email' => 'kasi.polijiwa@sipuas.local', 'room_id' => 18],
            ['username' => 'kasi_poliobgyn', 'name' => 'dr. I Made Dwi Artha, Sp.OG', 'nip' => '198109222008011004', 'phone_number' => '081200000019', 'email' => 'kasi.poliobgyn@sipuas.local', 'room_id' => 19],
            ['username' => 'kasi_polikulit', 'name' => 'dr. Ni Kadek Dwi Jayanthi, Sp.DV', 'nip' => '198605172011012003', 'phone_number' => '081200000020', 'email' => 'kasi.polikulit@sipuas.local', 'room_id' => 20],
            ['username' => 'kasi_polimata', 'name' => 'dr. I Gede Eka Putra, Sp.M', 'nip' => '198312012009021002', 'phone_number' => '081200000021', 'email' => 'kasi.polimata@sipuas.local', 'room_id' => 21],
            ['username' => 'kasi_poliparu', 'name' => 'dr. I Nyoman Sumartana, Sp.P', 'nip' => '198207102008011003', 'phone_number' => '081200000022', 'email' => 'kasi.poliparu@sipuas.local', 'room_id' => 22],
            ['username' => 'kasi_polipd', 'name' => 'dr. I Gusti Agung Bagus Krisna, Sp.PD', 'nip' => '198005122006041004', 'phone_number' => '081200000023', 'email' => 'kasi.polipd@sipuas.local', 'room_id' => 23],
            ['username' => 'kasi_polisaraf', 'name' => 'dr. Ni Putu Ayu Lestari, Sp.S', 'nip' => '198408232010012004', 'phone_number' => '081200000024', 'email' => 'kasi.polisaraf@sipuas.local', 'room_id' => 24],
            ['username' => 'kasi_politht', 'name' => 'dr. I Komang Adi Wiratama, Sp.THT-BKL', 'nip' => '198301142008011002', 'phone_number' => '081200000025', 'email' => 'kasi.politht@sipuas.local', 'room_id' => 25],
            ['username' => 'kasi_poliumum', 'name' => 'dr. I Made Pasek Adiputra', 'nip' => '198709192012011003', 'phone_number' => '081200000026', 'email' => 'kasi.poliumum@sipuas.local', 'room_id' => 26],
            ['username' => 'kasi_radiologi', 'name' => 'dr. Ni Wayan Candrawati, Sp.Rad', 'nip' => '198210152008012004', 'phone_number' => '081200000027', 'email' => 'kasi.radiologi@sipuas.local', 'room_id' => 27],
            ['username' => 'kasi_ranap', 'name' => 'Ns. I Komang Yudi, M.Kep', 'nip' => '198406182010011005', 'phone_number' => '081200000028', 'email' => 'kasi.ranap@sipuas.local', 'room_id' => 28],
            ['username' => 'kasi_ranapkasuari', 'name' => 'Ns. Ni Ketut Astini, S.Kep', 'nip' => '198511202010012006', 'phone_number' => '081200000029', 'email' => 'kasi.ranapkasuari@sipuas.local', 'room_id' => 29],
            ['username' => 'kasi_ranapmerpati', 'name' => 'Ns. I Putu Agus Sudarma, S.Kep', 'nip' => '198603092011011004', 'phone_number' => '081200000030', 'email' => 'kasi.ranapmerpati@sipuas.local', 'room_id' => 30],
        ];

        $createdKasiUsers = [];
        foreach ($kasiUsersData as $kasi) {
            $createdKasiUsers[] = User::updateOrCreate(
                ['username' => $kasi['username']],
                [
                    'name' => $kasi['name'],
                    'nip' => $kasi['nip'],
                    'email' => $kasi['email'],
                    'phone_number' => $kasi['phone_number'],
                    'password' => Hash::make('12345678'),
                    'role_id' => Role::KEPALA_SEKSI,
                    'room_id' => $kasi['room_id'],
                    'is_active' => true,
                    'total_points' => 100,
                ]
            );
        }

        // 3. Kepala Bidang (Kabid) Account (Password: 12345678)
        $kabidUser = User::updateOrCreate(
            ['username' => 'kabid'],
            [
                'name' => 'dr. I Gusti Ngurah Agung, Sp.PD',
                'nip' => '197603122005011001',
                'email' => 'kabid@sipuas.local',
                'phone_number' => '081234560020',
                'password' => Hash::make('12345678'),
                'role_id' => Role::KEPALA_BIDANG,
                'room_id' => null,
                'is_active' => true,
                'total_points' => 100,
            ]
        );

        $adminUser = User::where('role_id', Role::ADMINISTRATOR)->first();
        $verifierId = $createdKasiUsers[0]->id ?? ($adminUser->id ?? 1);

        // 4. Exactly 10 PENDING Reports (Menunggu Verifikasi untuk Kasi Feed & Verify)
        $pendingReportsData = [
            [
                'room_id' => $roomFarmasi->id,
                'target_object' => 'Loket Penyerahan Obat Farmasi',
                'isi_laporan' => 'Antrean pengambilan obat racikan anak siang ini agak tersendat, sudah menunggu sekitar 45 menit belum dipanggil.',
                'ai_sentiment' => 'NEGATIF',
                'ai_category' => 'Waktu Tunggu & Antrean',
                'ai_score' => -5,
                'ai_confidence' => '94%',
                'priority' => 'HIGH',
                'reporter_name' => 'Ibu Wayan Ratna Dewi',
                'reporter_phone' => '081238910001',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 25,
            ],
            [
                'room_id' => $roomIgd->id,
                'target_object' => 'Perawat Triase IGD',
                'isi_laporan' => 'Pelayanan di IGD sangat cekatan dan sigap, perawat jaga langsung sigap memasang infus dan menenangkan pasien yang cemas.',
                'ai_sentiment' => 'POSITIF',
                'ai_category' => 'Sikap & Keramahan Staf',
                'ai_score' => 5,
                'ai_confidence' => '98%',
                'priority' => 'LOW',
                'reporter_name' => 'Bpk. Made Artana',
                'reporter_phone' => '081238910002',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 45,
            ],
            [
                'room_id' => $roomPendaftaran->id,
                'target_object' => 'AC Ruang Tunggu Pendaftaran',
                'isi_laporan' => 'Suhu AC di ruang tunggu pendaftaran terasa agak panas saat pengunjung sedang ramai siang hari, mohon dicek perawatannya.',
                'ai_sentiment' => 'NEGATIF',
                'ai_category' => 'Sarana & Prasarana',
                'ai_score' => -3,
                'ai_confidence' => '92%',
                'priority' => 'NORMAL',
                'reporter_name' => 'I Ketut Sudirga',
                'reporter_phone' => '081238910003',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 70,
            ],
            [
                'room_id' => $roomKasir->id,
                'target_object' => 'Petugas Kasir Pembayaran',
                'isi_laporan' => 'Petugas loket kasir menjelaskan rincian biaya pengobatan dan klaim BPJS dengan sangat sabar, ramah, dan transparan.',
                'ai_sentiment' => 'POSITIF',
                'ai_category' => 'Komunikasi & Informasi',
                'ai_score' => 5,
                'ai_confidence' => '96%',
                'priority' => 'LOW',
                'reporter_name' => 'Ni Nyoman Ayu Laksmi',
                'reporter_phone' => '081238910004',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 95,
            ],
            [
                'room_id' => $roomRanap->id,
                'target_object' => 'Kamar Mandi Rawat Inap',
                'isi_laporan' => 'Kamar rawat inap sangat bersih dan wangi, sprei tempat tidur pasien diganti tepat waktu setiap pagi oleh perawat.',
                'ai_sentiment' => 'POSITIF',
                'ai_category' => 'Kebersihan & Kenyamanan',
                'ai_score' => 5,
                'ai_confidence' => '97%',
                'priority' => 'LOW',
                'reporter_name' => 'Bpk. Komang Sujana',
                'reporter_phone' => '081238910005',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 120,
            ],
            [
                'room_id' => $roomLab->id,
                'target_object' => 'Layanan Tes Darah Lab',
                'isi_laporan' => 'Hasil laboratorium darah rutin keluar lebih dari 2 jam dari estimasi 1 jam yang dijanjikan di awal, mohon percepatannya.',
                'ai_sentiment' => 'NEGATIF',
                'ai_category' => 'Waktu Tunggu & Antrean',
                'ai_score' => -4,
                'ai_confidence' => '91%',
                'priority' => 'HIGH',
                'reporter_name' => 'Ibu Luh Putu Sunarti',
                'reporter_phone' => '081238910006',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 150,
            ],
            [
                'room_id' => $roomPoliUmum->id,
                'target_object' => 'Dokter Poli Umum',
                'isi_laporan' => 'Dokter di Poli Umum mendengarkan keluhan dengan teliti dan memberikan edukasi pola hidup sehat dengan sangat memuaskan.',
                'ai_sentiment' => 'POSITIF',
                'ai_category' => 'Pelayanan Medis',
                'ai_score' => 5,
                'ai_confidence' => '99%',
                'priority' => 'LOW',
                'reporter_name' => 'I Gede Yudiantara',
                'reporter_phone' => '081238910007',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 180,
            ],
            [
                'room_id' => $roomAreaPublik->id,
                'target_object' => 'Sistem Informasi Antrean',
                'isi_laporan' => 'Alangkah baiknya bila layar monitor nomor antrean juga dilengkapi speaker audio di koridor penghubung agar tidak terlewat.',
                'ai_sentiment' => 'NETRAL',
                'ai_category' => 'Sarana & Prasarana',
                'ai_score' => 0,
                'ai_confidence' => '88%',
                'priority' => 'NORMAL',
                'reporter_name' => 'Bpk. Made Wira',
                'reporter_phone' => '081238910008',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 210,
            ],
            [
                'room_id' => $roomFarmasi->id,
                'target_object' => 'Edukasi Obat Apotek',
                'isi_laporan' => 'Apoteker memberikan penjelasan aturan pakai obat secara detail dan memberikan label pengingat yang sangat bermanfaat.',
                'ai_sentiment' => 'POSITIF',
                'ai_category' => 'Komunikasi & Informasi',
                'ai_score' => 5,
                'ai_confidence' => '95%',
                'priority' => 'LOW',
                'reporter_name' => 'Ibu Kadek Melati',
                'reporter_phone' => '081238910009',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 240,
            ],
            [
                'room_id' => $roomIgd->id,
                'target_object' => 'Fasilitas Kursi Roda IGD',
                'isi_laporan' => 'Ketersediaan kursi roda di lobi depan IGD mohon ditambah saat jam puncak kedatangan pasien agar pasien lansia tidak perlu menunggu.',
                'ai_sentiment' => 'NETRAL',
                'ai_category' => 'Sarana & Prasarana',
                'ai_score' => 0,
                'ai_confidence' => '90%',
                'priority' => 'HIGH',
                'reporter_name' => 'Bpk. Ketut Astawa',
                'reporter_phone' => '081238910010',
                'shift_info' => 'Pagi (07.00 - 14.00)',
                'minutes_ago' => 270,
            ],
        ];

        foreach ($pendingReportsData as $idx => $repData) {
            $ticketNumber = 'LP-' . $now->format('Y-m') . '-' . str_pad(100 + $idx, 4, '0', STR_PAD_LEFT);
            $createdAt = $now->copy()->subMinutes($repData['minutes_ago']);

            Report::updateOrCreate(
                ['ticket_number' => $ticketNumber],
                [
                    'uuid' => (string) Str::uuid(),
                    'room_id' => $repData['room_id'],
                    'target_object' => $repData['target_object'],
                    'isi_laporan' => $repData['isi_laporan'],
                    'ai_sentiment' => $repData['ai_sentiment'],
                    'ai_category' => $repData['ai_category'],
                    'ai_score' => $repData['ai_score'],
                    'ai_confidence' => $repData['ai_confidence'],
                    'ai_metadata' => [
                        'summary' => $repData['isi_laporan'],
                        'sentiment' => $repData['ai_sentiment'],
                        'category' => $repData['ai_category'],
                    ],
                    'shift_info' => $repData['shift_info'],
                    'reporter_name' => $repData['reporter_name'],
                    'reporter_phone' => $repData['reporter_phone'],
                    'is_anonymous' => false,
                    'status' => 'PENDING',
                    'priority' => $repData['priority'],
                    'verified_by' => null,
                    'verified_at' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }

        // 5. Past Historical Reports (Terverifikasi & Selesai) selama 14 hari terakhir
        // Agar grafik tren, donat sentimen, kategori, dan zona merah unit terisi data riil yang kaya
        $categoriesList = [
            'Waktu Tunggu & Antrean',
            'Sikap & Keramahan Staf',
            'Sarana & Prasarana',
            'Kebersihan & Kenyamanan',
            'Komunikasi & Informasi',
            'Pelayanan Medis',
        ];

        $roomsList = [$roomIgd, $roomFarmasi, $roomRanap, $roomPoliUmum, $roomLab, $roomKasir, $roomPendaftaran];

        for ($day = 1; $day <= 13; $day++) {
            $reportDate = $now->copy()->subDays($day);
            // 2 sampai 4 laporan per hari di masa lalu
            $reportsCount = rand(2, 4);

            for ($i = 0; $i < $reportsCount; $i++) {
                $rRoom = $roomsList[array_rand($roomsList)];
                $sentiment = (rand(1, 10) <= 7) ? 'POSITIF' : ((rand(1, 10) <= 6) ? 'NEGATIF' : 'NETRAL');
                $score = $sentiment === 'POSITIF' ? 5 : ($sentiment === 'NEGATIF' ? -5 : 0);
                $category = $categoriesList[array_rand($categoriesList)];

                $text = match ($sentiment) {
                    'POSITIF' => 'Pelayanan di ruangan ' . $rRoom->name . ' sangat ramah, cepat, dan petugas tanggap dalam melayani kebutuhan pasien.',
                    'NEGATIF' => 'Waktu pelayanan di ' . $rRoom->name . ' agak memakan waktu lama saat jam kunjungan padat, mohon ditingkatkan kecepatannya.',
                    default => 'Secara umum pelayanan di ' . $rRoom->name . ' sudah cukup baik, saran kami sistem informasi pemanggilan antrean dioptimalkan.',
                };

                $ticketNum = 'LP-' . $reportDate->format('Y-m') . '-' . str_pad((200 + ($day * 10) + $i), 4, '0', STR_PAD_LEFT);
                $createdAt = $reportDate->copy()->setHour(rand(8, 16))->setMinute(rand(0, 59));
                $verifiedAt = $createdAt->copy()->addMinutes(rand(30, 150));

                Report::updateOrCreate(
                    ['ticket_number' => $ticketNum],
                    [
                        'uuid' => (string) Str::uuid(),
                        'room_id' => $rRoom->id,
                        'target_object' => 'Staf Pelayanan ' . $rRoom->name,
                        'isi_laporan' => $text,
                        'ai_sentiment' => $sentiment,
                        'ai_category' => $category,
                        'ai_score' => $score,
                        'ai_confidence' => rand(88, 99) . '%',
                        'ai_metadata' => [
                            'summary' => $text,
                            'sentiment' => $sentiment,
                            'category' => $category,
                        ],
                        'shift_info' => 'Pagi (07.00 - 14.00)',
                        'reporter_name' => 'Keluarga Pasien ' . $rRoom->name,
                        'reporter_phone' => '0812' . rand(10000000, 99999999),
                        'is_anonymous' => false,
                        'status' => 'VERIFIED',
                        'priority' => 'NORMAL',
                        'verified_by' => $verifierId,
                        'verified_at' => $verifiedAt,
                        'supervisor_notes' => 'Telah ditindaklanjuti dan diverifikasi sesuai SOP unit.',
                        'created_at' => $createdAt,
                        'updated_at' => $verifiedAt,
                    ]
                );
            }
        }
    }
}
