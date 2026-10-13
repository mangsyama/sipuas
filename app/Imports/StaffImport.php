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
            $rawName = $row['nama_staf'] ?? $row['nama'] ?? $row['name'] ?? null;
            $rawNip = $row['nip'] ?? $row['nip_pegawai'] ?? $row['id_pegawai'] ?? null;
            $rawPhone = $row['nomor_hp'] ?? $row['no_hp'] ?? $row['telepon'] ?? $row['phone_number'] ?? $row['wa'] ?? null;
            $rawPosition = $row['jabatan'] ?? $row['profesi'] ?? $row['posisi'] ?? $row['position'] ?? null;
            $rawRooms = $row['ruangan'] ?? $row['unit'] ?? $row['rooms'] ?? $row['penempatan'] ?? null;
            $rawStatus = $row['status_aktif'] ?? $row['status'] ?? $row['is_active'] ?? null;

            // Lewati baris yang benar-benar kosong
            if (empty($rawName) && empty($rawNip) && empty($rawPosition)) {
                $this->skippedCount++;
                continue;
            }

            // Validasi Nama Staf
            $name = trim((string)$rawName);
            if (empty($name)) {
                $this->errors[] = "Baris #{$rowNumber}: Nama staf tidak boleh kosong.";
                $this->skippedCount++;
                continue;
            }

            // Normalisasi & Validasi NIP (18 digit angka)
            if (is_numeric($rawNip)) {
                $rawNip = sprintf('%.0f', $rawNip);
            }
            $nip = preg_replace('/\D/', '', (string)$rawNip);

            if (empty($nip) || strlen($nip) !== 18) {
                $nipDisplay = !empty($rawNip) ? (string)$rawNip : '(kosong)';
                $this->errors[] = "Baris #{$rowNumber} ({$name}): NIP '{$nipDisplay}' tidak valid. NIP wajib terdiri dari 18 digit angka.";
                $this->skippedCount++;
                continue;
            }

            // Normalisasi Nomor HP (opsional, jika ada 10 - 15 digit angka)
            $phone = null;
            if (!empty($rawPhone)) {
                $cleanedPhone = preg_replace('/\D/', '', (string)$rawPhone);
                if (strlen($cleanedPhone) >= 10 && strlen($cleanedPhone) <= 15) {
                    $phone = $cleanedPhone;
                }
            }

            // Normalisasi Jabatan
            $position = trim((string)$rawPosition);
            if (empty($position)) {
                $position = 'Staf Pelaksana';
            }

            // Normalisasi Status Aktif
            $isActive = true;
            if (!empty($rawStatus)) {
                $statusLower = strtolower(trim((string)$rawStatus));
                if (in_array($statusLower, ['tidak', 'tidak aktif', 'nonaktif', 'non-aktif', 'inaktif', '0', 'false', 'off'])) {
                    $isActive = false;
                }
            }

            // Pencocokan Penempatan Ruangan (Multi-ruangan dengan pemisah koma/titik koma)
            $matchedRoomIds = [];
            if (!empty($rawRooms)) {
                $roomTokens = array_filter(array_map('trim', preg_split('/[,;]+/', (string)$rawRooms)));
                foreach ($roomTokens as $token) {
                    $tokenLower = strtolower($token);
                    
                    // 1. Pencocokan tepat (Exact case-insensitive)
                    $matched = $allRooms->first(fn($r) => strtolower($r->name) === $tokenLower);

                    // 2. Pencocokan parsial jika tidak ada exact match (contoh: "IGD" cocok dengan "IGD (Instalasi Gawat Darurat)")
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

            // Simpan / Perbarui Data Staf (Upsert berdasarkan NIP)
            $existingStaff = Staff::where('nip', $nip)->first();
            $linkedUser = User::where('nip', $nip)->first();

            if ($existingStaff) {
                $existingStaff->update([
                    'name' => $name,
                    'position' => $position,
                    'phone_number' => $phone,
                    'is_active' => $isActive,
                    'user_id' => $existingStaff->user_id ?: ($linkedUser ? $linkedUser->id : null),
                ]);

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
