<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    /**
     * Display personal performance dashboard for staff member.
     */
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['unit']);
        $today = Carbon::today();

        // 1. Status presensi hari ini
        $activeAttendance = StaffAttendance::where('user_id', $user->id)
            ->where('status', 'ON_DUTY')
            ->latest('check_in_at')
            ->first()
            ?: StaffAttendance::where('user_id', $user->id)
                ->whereDate('duty_date', $today)
                ->latest('check_in_at')
                ->first();

        // 2. Riwayat logbook KPI
        $kpiLogs = $user->kpiLogs()
            ->with(['report', 'verifier'])
            ->latest('logged_at')
            ->take(15)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action_type' => $log->action_type,
                    'points' => $log->points,
                    'note' => $log->note,
                    'ticket_number' => $log->report ? $log->report->ticket_number : 'EVALUASI_MANUAL',
                    'verifier_name' => $log->verifier ? $log->verifier->name : 'Supervisor Kasi',
                    'date' => $log->logged_at ? $log->logged_at->format('d M Y, H:i') : '-',
                ];
            });

        // 3. Riwayat laporan pasien yang berkaitan langsung dengan staf
        $relatedReports = $user->reports()
            ->with(['unit', 'verifier'])
            ->latest('verified_at')
            ->take(10)
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->ticket_number,
                    'isi_laporan' => $r->isi_laporan,
                    'ai_sentiment' => $r->ai_sentiment,
                    'action_type' => $r->pivot->action_type ?? 'NETRAL',
                    'points' => $r->pivot->points ?? 0,
                    'supervisor_notes' => $r->supervisor_notes,
                    'verified_at' => $r->verified_at ? $r->verified_at->format('d M Y, H:i') : '-',
                ];
            });

        // 4. Riwayat 7 presensi terakhir
        $recentAttendances = StaffAttendance::where('user_id', $user->id)
            ->orderByDesc('duty_date')
            ->take(7)
            ->get()
            ->map(function ($att) {
                return [
                    'id' => $att->id,
                    'duty_date' => $att->duty_date ? $att->duty_date->format('d M Y') : '-',
                    'shift_name' => $att->shift_name,
                    'check_in_at' => $att->check_in_at ? $att->check_in_at->format('H:i') : '-',
                    'check_out_at' => $att->check_out_at ? $att->check_out_at->format('H:i') : 'Belum Pulang',
                    'status' => $att->status,
                ];
            });

        return Inertia::render('Staff/Dashboard', [
            'staff' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip ?? '-',
                'unit_name' => $user->unit ? $user->unit->name : 'Unit Umum Pelayanan',
                'role' => $user->role ?? 'Staf Pelayanan',
                'profile_photo_path' => $user->profile_photo_path,
                'total_points' => (int) $user->total_points,
                'praise_count' => (int) $user->praise_count,
                'complaint_count' => (int) $user->complaint_count,
                'is_on_duty' => (bool) $user->is_on_duty,
            ],
            'attendanceToday' => $activeAttendance ? [
                'id' => $activeAttendance->id,
                'shift_name' => $activeAttendance->shift_name,
                'check_in_at' => $activeAttendance->check_in_at ? $activeAttendance->check_in_at->format('H:i') : null,
                'check_out_at' => $activeAttendance->check_out_at ? $activeAttendance->check_out_at->format('H:i') : null,
                'status' => $activeAttendance->status,
            ] : null,
            'kpiLogs' => $kpiLogs,
            'relatedReports' => $relatedReports,
            'recentAttendances' => $recentAttendances,
        ]);
    }
}
