<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PesupeluhService
{
    /**
     * Get active issue categories from Pesu Peluh.
     * Queries direct DB connection with fallback to HTTP API.
     */
    public function getCategories(): array
    {
        // 1. Try Direct Database Connection (Fastest & Most Reliable)
        try {
            $categories = DB::connection('pesupeluh')
                ->table('issue_categories')
                ->leftJoin('supporting_units', 'issue_categories.supporting_unit_id', '=', 'supporting_units.id')
                ->select(
                    'issue_categories.id',
                    'issue_categories.name',
                    'issue_categories.description',
                    'supporting_units.name as unit_name',
                    'supporting_units.type as unit_type'
                )
                ->whereNull('issue_categories.deleted_at')
                ->orderBy('supporting_units.name')
                ->orderBy('issue_categories.name')
                ->get();

            if ($categories->isNotEmpty()) {
                return $categories->map(function ($c) {
                    $prefix = ($c->unit_name && strtoupper(trim($c->unit_name)) !== 'IPSRS') ? "[{$c->unit_name}] " : "";
                    return [
                        'id' => (int) $c->id,
                        'name' => $c->name,
                        'unit_name' => $c->unit_name ?: 'Penunjang Umum',
                        'unit_type' => $c->unit_type,
                        'description' => $c->description,
                        'display_label' => $prefix . $c->name,
                    ];
                })->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('PesupeluhService::getCategories via DB failed: ' . $e->getMessage());
        }

        // 2. Fallback to HTTP API
        try {
            $apiUrl = rtrim(config('services.pesupeluh.api_url'), '/');
            $apiToken = config('services.pesupeluh.api_token');

            $response = Http::timeout(5)
                ->withHeaders([
                    'X-Integration-Token' => $apiToken,
                    'Accept' => 'application/json',
                ])
                ->get("{$apiUrl}/integration/categories");

            if ($response->successful() && $response->json('categories')) {
                return collect($response->json('categories'))->map(function ($c) {
                    $prefix = (!empty($c['unit_name']) && strtoupper(trim($c['unit_name'])) !== 'IPSRS') ? "[{$c['unit_name']}] " : "";
                    return [
                        'id' => (int) $c['id'],
                        'name' => $c['name'],
                        'unit_name' => $c['unit_name'] ?: 'Penunjang Umum',
                        'unit_type' => $c['unit_type'] ?? null,
                        'description' => $c['description'] ?? null,
                        'display_label' => $prefix . $c['name'],
                    ];
                })->toArray();
            }
        } catch (\Throwable $e) {
            Log::error('PesupeluhService::getCategories via HTTP failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get active rooms from Pesu Peluh.
     */
    public function getRooms(): array
    {
        try {
            return DB::connection('pesupeluh')
                ->table('rooms')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'building_name', 'location_floor'])
                ->map(function ($r) {
                    $details = array_filter([$r->building_name, $r->location_floor]);
                    return [
                        'id' => (int) $r->id,
                        'name' => $r->name,
                        'display_label' => $r->name . (!empty($details) ? ' (' . implode(' - ', $details) . ')' : ''),
                    ];
                })
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('PesupeluhService::getRooms failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Dispatch / Forward a SIPUAS report to PESU PELUH.
     */
    public function dispatchReport(Report $report, array $params, ?User $kasiUser = null): array
    {
        $report->loadMissing(['room', 'attachments']);

        $categoryId = $params['category_id'] ?? null;
        $pesupeluhRoomId = $params['pesupeluh_room_id'] ?? null;
        $priority = in_array(strtoupper($params['priority'] ?? ''), ['URGENT', 'ROUTINE'])
            ? strtoupper($params['priority'])
            : ($report->priority === 'HIGH' || $report->priority === 'URGENT' ? 'URGENT' : 'ROUTINE');
        $supervisorNotes = $params['supervisor_notes'] ?? null;

        // 1. Prepare Base64 attachments from SIPUAS storage
        $base64Attachments = [];
        foreach ($report->attachments as $att) {
            try {
                $rawContent = null;
                if (Storage::disk('public')->exists($att->file_path)) {
                    $rawContent = Storage::disk('public')->get($att->file_path);
                } elseif (Storage::exists($att->file_path)) {
                    $rawContent = Storage::get($att->file_path);
                }

                if ($rawContent) {
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($rawContent) ?: ($att->mime_type ?: 'image/jpeg');
                    $base64Attachments[] = 'data:' . $mime . ';base64,' . base64_encode($rawContent);
                }
            } catch (\Throwable $e) {
                Log::warning('PesupeluhService: Gagal membaca file lampiran untuk dikirim: ' . $e->getMessage());
            }
        }

        // 2. Prepare Payload
        $payload = [
            'room_id' => $report->room_id,
            'pesupeluh_room_id' => $pesupeluhRoomId,
            'room_name' => $report->room ? $report->room->name : null,
            'category_id' => (int) $categoryId,
            'priority' => $priority,
            'problem_description' => $report->isi_laporan,
            'reporter_name' => $report->is_anonymous ? null : ($report->reporter_name ?: 'Masyarakat'),
            'reporter_phone' => $report->is_anonymous ? null : $report->reporter_phone,
            'is_anonymous' => (bool) $report->is_anonymous,
            'sipuas_ticket_number' => $report->ticket_number,
            'kasi_name' => $kasiUser ? $kasiUser->name : 'KASI Verifikator',
            'kasi_nip' => $kasiUser ? $kasiUser->nip : null,
            'supervisor_notes' => $supervisorNotes,
            'attachments' => $base64Attachments,
        ];

        $ticketId = null;
        $ticketNumber = null;

        // 3. Attempt Method A: HTTP API to Pesu Peluh
        try {
            $apiUrl = rtrim(config('services.pesupeluh.api_url'), '/');
            $apiToken = config('services.pesupeluh.api_token');

            $response = Http::timeout(12)
                ->withHeaders([
                    'X-Integration-Token' => $apiToken,
                    'Accept' => 'application/json',
                ])
                ->post("{$apiUrl}/integration/sipuas-ticket", $payload);

            if ($response->successful() && $response->json('ticket_number')) {
                $ticketId = $response->json('ticket_id');
                $ticketNumber = $response->json('ticket_number');
            } else {
                Log::warning('Pesupeluh HTTP API returned error: ' . $response->status() . ' body: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::warning('Pesupeluh HTTP API call failed: ' . $e->getMessage() . '. Falling back to Direct DB.');
        }

        // 4. Attempt Method B: Direct Database Insertion Fallback
        if (!$ticketNumber) {
            try {
                $ticketNumber = $this->dispatchViaDirectDb($report, $payload, $kasiUser, $base64Attachments);
            } catch (\Throwable $dbErr) {
                Log::error('Pesupeluh Direct DB Fallback failed: ' . $dbErr->getMessage());
            }
        }

        // 5. Update SIPUAS Report with Pesu Peluh Reference
        if ($ticketNumber) {
            $report->update([
                'pesupeluh_ticket_id' => $ticketId,
                'pesupeluh_ticket_number' => $ticketNumber,
                'dispatched_to_pesupeluh_at' => Carbon::now(),
            ]);

            return [
                'success' => true,
                'ticket_number' => $ticketNumber,
                'ticket_id' => $ticketId,
                'message' => "Tiket berhasil diteruskan ke PESU PELUH dengan nomor {$ticketNumber}.",
            ];
        }

        return [
            'success' => false,
            'message' => 'Gagal menghubungkan atau meneruskan tiket ke sistem PESU PELUH.',
        ];
    }

    /**
     * Direct Database insertion fallback if HTTP API is not available.
     */
    private function dispatchViaDirectDb(Report $report, array $payload, ?User $kasiUser, array $base64Attachments): ?string
    {
        $pesuConn = DB::connection('pesupeluh');

        // 1. Resolve room_id: Prioritize manually selected pesupeluh_room_id, else smart fuzzy matching
        $roomId = null;
        if (!empty($payload['pesupeluh_room_id']) && $pesuConn->table('rooms')->where('id', $payload['pesupeluh_room_id'])->exists()) {
            $roomId = (int) $payload['pesupeluh_room_id'];
        }

        $pesuRoom = null;
        $originalRoom = $report->room ? $report->room->name : 'Unit Terkait';

        if (!$roomId) {
            if ($report->room) {
                $rawRoomName = $report->room->name;
                $pesuRoom = $pesuConn->table('rooms')->where('name', $rawRoomName)->first();

                if (!$pesuRoom) {
                    $allPesuRooms = $pesuConn->table('rooms')->get();
                    $clean = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $rawRoomName));
                    $stopWords = ['ruang', 'ruangan', 'rawat', 'inap', 'gedung', 'lantai', 'kelas', 'unit', 'area'];
                    $words = array_filter(explode(' ', $clean), fn($w) => strlen($w) >= 3 && !in_array($w, $stopWords));

                    foreach ($words as $word) {
                        foreach ($allPesuRooms as $pr) {
                            $pWords = array_filter(explode(' ', strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $pr->name))), fn($pw) => strlen($pw) >= 3);
                            foreach ($pWords as $pw) {
                                similar_text($word, $pw, $percent);
                                if ($percent >= 75 || str_contains($pw, $word) || str_contains($word, $pw)) {
                                    $pesuRoom = $pr;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }

            if ($pesuRoom) {
                $roomId = $pesuRoom->id;
            } elseif ($report->room_id && $pesuConn->table('rooms')->where('id', $report->room_id)->exists()) {
                $roomId = $report->room_id;
            } else {
                $roomId = $pesuConn->table('rooms')->value('id') ?? 1;
            }
        }

        // 2. Resolve or Create Virtual Reporter User in Pesu Peluh
        $reporterId = $this->resolvePesuPeluhReporterId($report, $pesuConn);

        // 3. Generate ticket number
        $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        while ($pesuConn->table('service_tickets')->where('ticket_number', $ticketNumber)->exists()) {
            $ticketNumber = 'TK-' . date('Ymd') . '-' . str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $reporterLabel = $payload['is_anonymous'] 
            ? 'Masyarakat / Publik (Anonim)' 
            : ($payload['reporter_name'] ?: 'Masyarakat');
        if (!empty($payload['reporter_phone']) && !$payload['is_anonymous']) {
            $reporterLabel .= ' (HP: ' . $payload['reporter_phone'] . ')';
        }

        $formattedDesc = "📌 [DISPOSISI ADUAN PUBLIK SIPUAS]\n"
            . "No. Tiket SIPUAS: {$report->ticket_number}\n"
            . "Lokasi / Ruangan: {$originalRoom}\n"
            . "Pelapor: {$reporterLabel}\n"
            . "Diteruskan oleh: " . ($kasiUser ? $kasiUser->name : 'KASI Pelayanan') . "\n\n"
            . "--- URAIAN KELUHAN FASILITAS ---\n" . trim($payload['problem_description']);

        if (!empty($payload['supervisor_notes'])) {
            $formattedDesc .= "\n\n--- CATATAN VERIFIKATOR (KASI) ---\n" . trim($payload['supervisor_notes']);
        }

        $now = Carbon::now();

        $ticketId = $pesuConn->table('service_tickets')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'ticket_number' => $ticketNumber,
            'reporter_id' => $reporterId,
            'room_id' => $roomId,
            'category_id' => $payload['category_id'],
            'problem_description' => $formattedDesc,
            'priority' => $payload['priority'],
            'status' => 'PENDING_VALIDATION',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pesuConn->table('ticket_histories')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $reporterId,
            'status' => 'PENDING_VALIDATION',
            'action' => 'CREATED_VIA_SIPUAS',
            'notes' => "Tiket otomatis dibuat via disposisi aduan publik SIPUAS [{$report->ticket_number}] oleh " . ($kasiUser ? $kasiUser->name : 'KASI') . ".",
            'created_at' => $now,
        ]);

        // Copy attachments to pesupeluh public storage if accessible on disk
        $pesuStoragePublic = base_path('../pesupeluh/storage/app/public/ticket_attachments');
        if (is_dir($pesuStoragePublic) && is_writable($pesuStoragePublic)) {
            foreach ($base64Attachments as $b64) {
                if (str_contains($b64, ';base64,')) {
                    $parts = explode(';base64,', $b64);
                    $data = base64_decode($parts[1]);
                    $fileName = 'ticket_sipuas_' . Str::random(20) . '_' . time() . '.jpg';
                    $destPath = $pesuStoragePublic . DIRECTORY_SEPARATOR . $fileName;
                    if (file_put_contents($destPath, $data)) {
                        $pesuConn->table('ticket_attachments')->insert([
                            'ticket_id' => $ticketId,
                            'file_path' => '/storage/ticket_attachments/' . $fileName,
                            'uploaded_by' => $reporterId,
                            'uploaded_at' => $now,
                        ]);
                    }
                }
            }
        }

        return $ticketNumber;
    }

    /**
     * Resolve or find dedicated generic reporter user in Pesu Peluh.
     */
    private function resolvePesuPeluhReporterId(Report $report, $pesuConn): int
    {
        $targetUsername = config('services.pesupeluh.reporter_username', 'sipuas_masyarakat');

        // 1. Try finding user by configured username
        $user = $pesuConn->table('users')->where('username', $targetUsername)->first();
        if ($user) {
            return (int) $user->id;
        }

        // 2. Try finding user by known fallback usernames
        $fallbackUser = $pesuConn->table('users')
            ->whereIn('username', ['sipuas_masyarakat', 'masyarakat_sipuas', 'sipuas'])
            ->orWhere('name', 'like', '%Masyarakat%')
            ->first();
        if ($fallbackUser) {
            return (int) $fallbackUser->id;
        }

        // 3. Create ONE single dedicated user in Pesu Peluh with NIP '-'
        $now = Carbon::now();
        return (int) $pesuConn->table('users')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'name' => 'Masyarakat (via SIPUAS)',
            'username' => $targetUsername ?: 'sipuas_masyarakat',
            'nip' => '-',
            'email' => 'sipuas_masyarakat@rsud.local',
            'password' => \Illuminate\Support\Facades\Hash::make(Str::random(16)),
            'phone_number' => '-',
            'role_id' => 11, // STAFF
            'is_active' => 1,
            'is_on_duty' => 0,
            'duty_status' => 'READY',
            'approved_by' => 1,
            'approved_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
