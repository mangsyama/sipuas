<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Room;
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
            ->orderBy('building_name')
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
        $selectedRoomId = $request->query('room', $request->query('unit', ''));

        return Inertia::render('Report/Create', [
            'roomId' => $selectedRoomId,
            'unitId' => $selectedRoomId, // Backward compatibility
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
        $validated = $request->validate([
            'room_id' => 'nullable',
            'unit_id' => 'nullable',
            'target_object' => 'nullable|string|max:255',
            'isi_laporan' => 'required|string|min:5|max:3000',
            'reporter_name' => 'nullable|string|max:150',
            'reporter_phone' => 'nullable|string|max:30',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm|max:10240', // Max 10MB
        ]);

        $roomId = $validated['room_id'] ?? $validated['unit_id'] ?? null;

        // Find room either by id or name
        $room = Room::where('id', $roomId)
            ->orWhere('name', $roomId)
            ->first();

        if (!$room) {
            $room = Room::first();
        }

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

        $report = Report::create([
            'ticket_number' => $ticketNumber,
            'room_id' => $room->id,
            'target_object' => $validated['target_object'] ?? null,
            'isi_laporan' => $validated['isi_laporan'],
            'ai_sentiment' => $sentiment,
            'ai_category' => $category,
            'ai_score' => $score,
            'ai_confidence' => $confidence,
            'ai_metadata' => $aiAnalysis,
            'shift_info' => $shiftInfo,
            'reporter_name' => $validated['reporter_name'] ?? 'Anonim',
            'reporter_phone' => $validated['reporter_phone'] ?? null,
            'is_anonymous' => empty($validated['reporter_name']) || strtolower($validated['reporter_name']) === 'anonim',
            'status' => 'PENDING',
            'priority' => ($sentiment === 'NEGATIF' || ($aiAnalysis['urgency'] ?? '') === 'TINGGI' || ($aiAnalysis['urgency'] ?? '') === 'KRITIS') ? 'HIGH' : 'NORMAL',
        ]);

        // Process file attachment if present
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
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
        }

        // Kirim Notifikasi WhatsApp Siaga ke Kasi Terkait (Non-blocking)
        try {
            $kasiUsers = \App\Models\User::where(function ($q) use ($room) {
                    $q->where('room_id', $room->id)
                      ->orWhere('unit_id', $room->id);
                })
                ->where('role', 'KASI')
                ->where('is_active', true)
                ->whereNotNull('phone_number')
                ->get();

            if ($kasiUsers->isNotEmpty()) {
                $waUrl = config('services.wa_gateway.local_url', 'http://127.0.0.1:3000/send');
                $secretKey = config('services.wa_gateway.secret_key');

                $waMessage = "🔔 *NOTIFIKASI SIAGA SIPUAS*\n"
                    . "Ada laporan pelayanan baru di ruangan Anda:\n\n"
                    . "📋 *No. Tiket:* {$ticketNumber}\n"
                    . "🏥 *Ruangan:* " . ($room->name ?? 'Umum') . " (" . ($room->location_info ?? '-') . ")\n"
                    . "👤 *Sasaran/Staf:* " . ($validated['target_object'] ?? '-') . "\n"
                    . "🕒 *Shift:* {$shiftInfo}\n"
                    . "📊 *Sentimen AI:* {$sentiment}\n"
                    . "📝 *Uraian Singkat:* " . mb_substr($validated['isi_laporan'], 0, 100) . "...\n\n"
                    . "Mohon segera buka menu *Feed Aduan Masuk Unit* untuk melakukan verifikasi staf dinas.\n"
                    . "🔗 " . url('/kasi/dashboard');

                foreach ($kasiUsers as $kasi) {
                    \Illuminate\Support\Facades\Http::timeout(3)->withoutVerifying()
                        ->withHeaders(!empty($secretKey) ? ['X-Api-Key' => $secretKey] : [])
                        ->post($waUrl, [
                            'target' => $kasi->phone_number,
                            'message' => $waMessage,
                        ]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('WA Gateway notification notice: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'ticket_number' => $ticketNumber,
                'uuid' => $report->uuid,
                'report_id' => $report->id,
            ]);
        }

        return redirect()->route('report.success', ['id' => $ticketNumber]);
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
            $report = Report::with(['room', 'attachments'])
                ->where('ticket_number', $ticket)
                ->orWhere('uuid', $ticket)
                ->first();

            if ($report) {
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
                    'resolved_at' => $report->resolved_at ? $report->resolved_at->format('d M Y, H:i') : null,
                    'supervisor_notes' => $report->supervisor_notes,
                    'resolution_notes' => $report->resolution_notes,
                    'has_attachment' => $report->attachments->isNotEmpty(),
                    'attachments_count' => $report->attachments->count(),
                ];
            }
        }

        return Inertia::render('Report/Track', [
            'initialTicket' => $ticket,
            'report' => $reportData,
            'searched' => $searched,
        ]);
    }
}
