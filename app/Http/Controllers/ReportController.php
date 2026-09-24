<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Room;
use App\Channels\WaGatewayChannel;
use App\Services\AiAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    protected AiAnalysisService $aiService;

    public function __construct(AiAnalysisService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Show public report creation form.
     */
    public function create(Request $request): Response
    {
        $rooms = Room::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->location_info,
                'building_name' => $r->building_name,
                'location_floor' => $r->location_floor,
            ]);

        $step = $request->query('step');
        $selectedRoomParam = $request->query('room_id', $request->query('room', $request->query('unit', '')));
        $target = $request->query('target', $request->query('target_object', $request->query('doctor', '')));
        $mode = $request->query('type', $request->query('mode', ''));

        // Match room by ID or name
        $selectedRoomId = '';
        if (!empty($selectedRoomParam)) {
            $matched = $rooms->first(function ($r) use ($selectedRoomParam) {
                return (string) $r['id'] === (string) $selectedRoomParam 
                    || strcasecmp($r['name'], $selectedRoomParam) === 0;
            });
            $selectedRoomId = $matched ? (string) $matched['id'] : (string) $selectedRoomParam;
        }

        // Dedicated staff review detection: only if explicit type=review or doctor param or target passed with room
        $isStaffReview = ($mode === 'review') || $request->has('doctor') || (!empty($target) && $request->has('target'));

        // If scanning doctor/staff QR with pre-filled target and room, auto advance to step 2 if step is not explicitly set
        if ($step === null && !empty($selectedRoomId) && !empty($target) && $isStaffReview) {
            $step = 2;
        }

        return Inertia::render('Report/Create', [
            'roomId' => $selectedRoomId,
            'unitId' => $selectedRoomId, // Backward compatibility
            'targetObject' => $target,
            'reportMode' => $isStaffReview ? 'review' : $mode,
            'initialStep' => $step !== null ? (int) $step : null,
            'rooms' => $rooms,
            'units' => $rooms, // Backward compatibility
        ]);
    }

    /**
     * Store a newly created report from patient.
     */
    public function store(Request $request)
    {
        $report = null;
        try {
            $validated = $request->validate([
                'room_id' => 'nullable',
                'unit_id' => 'nullable',
                'target_object' => 'nullable|string|max:255',
                'isi_laporan' => 'required|string|min:5|max:3000',
                'reporter_name' => 'nullable|string|max:150',
                'reporter_phone' => 'nullable|string|max:30',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm|max:10240', // Max 10MB
            ]);

            $rawRoomId = $validated['room_id'] ?? $validated['unit_id'] ?? null;
            if (is_array($rawRoomId) || is_object($rawRoomId)) {
                $rawRoomId = is_array($rawRoomId) ? ($rawRoomId['id'] ?? $rawRoomId['name'] ?? null) : ($rawRoomId->id ?? $rawRoomId->name ?? null);
            }

            // Find room safely without triggering SQL Server type mismatch
            $room = null;
            if (is_numeric($rawRoomId)) {
                $room = Room::where('id', (int)$rawRoomId)->first();
            }
            if (!$room && !empty($rawRoomId) && is_string($rawRoomId) && $rawRoomId !== '[object Object]') {
                $room = Room::where('name', 'LIKE', '%' . trim((string)$rawRoomId) . '%')->first();
            }
            if (!$room) {
                $room = Room::where('is_active', true)->first() ?? Room::first();
            }

            // Safe fallback for room_id to avoid foreign key failure
            $fallbackRoomId = $room ? $room->id : (Room::query()->value('id') ?? 1);

            // Run AI Analysis with safety fallback
            try {
                $aiAnalysis = $this->aiService->analyzeReport(
                    $validated['isi_laporan'],
                    $room ? $room->name : 'Umum',
                    $validated['target_object'] ?? null
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('AI Analysis Warning: ' . $e->getMessage());
                $aiAnalysis = $this->aiService->fallbackHeuristicAnalysis(
                    $validated['isi_laporan'],
                    $room ? $room->name : 'Umum',
                    $validated['target_object'] ?? null
                );
            }

            $sentiment = $aiAnalysis['sentiment'] ?? 'NETRAL';
            $score = $aiAnalysis['score'] ?? 0;
            $category = $aiAnalysis['category'] ?? 'Pelayanan Umum';
            $confidence = $aiAnalysis['confidence'] ?? '95%';

            // Determine shift from current time
            $hour = (int) date('H');
            $shiftInfo = ($hour >= 7 && $hour < 14) 
                ? 'Shift Pagi (07:00 - 14:00 WITA)' 
                : (($hour >= 14 && $hour < 21) ? 'Shift Siang (14:00 - 21:00 WITA)' : 'Shift Malam (21:00 - 07:00 WITA)');

            $ticketNumber = Report::generateTicketNumber();
            $clientIp = $request->ip();
            $clientUserAgent = $request->userAgent();
            $deviceSummary = self::parseDeviceSummary($clientUserAgent);

            // Try creating report record with full fields, with minimal fallback on error
            try {
                $report = Report::create([
                    'ticket_number' => $ticketNumber,
                    'room_id' => $fallbackRoomId,
                    'target_object' => mb_substr($validated['target_object'] ?? '', 0, 250) ?: null,
                    'isi_laporan' => $validated['isi_laporan'],
                    'ai_sentiment' => $sentiment,
                    'ai_category' => mb_substr($category, 0, 90),
                    'ai_score' => (int) $score,
                    'ai_confidence' => mb_substr((string) $confidence, 0, 18),
                    'ai_metadata' => is_array($aiAnalysis) ? $aiAnalysis : [],
                    'shift_info' => $shiftInfo,
                    'reporter_name' => mb_substr($validated['reporter_name'] ?? 'Anonim', 0, 140),
                    'reporter_phone' => mb_substr($validated['reporter_phone'] ?? '', 0, 28) ?: null,
                    'is_anonymous' => empty($validated['reporter_name']) || strtolower(trim($validated['reporter_name'])) === 'anonim',
                    'ip_address' => mb_substr($clientIp ?? '', 0, 45) ?: null,
                    'user_agent' => mb_substr($clientUserAgent ?? '', 0, 1000) ?: null,
                    'device_info' => $deviceSummary,
                    'status' => 'PENDING',
                    'priority' => in_array(strtoupper($aiAnalysis['urgency'] ?? ''), ['TINGGI', 'KRITIS']) ? 'HIGH' : 'NORMAL',
                ]);
            } catch (\Throwable $createErr) {
                \Illuminate\Support\Facades\Log::error('Report::create Primary Attempt Notice: ' . $createErr->getMessage() . '. Executing minimal fallback insert.');
                
                $report = Report::create([
                    'ticket_number' => $ticketNumber,
                    'room_id' => $fallbackRoomId,
                    'isi_laporan' => $validated['isi_laporan'],
                    'reporter_name' => mb_substr($validated['reporter_name'] ?? 'Anonim', 0, 140),
                    'reporter_phone' => mb_substr($validated['reporter_phone'] ?? '', 0, 28) ?: null,
                    'is_anonymous' => empty($validated['reporter_name']) || strtolower(trim($validated['reporter_name'])) === 'anonim',
                    'ip_address' => mb_substr($clientIp ?? '', 0, 45) ?: null,
                    'user_agent' => mb_substr($clientUserAgent ?? '', 0, 1000) ?: null,
                    'device_info' => $deviceSummary,
                    'status' => 'PENDING',
                    'priority' => 'NORMAL',
                    'ai_sentiment' => 'NETRAL',
                ]);
            }

            // Process file attachment if present
            if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
                try {
                    $file = $request->file('attachment');
                    $path = $file->store('attachments/' . date('Y/m'), 'public');
                    $mime = $file->getMimeType();
                    $type = str_starts_with($mime, 'video/') ? 'video' : 'image';

                    ReportAttachment::create([
                        'report_id' => $report->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $type,
                        'mime_type' => $mime,
                        'file_size_bytes' => $file->getSize(),
                    ]);
                } catch (\Throwable $attErr) {
                    \Illuminate\Support\Facades\Log::warning('Report Attachment upload notice: ' . $attErr->getMessage());
                }
            }

            // Real-Time System Notification Broadcast
            try {
                event(new \App\Events\NewReportSubmitted($report));
            } catch (\Throwable $bErr) {
                \Illuminate\Support\Facades\Log::info('Realtime broadcast notice: ' . $bErr->getMessage());
            }

            // Kirim Notifikasi WhatsApp secara Asinkron (Non-blocking via Queue / AfterResponse)
            try {
                $targetObject = $validated['target_object'] ?? '-';
                $isiLaporan = $validated['isi_laporan'] ?? '';
                $reporterPhone = $validated['reporter_phone'] ?? null;
                $reporterName = $validated['reporter_name'] ?? null;
                $roomId = $room ? $room->id : null;
                $roomLabel = $room 
                    ? ($room->location_info ? "{$room->name} ({$room->location_info})" : $room->name)
                    : 'Pelayanan Umum / Semua Ruangan';
                $verifyUrl = url('/kasi/verify/' . $ticketNumber);
                $trackingUrl = url('/report/track?ticket=' . $ticketNumber);
                $waktuLaporan = now()->translatedFormat('d F Y, H:i') . ' WITA';

                dispatch(function () use (
                    $roomId,
                    $roomLabel,
                    $ticketNumber,
                    $targetObject,
                    $isiLaporan,
                    $reporterPhone,
                    $reporterName,
                    $verifyUrl,
                    $trackingUrl,
                    $waktuLaporan
                ) {
                    try {
                        $kasiUsers = \App\Models\User::where('role_id', \App\Models\Role::KEPALA_SEKSI)
                            ->where('is_active', true)
                            ->where('wa_notify_enabled', true)
                            ->whereNotNull('phone_number')
                            ->where(function ($q) use ($roomId) {
                                if ($roomId) {
                                    $q->where('room_id', $roomId)
                                      ->orWhereNull('room_id');
                                } else {
                                    $q->whereNull('room_id');
                                }
                            })
                            ->get();

                        if ($kasiUsers->isNotEmpty()) {
                            $targetLabel = !empty($targetObject) && $targetObject !== '-' ? $targetObject : '-';

                            $waMessage = "🔔 *NOTIFIKASI SIPUAS*\n"
                                . "Ada laporan pelayanan baru masuk ke sistem:\n\n"
                                . "*No. Tiket :* {$ticketNumber}\n"
                                . "*Ruangan :* {$roomLabel}\n"
                                . "*Sasaran/Staf :* {$targetLabel}\n"
                                . "*Waktu :* {$waktuLaporan}\n"
                                . "*Uraian :* {$isiLaporan}\n\n"
                                . "Lihat Laporan:\n"
                                . "{$verifyUrl}";

                            foreach ($kasiUsers as $kasi) {
                                WaGatewayChannel::sendDirect($kasi->phone_number, $waMessage);
                            }
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::info('WA Gateway notification notice: ' . $e->getMessage());
                    }

                    // Kirim Notifikasi WhatsApp Konfirmasi & Pengingat Tiket ke Pelapor (jika nomor HP diisi)
                    if (!empty($reporterPhone)) {
                        try {
                            $hasName = !empty($reporterName) && strtolower(trim($reporterName)) !== 'anonim';
                            $greeting = $hasName ? "Halo *{$reporterName}*," : "Halo,";

                            $reporterWaMsg = "{$greeting}\n\n"
                                . "Terima kasih telah menyampaikan laporan/aspirasi pelayanan Anda melalui sistem *SIPUAS*.\n\n"
                                . "Berikut adalah rincian tiket aduan Anda:\n"
                                . "*Nomor Tiket :* *{$ticketNumber}*\n"
                                . "*Ruangan :* {$roomLabel}\n"
                                . "*Waktu :* {$waktuLaporan}\n"
                                . "*Status :* Menunggu Verifikasi Kepala Seksi\n\n"
                                . "Simpan nomor tiket ini untuk memantau proses tindak lanjut penanganan aduan Anda secara berkala melalui tautan berikut: {$trackingUrl}\n\n"
                                . "Setiap masukan Anda sangat berarti untuk peningkatan mutu pelayanan kami.\n\n"
                                . "Salam sehat,\n_Tim Manajemen Pelayanan Rumah Sakit_";

                            WaGatewayChannel::sendDirect($reporterPhone, $reporterWaMsg);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::info('Reporter WA confirmation failed: ' . $e->getMessage());
                        }
                    }
                })->afterResponse();
            } catch (\Throwable $dispatchErr) {
                \Illuminate\Support\Facades\Log::warning('Dispatch WA notice: ' . $dispatchErr->getMessage());
            }

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'ticket_number' => $ticketNumber,
                    'uuid' => $report->uuid ?? null,
                    'report_id' => $report->id ?? null,
                ]);
            }

            return redirect()->route('report.success', ['id' => $ticketNumber]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('ReportController::store Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            // If report record was successfully saved to DB, return success to client instead of 500 error
            if ($report && $report->exists) {
                if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => true,
                        'ticket_number' => $report->ticket_number,
                        'uuid' => $report->uuid,
                        'report_id' => $report->id,
                        'notice' => 'Laporan tersimpan. Sebagian notifikasi sekunder mengalami kendala.',
                    ]);
                }
                return redirect()->route('report.success', ['id' => $report->ticket_number]);
            }

            if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kendala saat menyimpan aduan: ' . $e->getMessage(),
                ], 422);
            }
            throw $e;
        }
    }

    /**
     * Show report success confirmation page.
     */
    public function success(Request $request): Response
    {
        $id = $request->query('id', 'LP-' . date('Y-m') . '-0001');

        return Inertia::render('Report/Success', [
            'id' => $id,
        ]);
    }

    /**
     * Show public report progress tracking page.
     */
    public function track(Request $request): Response
    {
        $ticket = strtoupper(trim($request->query('ticket', '')));
        $reportData = null;
        $searched = false;

        if (!empty($ticket)) {
            $searched = true;
            $query = Report::with(['room', 'attachments']);
            if (\Illuminate\Support\Str::isUuid($ticket)) {
                $query->where('uuid', strtolower($ticket));
            } else {
                $query->where('ticket_number', $ticket);
            }
            $report = $query->first();

            if ($report) {
                $evidenceAttachments = $report->attachments->filter(function ($att) {
                    return $att->category !== 'VERIFICATION';
                });

                $reportData = [
                    'uuid' => $report->uuid,
                    'ticket_number' => $report->ticket_number,
                    'room_name' => $report->room ? $report->room->name : 'Ruangan Umum',
                    'unit_name' => $report->room ? $report->room->name : 'Ruangan Umum',
                    'location_info' => $report->room ? $report->room->location_info : '-',
                    'target_object' => $report->target_object,
                    'isi_laporan' => $report->isi_laporan,
                    'status' => $report->status ?? 'PENDING',
                    'created_at' => $report->created_at ? $report->created_at->format('d M Y, H:i') : null,
                    'verified_at' => $report->verified_at ? $report->verified_at->format('d M Y, H:i') : null,
                    'resolved_at' => ($report->resolved_at ?? $report->verified_at) ? ($report->resolved_at ?? $report->verified_at)->format('d M Y, H:i') : null,
                    'has_attachment' => $evidenceAttachments->isNotEmpty(),
                    'attachments_count' => $evidenceAttachments->count(),
                ];
            }
        }

        return Inertia::render('Report/Track', [
            'initialTicket' => $ticket,
            'report' => $reportData,
            'searched' => $searched,
        ]);
    }

    /**
     * Parse User-Agent into a clean, human-readable device and browser summary for IT audit.
     */
    public static function parseDeviceSummary(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return 'Perangkat Tidak Diketahui';
        }

        // Detect OS
        $os = 'OS Lainnya';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $userAgent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/i', $userAgent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/windows nt/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/android (\d+(\.\d+)?)/i', $userAgent, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/iphone os (\d+(_\d+)?)/i', $userAgent, $m)) {
            $os = 'iOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPadOS';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }

        // Detect Browser
        $browser = 'Browser Lainnya';
        if (preg_match('/edg\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Edge ' . explode('.', $m[1])[0];
        } elseif (preg_match('/opr\/([\d\.]+)|opera/i', $userAgent, $m)) {
            $browser = 'Opera';
        } elseif (preg_match('/crios\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Chrome iOS ' . explode('.', $m[1])[0];
        } elseif (preg_match('/chrome\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Chrome ' . explode('.', $m[1])[0];
        } elseif (preg_match('/version\/([\d\.]+).*safari/i', $userAgent, $m)) {
            $browser = 'Safari ' . explode('.', $m[1])[0];
        } elseif (preg_match('/firefox\/([\d\.]+)/i', $userAgent, $m)) {
            $browser = 'Firefox ' . explode('.', $m[1])[0];
        }

        // Detect Device Type
        $isMobile = preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent);
        $type = $isMobile ? 'Mobile' : 'Desktop';

        return "{$browser} on {$os} ({$type})";
    }
}
