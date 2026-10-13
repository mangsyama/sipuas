<?php

namespace App\Imports;

use App\Models\Room;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StaffImport implements ToCollection, WithHeadingRow
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    public function collection(Collection $rows)
    {
        // Pre-fetch seluruh master data ruangan untuk pencocokan cepat
        $allRooms = Room::all(['id', 'name']);

        $rowNumber = 1; // Baris 1 adalah header di spreadsheet
        foreach ($rows as $row) {
            $rowNumber++;

            // Tangkap nilai-nilai kolom dengan toleransi berbagai variasi penamaan header
            $rawName = $row['nama_staf'] ?? $row['nama'] ?? $row['name'] ?? $row['nama_pegawai'] ?? $row['nama_lengkap'] ?? null;
            $rawStatus = $row['status_aktif'] ?? $row['status'] ?? $row['is_active'] ?? $row['status_pegawai'] ?? $row['aktif'] ?? null;
            $rawNip = $row['nip'] ?? $row['nip_pegawai'] ?? $row['id_pegawai'] ?? $row['nik'] ?? null;
            $rawPhone = $row['nomor_hp'] ?? $row['no_hp'] ?? $row['telepon'] ?? $row['phone_number'] ?? $row['wa'] ?? $row['no_telepon'] ?? null;
            $rawPosition = $row['jabatan'] ?? $row['profesi'] ?? $row['posisi'] ?? $row['position'] ?? null;
            $rawRooms = $row['ruangan'] ?? $row['unit'] ?? $row['rooms'] ?? $row['penempatan'] ?? $row['poli'] ?? null;

            // Lewati baris yang benar-benar kosong
            if (empty($rawName) && empty($rawNip) && empty($rawPosition) && empty($rawStatus)) {
                $this->skippedCount++;
                continue;
            }

            // 1. Validasi Nama Staf (WAJIB)
            $name = mb_substr(trim((string)$rawName), 0, 150);
            if (empty($name)) {
                $this->errors[] = "Baris #{$rowNumber}: Nama staf wajib diisi.";
                $this->skippedCount++;
                continue;
            }

            // 2. Validasi Status Aktif (WAJIB)
            if ($rawStatus === null || trim((string)$rawStatus) === '') {
                $this->errors[] = "Baris #{$rowNumber} ({$name}): Status aktif wajib diisi (isi 'Aktif' atau 'Tidak Aktif').";
                $this->skippedCount++;
                continue;
            }

            $statusLower = strtolower(trim((string)$rawStatus));
            $isActive = true;
            if (in_array($statusLower, ['tidak', 'tidak aktif', 'nonaktif', 'non-aktif', 'non aktif', 'inaktif', '0', 'false', 'off', 'inaktip', 'non active', 'inactive', 'no'])) {
                $isActive = false;
            } elseif (in_array($statusLower, ['aktif', 'active', 'ya', 'yes', '1', 'true', 'on'])) {
                $isActive = true;
            } else {
                $this->errors[] = "Baris #{$rowNumber} ({$name}): Nilai status aktif '{$rawStatus}' tidak dikenal. Harap gunakan 'Aktif' atau 'Tidak Aktif'.";
                $this->skippedCount++;
                continue;
            }

            // 3. Normalisasi NIP (OPSIONAL - Boleh kosong)
            $nip = null;
            if ($rawNip !== null && trim((string)$rawNip) !== '') {
                $cleanNip = trim((string)$rawNip);
                if (is_numeric($cleanNip)) {
                    $cleanNip = sprintf('%.0f', (float)$cleanNip);
                }
                $nipCleaned = preg_replace('/[^\w\.\-\/]/', '', $cleanNip);
                if (!empty($nipCleaned)) {
                    $nip = mb_substr($nipCleaned, 0, 50);
                }
            }

            // 4. Normalisasi Nomor HP (OPSIONAL - Boleh kosong)
            $phone = null;
            if (!empty($rawPhone)) {
                $cleanedPhone = preg_replace('/\D/', '', (string)$rawPhone);
                if (!empty($cleanedPhone)) {
                    $phone = mb_substr($cleanedPhone, 0, 30);
                }
            }

            // 5. Normalisasi Jabatan (OPSIONAL - Boleh kosong, default: 'Staf Pelaksana')
            $position = mb_substr(trim((string)$rawPosition), 0, 100);
            if (empty($position)) {
                $position = 'Staf Pelaksana';
            }

            // 6. Pencocokan Penempatan Ruangan (OPSIONAL - Boleh kosong)
            $matchedRoomIds = [];
            if (!empty($rawRooms)) {
                $roomTokens = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$rawRooms)));
                foreach ($roomTokens as $token) {
                    $tokenLower = strtolower($token);
                    
                    // 1. Pencocokan tepat
                    $matched = $allRooms->first(fn($r) => strtolower($r->name) === $tokenLower);

                    // 2. Pencocokan parsial
                    if (!$matched) {
                        $matched = $allRooms->first(function ($r) use ($tokenLower) {
                            $rLower = strtolower($r->name);
                            return str_contains($rLower, $tokenLower) || str_contains($tokenLower, $rLower);
                        });
                    }

                    if ($matched) {
                        $matchedRoomIds[] = $matched->id;
                    }
                }
                $matchedRoomIds = array_unique($matchedRoomIds);
            }

            // 7. Simpan / Perbarui Data Staf (Upsert berdasarkan NIP jika ada, atau Nama Staf jika tanpa NIP)
            $existingStaff = null;
            if (!empty($nip)) {
                $existingStaff = Staff::where('nip', $nip)->first();
            }
            if (!$existingStaff) {
                $existingStaff = Staff::where('name', $name)->first();
            }

            $linkedUser = !empty($nip) ? User::where('nip', $nip)->first() : null;

            if ($existingStaff) {
                $updatePayload = [
                    'name' => $name,
                    'is_active' => $isActive,
                ];

                if (!empty($nip)) {
                    $updatePayload['nip'] = $nip;
                }
                if (!empty($rawPosition)) {
                    $updatePayload['position'] = $position;
                }
                if ($phone !== null) {
                    $updatePayload['phone_number'] = $phone;
                }
                if ($linkedUser && empty($existingStaff->user_id)) {
                    $updatePayload['user_id'] = $linkedUser->id;
                }

                $existingStaff->update($updatePayload);

                // Sinkronisasi ruangan jika kolom ruangan diisi pada file
                if (!empty($rawRooms)) {
                    $existingStaff->rooms()->sync($matchedRoomIds);
                }

                $this->updatedCount++;
            } else {
                $newStaff = Staff::create([
                    'name' => $name,
                    'position' => $position,
                    'nip' => $nip,
                    'phone_number' => $phone,
                    'is_active' => $isActive,
                    'user_id' => $linkedUser ? $linkedUser->id : null,
                    'total_points' => 100,
                    'praise_count' => 0,
                    'complaint_count' => 0,
                ]);

                if (!empty($matchedRoomIds)) {
                    $newStaff->rooms()->sync($matchedRoomIds);
                }

                $this->createdCount++;
            }
        }
    }
}
