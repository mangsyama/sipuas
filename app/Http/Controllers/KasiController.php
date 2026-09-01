<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportStaff;
use App\Models\Staff;
use App\Models\StaffKpiLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class KasiController extends Controller
{
    /**
     * Display Kasi Feed of Reports for their unit.
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        
        $query = Report::with(['unit', 'verifier']);
        if ($user && $user->unit_id && !$user->isSuperAdmin()) {
            $query->where('unit_id', $user->unit_id);
        }

        $reports = $query->latest()->get()->map(function ($r) {
            return [
                'id' => $r->ticket_number,
                'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : '-',
                'unit' => $r->unit ? $r->unit->name : 'Unit Umum',
                'isi_laporan' => $r->isi_laporan,
                'ai_sentiment' => $r->ai_sentiment,
                'ai_category' => $r->ai_category ?? 'Pelayanan',
                'ai_score' => $r->ai_score,
                'status' => $r->status,
                'verified_by' => $r->verifier ? $r->verifier->name : null,
            ];
        });

        return Inertia::render('Kasi/Dashboard', [
            'initialReports' => $reports,
        ]);
    }

    /**
     * Show verification page for a specific report ticket.
     */
    public function verify(Request $request, $id = null): Response
    {
        $report = null;
        if ($id) {
            $report = Report::with(['unit', 'attachments', 'staff', 'verifier'])
                ->where('ticket_number', $id)
                ->orWhere('id', $id)
                ->first();
        }

        if (!$report) {
            $report = Report::with(['unit', 'attachments', 'staff', 'verifier'])->latest()->first();
        }

        $unitId = $report ? $report->unit_id : null;
        $staffList = Staff::where('unit_id', $unitId)
            ->where('is_active', true)
            ->get()
            ->map(function ($s) use ($report) {
                $isLinked = $report && $report->staff->contains('id', $s->id);
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nip' => $s->nip ?? '-',
                    'role' => $s->role,
                    'total_points' => $s->total_points,
                    'selected' => $isLinked,
                ];
            });

        // Format attachments
        $attachments = $report ? $report->attachments->map(function ($att) {
            return [
                'id' => $att->id,
                'file_name' => $att->file_name,
                'file_type' => $att->file_type,
                'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                'file_size' => $att->file_size_bytes ? round($att->file_size_bytes / 1024, 1) . ' KB' : '-',
            ];
        }) : [];

        // Parse AI metadata
        $aiMeta = [];
        if ($report && $report->ai_metadata) {
            $aiMeta = is_array($report->ai_metadata) ? $report->ai_metadata : json_decode($report->ai_metadata, true);
        }

        $reportDetail = $report ? [
            'id' => $report->ticket_number,
            'timestamp' => $report->created_at ? $report->created_at->format('d M Y, H:i') . ' WITA' : '-',
            'unit' => $report->unit ? $report->unit->name : 'Unit Umum',
            'unit_code' => $report->unit ? $report->unit->code : '',
            'target_object' => $report->target_object,
            'isi_laporan' => $report->isi_laporan,
            'reporter_name' => $report->reporter_name ?? 'Anonim',
            'reporter_phone' => $report->reporter_phone,
            'is_anonymous' => (bool) $report->is_anonymous,
            'ai_sentiment' => $report->ai_sentiment,
            'ai_category' => $report->ai_category ?? ($aiMeta['category'] ?? 'Pelayanan Umum'),
            'ai_score' => $report->ai_score ?? ($aiMeta['score'] ?? 0),
            'ai_confidence' => $report->ai_confidence ?? ($aiMeta['confidence'] ?? '95%'),
            'ai_summary' => $aiMeta['summary'] ?? ($aiMeta['explanation'] ?? null),
            'ai_urgency' => $aiMeta['urgency'] ?? ($report->priority === 'HIGH' ? 'TINGGI' : 'NORMAL'),
            'ai_recommendation' => $aiMeta['recommendation'] ?? ($report->ai_sentiment === 'POSITIF' ? 'Berikan apresiasi poin reward pada shift kerja ini.' : 'Tindaklanjuti dengan pembinaan poin KPI untuk peningkatan mutu pelayanan.'),
            'ai_provider' => $aiMeta['provider'] ?? 'Groq AI (Llama-3 / GPT-OSS)',
            'shift_info' => $report->shift_info ?? 'Shift Pagi / Siang',
            'status' => $report->status,
            'priority' => $report->priority,
            'supervisor_notes' => $report->supervisor_notes,
            'verified_at' => $report->verified_at ? $report->verified_at->format('d M Y, H:i') : null,
            'verified_by' => $report->verifier ? $report->verifier->name : null,
            'attachments' => $attachments,
        ] : null;

        return Inertia::render('Kasi/Verify', [
            'id' => $report ? $report->ticket_number : ($id ?? ''),
            'reportDetail' => $reportDetail,
            'staffMembers' => $staffList,
        ]);
    }

    /**
     * Process verification and update KPI logbook.
     */
    public function processVerification(Request $request, $id)
    {
        $validated = $request->validate([
            'selected_staff_ids' => 'required|array|min:1',
            'action_type' => 'required|string|in:PENAMBAHAN,PEMOTONGAN,NETRAL',
            'points' => 'required|integer',
            'supervisor_notes' => 'nullable|string',
        ]);

        $report = Report::where('ticket_number', $id)->orWhere('id', $id)->firstOrFail();

        $points = (int) $validated['points'];
        if ($validated['action_type'] === 'PEMOTONGAN' && $points > 0) {
            $points = -$points;
        }

        // Update Report
        $report->update([
            'status' => 'VERIFIED',
            'verified_by' => $request->user() ? $request->user()->id : null,
            'verified_at' => Carbon::now(),
            'supervisor_notes' => $validated['supervisor_notes'],
        ]);

        // Sync report staff & update staff KPI logs
        foreach ($validated['selected_staff_ids'] as $staffId) {
            $staff = Staff::find($staffId);
            if (!$staff) continue;

            ReportStaff::updateOrCreate(
                ['report_id' => $report->id, 'staff_id' => $staff->id],
                ['action_type' => $validated['action_type'], 'points' => $points]
            );

            // Update Staff Balance Points
            if ($validated['action_type'] === 'PENAMBAHAN') {
                $staff->increment('praise_count');
                $staff->increment('total_points', abs($points));
            } elseif ($validated['action_type'] === 'PEMOTONGAN') {
                $staff->increment('complaint_count');
                $staff->decrement('total_points', abs($points));
            }
            $staff->update(['last_point_update_at' => Carbon::now()]);

            // Add KPI Log
            StaffKpiLog::create([
                'staff_id' => $staff->id,
                'report_id' => $report->id,
                'verified_by' => $request->user() ? $request->user()->id : null,
                'action_type' => $validated['action_type'],
                'points' => $points,
                'note' => $validated['supervisor_notes'] ?? 'Verifikasi laporan aduan/apresiasi unit.',
                'logged_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('kasi.dashboard');
    }

    /**
     * Display Logbook KPI history of staff in unit.
     */
    public function logbook(Request $request): Response
    {
        $user = $request->user();

        $query = Staff::with(['unit', 'kpiLogs.report', 'kpiLogs.verifier'])->where('is_active', true);
        if ($user && $user->unit_id && !$user->isSuperAdmin()) {
            $query->where('unit_id', $user->unit_id);
        }

        $staffLogbooks = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'nip' => $s->nip ?? '-',
                'role' => $s->role,
                'total_points' => $s->total_points,
                'praise_count' => $s->praise_count,
                'complaint_count' => $s->complaint_count,
                'last_update' => $s->last_point_update_at ? $s->last_point_update_at->format('Y-m-d H:i') : '-',
                'history' => $s->kpiLogs->map(function ($log) {
                    return [
                        'id' => $log->report ? $log->report->ticket_number : 'MANUAL',
                        'type' => $log->action_type,
                        'points' => $log->points,
                        'note' => $log->note,
                        'date' => $log->logged_at ? $log->logged_at->format('Y-m-d') : '-',
                    ];
                }),
            ];
        });

        return Inertia::render('Kasi/Logbook', [
            'staffLogbooks' => $staffLogbooks,
        ]);
    }
}
