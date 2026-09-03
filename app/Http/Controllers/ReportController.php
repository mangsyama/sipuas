<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Unit;
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
        $units = Unit::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category']);

        return Inertia::render('Report/Create', [
            'unitId' => $request->query('unit', ''),
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created report from patient.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => 'required',
            'target_object' => 'nullable|string|max:255',
            'isi_laporan' => 'required|string|min:5|max:3000',
            'reporter_name' => 'nullable|string|max:150',
            'reporter_phone' => 'nullable|string|max:30',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm|max:10240', // Max 10MB
        ]);

        // Find unit either by id or code
        $unit = Unit::where('id', $validated['unit_id'])
            ->orWhere('code', $validated['unit_id'])
            ->first();

        if (!$unit) {
            $unit = Unit::first();
        }

        // Run AI Analysis with safety fallback
        try {
            $aiAnalysis = $this->aiService->analyzeReport(
                $validated['isi_laporan'],
                $unit ? $unit->name : 'Umum',
                $validated['target_object'] ?? null
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('AI Analysis Warning: ' . $e->getMessage());
            $aiAnalysis = $this->aiService->fallbackHeuristicAnalysis(
                $validated['isi_laporan'],
                $unit ? $unit->name : 'Umum',
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
            'unit_id' => $unit->id,
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

        // Kirim Notifikasi WhatsApp Siaga ke Kasi Unit Terkait (Non-blocking)
        try {
            $kasiUsers = \App\Models\User::where('unit_id', $unit->id)
                ->where('role', 'KASI')
                ->where('is_active', true)
                ->whereNotNull('phone_number')
                ->get();

            if ($kasiUsers->isNotEmpty()) {
                $waUrl = config('services.wa_gateway.local_url', 'http://127.0.0.1:3000/send');
                $secretKey = config('services.wa_gateway.secret_key');

                $waMessage = "🔔 *NOTIFIKASI SIAGA SIPUAS*\n"
                    . "Ada laporan pelayanan baru di unit kerja Anda:\n\n"
                    . "📋 *No. Tiket:* {$ticketNumber}\n"
                    . "🏥 *Unit:* " . ($unit->name ?? 'Umum') . "\n"
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
}
