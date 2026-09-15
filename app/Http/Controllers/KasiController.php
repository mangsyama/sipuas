<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportStaff;
use App\Models\Role;
use App\Models\StaffAttendance;
use App\Models\StaffKpiLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Room;
use Carbon\CarbonPeriod;

class KasiController extends Controller
{
    /**
     * Display dedicated Kasi Unit Management Dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $userRoomId = $user ? ($user->room_id ?? $user->unit_id) : null;
        $isElevated = $user && ($user->isSuperAdmin() || $user->isDirektur() || $user->isKabid());

        // Selected Room Filter
        $roomId = $request->input('room_id');
        if (!$isElevated || empty($roomId)) {
            $effectiveRoomId = $userRoomId;
        } else {
            $effectiveRoomId = $roomId;
        }

        // Parse Period Filter
        $period = $request->input('period', 'all');
        $now = Carbon::now();
        $startDate = null;
        $endDate = null;
        $periodLabel = 'Semua Periode';

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hari Ini';
                break;
            case '7d':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '7 Hari Terakhir';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodLabel = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                break;
            case '30d':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '30 Hari Terakhir';
                break;
            case 'all':
            default:
                $period = 'all';
                $startDate = null;
                $endDate = null;
                $periodLabel = 'Semua Periode';
                break;
        }

        // Base Query
        $baseQuery = Report::query();
        if ($effectiveRoomId) {
            $baseQuery->where('room_id', $effectiveRoomId);
        }
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Unit Stats Calculation
        $totalUnitReports = (clone $baseQuery)->count();
        $pendingCount = (clone $baseQuery)->where('status', 'PENDING')->count();
        $verifiedCount = (clone $baseQuery)->whereIn('status', ['VERIFIED', 'RESOLVED'])->count();
        $positiveCount = (clone $baseQuery)->where('ai_sentiment', 'POSITIF')->count();
        $negativeCount = (clone $baseQuery)->where('ai_sentiment', 'NEGATIF')->count();
        $neutralCount = (clone $baseQuery)->where('ai_sentiment', 'NETRAL')->count();

        // Real Average Response Duration Calculation for this unit
        $verifiedReports = (clone $baseQuery)
            ->whereIn('status', ['VERIFIED', 'RESOLVED'])
            ->whereNotNull('verified_at')
            ->get(['created_at', 'verified_at']);

        if ($verifiedReports->count() > 0) {
            $totalMinutes = $verifiedReports->reduce(function ($carry, $r) {
                $diff = max(0, Carbon::parse($r->created_at)->diffInMinutes(Carbon::parse($r->verified_at)));
                return $carry + $diff;
            }, 0);

            $avgMins = round($totalMinutes / $verifiedReports->count());
            $avgFormatted = $avgMins < 60 ? $avgMins . ' Menit' : round($avgMins / 60, 1) . ' Jam';
        } else {
            $avgFormatted = $verifiedCount > 0 ? '< 1 Jam' : '-';
        }

        $satisfactionIndex = $totalUnitReports > 0 ? round(($positiveCount / $totalUnitReports) * 100, 1) . '%' : '100%';

        // Unit Trend Data for Chart (last 7 days or selected period)
        $trendPeriodStart = $startDate ?: $now->copy()->subDays(6)->startOfDay();
        $trendPeriodEnd = $endDate ?: $now->copy()->endOfDay();
        $trendLabels = [];
        $trendIncoming = [];
        $trendVerified = [];

        $periodRange = CarbonPeriod::create($trendPeriodStart, '1 day', $trendPeriodEnd);
        foreach ($periodRange as $d) {
            $trendLabels[] = $d->translatedFormat('d M');
            $inQ = Report::whereBetween('created_at', [$d->copy()->startOfDay(), $d->copy()->endOfDay()]);
            $verQ = Report::whereBetween('verified_at', [$d->copy()->startOfDay(), $d->copy()->endOfDay()])
                ->whereIn('status', ['VERIFIED', 'RESOLVED']);
            if ($effectiveRoomId) {
                $inQ->where('room_id', $effectiveRoomId);
                $verQ->where('room_id', $effectiveRoomId);
            }
            $trendIncoming[] = $inQ->count();
            $trendVerified[] = $verQ->count();
        }

        // Top Unit Categories
        $topCategories = (clone $baseQuery)
            ->whereNotNull('ai_category')
            ->where('ai_category', '!=', '')
            ->selectRaw('ai_category, count(*) as count,
                SUM(CASE WHEN ai_sentiment = "POSITIF" THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN ai_sentiment = "NEGATIF" THEN 1 ELSE 0 END) as negative_count,
                SUM(CASE WHEN ai_sentiment = "NETRAL" THEN 1 ELSE 0 END) as neutral_count')
            ->groupBy('ai_category')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($totalUnitReports) {
                $percentage = $totalUnitReports > 0 ? round(($item->count / $totalUnitReports) * 100, 1) : 0;
                return [
                    'name' => $item->ai_category,
                    'count' => (int) $item->count,
                    'percentage' => $percentage,
                    'positive' => (int) $item->positive_count,
                    'negative' => (int) $item->negative_count,
                    'neutral' => (int) $item->neutral_count,
                ];
            });

        // Recent 5 Reports for preview in Dashboard
        $recentReports = (clone $baseQuery)->with(['room', 'verifier'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->ticket_number,
                'created_at_human' => $r->created_at ? $r->created_at->diffForHumans() : '-',
                'created_at_formatted' => $r->created_at ? $r->created_at->translatedFormat('d M, H:i') : '-',
                'reporter_name' => $r->is_anonymous ? 'Pasien Anonim' : ($r->reporter_name ?: 'Pasien / Keluarga'),
                'target_object' => $r->target_object ?: 'Pelayanan Ruangan',
                'isi_laporan' => $r->isi_laporan,
                'ai_sentiment' => $r->ai_sentiment,
                'ai_category' => $r->ai_category ?? 'Pelayanan Umum',
                'status' => $r->status,
                'unit' => $r->room ? $r->room->name : 'Ruangan Pelayanan',
            ]);

        // Current Unit Info
        $currentRoom = $effectiveRoomId ? Room::find($effectiveRoomId) : null;
        $rooms = $isElevated ? Room::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get() : [];

        // Category Chart Data for horizontal bar chart
        $catLabels = $topCategories->pluck('name')->toArray();
        $catPositive = $topCategories->pluck('positive')->toArray();
        $catNegative = $topCategories->pluck('negative')->toArray();
        $catNeutral = $topCategories->pluck('neutral')->toArray();

        return Inertia::render('Kasi/Dashboard', [
            'unitStats' => [
                'total' => $totalUnitReports,
                'pending' => $pendingCount,
                'verified' => $verifiedCount,
                'positive' => $positiveCount,
                'negative' => $negativeCount,
                'neutral' => $neutralCount,
                'satisfaction_index' => $satisfactionIndex,
                'avg_response_hours' => $avgFormatted,
                'unit_name' => $currentRoom ? $currentRoom->name : 'Seluruh Ruangan RS',
            ],
            'unitTrend' => [
                'labels' => $trendLabels,
                'incoming' => $trendIncoming,
                'verified' => $trendVerified,
            ],
            'categoryChart' => [
                'labels' => $catLabels,
                'positive' => $catPositive,
                'negative' => $catNegative,
                'neutral' => $catNeutral,
            ],
            'topCategories' => $topCategories,
            'recentReports' => $recentReports,
            'rooms' => $rooms,
            'filters' => [
                'period' => $period,
                'period_label' => $periodLabel,
                'room_id' => $effectiveRoomId,
            ],
        ]);
    }

    /**
     * Display dedicated Aduan & Verifikasi Queue / Feed.
     */
    public function feed(Request $request): Response
    {
        $user = $request->user();
        $userRoomId = $user ? ($user->room_id ?? $user->unit_id) : null;
        $isElevated = $user && ($user->isSuperAdmin() || $user->isDirektur() || $user->isKabid());

        // Selected Room Filter
        $roomId = $request->input('room_id');
        if (!$isElevated || empty($roomId)) {
            $effectiveRoomId = $userRoomId;
        } else {
            $effectiveRoomId = $roomId;
        }

        $query = Report::with(['room', 'unit', 'verifier']);
        if ($effectiveRoomId) {
            $query->where('room_id', $effectiveRoomId);
        }

        $reports = $query->latest('created_at')->get()->map(function ($r) {
            return [
                'id' => $r->ticket_number,
                'created_at' => $r->created_at ? $r->created_at->format('d M Y, H:i') : '-',
                'created_at_full' => $r->created_at ? $r->created_at->translatedFormat('d M Y, H:i') . ' WITA' : '-',
                'created_at_human' => $r->created_at ? $r->created_at->diffForHumans() : '-',
                'created_at_time' => $r->created_at ? $r->created_at->format('H:i') . ' WITA' : '-',
                'created_at_date' => $r->created_at ? $r->created_at->translatedFormat('d F Y') : '-',
                'unit' => $r->room ? $r->room->name : ($r->unit ? $r->unit->name : 'Unit Umum'),
                'room_id' => $r->room_id,
                'target_object' => $r->target_object,
                'shift_info' => $r->created_at ? $r->created_at->format('H:i') . ' WITA' : 'Waktu Aduan',
                'reporter_name' => $r->is_anonymous ? 'Anonim' : ($r->reporter_name ?: 'Anonim'),
                'reporter_phone' => $r->is_anonymous ? null : $r->reporter_phone,
                'isi_laporan' => $r->isi_laporan,
                'ai_sentiment' => $r->ai_sentiment,
                'ai_category' => $r->ai_category ?? 'Pelayanan',
                'ai_score' => $r->ai_score,
                'status' => $r->status,
                'verified_by' => $r->verifier ? $r->verifier->name : null,
            ];
        });

        $totalReports = $reports->count();
        $pendingReports = $reports->where('status', 'PENDING')->count();
        $verifiedReports = $reports->whereIn('status', ['VERIFIED', 'RESOLVED'])->count();

        $currentRoom = $effectiveRoomId ? Room::find($effectiveRoomId) : null;
        $rooms = $isElevated ? Room::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get() : [];

        return Inertia::render('Kasi/Feed', [
            'initialReports' => $reports->values()->all(),
            'stats' => [
                'total' => $totalReports,
                'pending' => $pendingReports,
                'verified' => $verifiedReports,
                'unit_name' => $currentRoom ? $currentRoom->name : 'Seluruh Ruangan RS',
            ],
            'rooms' => $rooms,
            'filters' => [
                'room_id' => $effectiveRoomId,
            ],
        ]);
    }

    /**
     * Show verification page for a specific report ticket.
     */
    public function verify(Request $request, $id = null): Response
    {
        $report = null;
        if ($id) {
            $report = Report::with(['room', 'unit', 'attachments', 'staff', 'verifier'])
                ->where('ticket_number', $id)
                ->orWhere('id', $id)
                ->first();
        }

        if (!$report) {
            $report = Report::with(['room', 'unit', 'attachments', 'staff', 'verifier'])->latest()->first();
        }

        $roomId = $report ? ($report->room_id ?? $report->unit_id) : null;
        $reportTime = $report ? $report->created_at : null;
        $reportDate = $reportTime ? $reportTime->toDateString() : null;

        // Ambil riwayat presensi staf di ruangan ini pada tanggal laporan masuk
        $attendancesOnDate = ($reportDate && $roomId) 
            ? StaffAttendance::where('room_id', $roomId)
                ->whereDate('duty_date', $reportDate)
                ->get()
                ->groupBy('user_id') 
            : collect();

        $staffList = User::where('room_id', $roomId)
            ->where('role_id', Role::STAFF)
            ->where('is_active', true)
            ->get()
            ->map(function ($s) use ($report, $reportTime, $attendancesOnDate) {
                $isLinked = $report && $report->staff->contains('id', $s->id);
                $isPending = $report && $report->status === 'PENDING';

                // Evaluasi kehadiran presensi nyata (Clock-In)
                $userAtts = $attendancesOnDate->get($s->id, collect());
                $activeAtTime = false;
                $attendanceType = 'NONE';
                $attendanceLabel = 'Belum Presensi';
                $clockInTime = null;

                foreach ($userAtts as $att) {
                    $in = $att->check_in_at ? Carbon::parse($att->check_in_at) : null;
                    $out = $att->check_out_at ? Carbon::parse($att->check_out_at) : null;

                    if ($in && $reportTime) {
                        // Jika laporan masuk saat staf sedang dinas (setelah check-in dan belum checkout / checkout setelah jam aduan)
                        if ($reportTime->gte($in) && (!$out || $reportTime->lte($out) || $att->status === 'ON_DUTY')) {
                            $activeAtTime = true;
                            $attendanceType = 'ACTIVE_AT_REPORT';
                            $attendanceLabel = 'Berdinas saat aduan (' . $in->format('H.i') . ')';
                            $clockInTime = $in->format('H.i');
                            break;
                        }
                    }

                    if ($in) {
                        $attendanceType = 'TODAY';
                        $attendanceLabel = 'Hadir Hari Ini (' . $in->format('H.i') . ')';
                        $clockInTime = $in->format('H.i');
                    }
                }

                if (!$activeAtTime && $s->is_on_duty) {
                    $attendanceLabel = 'Sedang On-Duty';
                }

                // Otomatis centang staf yang tercatat berdinas saat kejadian jika aduan belum diverifikasi
                $autoSelected = $isPending && $activeAtTime;

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nip' => $s->nip ?? '-',
                    'role' => $s->role ?? 'Staf Pelayanan',
                    'total_points' => $s->total_points,
                    'is_on_duty' => (bool)$s->is_on_duty,
                    'active_at_time' => $activeAtTime,
                    'attendance_type' => $attendanceType,
                    'attendance_label' => $attendanceLabel,
                    'clock_in_time' => $clockInTime,
                    'selected' => $isLinked || $autoSelected,
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

        $verifiedActionType = null;
        $verifiedPoints = null;
        if ($report && $report->status === 'VERIFIED') {
            $firstStaff = $report->staff->first();
            if ($firstStaff && $firstStaff->pivot) {
                $verifiedActionType = $firstStaff->pivot->action_type;
                $verifiedPoints = abs($firstStaff->pivot->points);
            } else {
                $firstKpi = StaffKpiLog::where('report_id', $report->id)->first();
                if ($firstKpi) {
                    $verifiedActionType = $firstKpi->action_type;
                    $verifiedPoints = abs($firstKpi->points);
                }
            }
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
            'ai_recommendation' => $aiMeta['action_recommendation'] ?? ($aiMeta['recommendation'] ?? ($report->ai_sentiment === 'POSITIF' 
                ? 'Apresiasi Pelayanan Prima: Pelayanan dinilai sangat memuaskan oleh masyarakat. Direkomendasikan kepada Kepala Ruangan/Kasi untuk memberikan pengakuan formal dan mengalokasikan penambahan poin reward (+ Poin KPI) kepada staf bertugas guna menjaga standar keunggulan kerja.' 
                : ($report->ai_sentiment === 'NEGATIF' 
                    ? 'Tindak Lanjut Keluhan: Laporan menunjukkan adanya ketidakpuasan pelayanan. Disarankan Kepala Ruangan/Kasi segera mengklarifikasi kronologi kejadian bersama staf yang bertugas saat kejadian, meninjau kesesuaian SOP, dan menerapkan penyesuaian poin pembinaan (- Poin KPI) jika terbukti ada kelalaian petugas.' 
                    : 'Laporan Masukan Umum: Informasi ini bersifat saran atau terkait sarana/kondisi fisik lingkungan rumah sakit tanpa keterlibatan langsung pelanggaran individu staf. Sesuai regulasi, status ini adalah TINDAKAN NETRAL (0 Poin KPI) dan TIDAK mengubah saldo kinerja staf unit.'))),
            'ai_provider' => ($aiMeta['engine'] ?? ($aiMeta['provider'] ?? '')) === 'HEURISTIC_RULE_BASED' 
                ? 'AI Heuristik Internal' 
                : (($aiMeta['engine'] ?? ($aiMeta['provider'] ?? '')) === 'GROQ_AI' ? 'Groq AI (Llama-3)' : 'Sistem AI SIPUAS'),
            'created_at_time' => $report->created_at ? $report->created_at->format('H:i') . ' WITA' : '-',
            'created_at_human' => $report->created_at ? $report->created_at->diffForHumans() : '-',
            'shift_info' => $report->created_at ? $report->created_at->format('H:i') . ' WITA' : 'Waktu Aduan',
            'status' => $report->status,
            'priority' => $report->priority,
            'supervisor_notes' => $report->supervisor_notes,
            'verified_at' => $report->verified_at ? $report->verified_at->format('d M Y, H:i') : null,
            'verified_by' => $report->verifier ? $report->verifier->name : null,
            'verified_action_type' => $verifiedActionType,
            'verified_points' => $verifiedPoints,
            'pesupeluh_ticket_id' => $report->pesupeluh_ticket_id,
            'pesupeluh_ticket_number' => $report->pesupeluh_ticket_number,
            'dispatched_to_pesupeluh_at' => $report->dispatched_to_pesupeluh_at ? $report->dispatched_to_pesupeluh_at->format('d M Y, H:i') . ' WITA' : null,
            'attachments' => $attachments,
        ] : null;

        $pesupeluhService = app(\App\Services\PesupeluhService::class);
        $pesupeluhCategories = $pesupeluhService->getCategories();
        $pesupeluhRooms = $pesupeluhService->getRooms();

        return Inertia::render('Kasi/Verify', [
            'id' => $report ? $report->ticket_number : ($id ?? ''),
            'reportDetail' => $reportDetail,
            'staffMembers' => $staffList,
            'pesupeluhCategories' => $pesupeluhCategories,
            'pesupeluhRooms' => $pesupeluhRooms,
        ]);
    }

    /**
     * Process verification and update KPI logbook.
     */
    public function processVerification(Request $request, $id)
    {
        $report = Report::where('ticket_number', $id)->orWhere('id', $id)->firstOrFail();

        if ($report->status === 'VERIFIED') {
            return redirect()->route('kasi.feed');
        }

        $isNeutral = $request->input('action_type') === 'NETRAL';

        $validated = $request->validate([
            'selected_staff_ids' => $isNeutral ? 'nullable|array' : 'required|array|min:1',
            'action_type' => 'required|string|in:PENAMBAHAN,PEMOTONGAN,NETRAL',
            'points' => 'required|integer',
            'supervisor_notes' => 'nullable|string',
            'forward_to_pesupeluh' => 'nullable|boolean',
            'pesupeluh_category_id' => 'nullable|integer',
            'pesupeluh_room_id' => 'nullable|integer',
            'pesupeluh_priority' => 'nullable|string|in:ROUTINE,URGENT',
        ]);

        $report = Report::where('ticket_number', $id)->orWhere('id', $id)->firstOrFail();

        $points = (int) $validated['points'];
        if ($validated['action_type'] === 'NETRAL') {
            $points = 0;
        } elseif ($validated['action_type'] === 'PEMOTONGAN' && $points > 0) {
            $points = -$points;
        }

        // Update Report
        $report->update([
            'status' => 'VERIFIED',
            'verified_by' => $request->user() ? $request->user()->id : null,
            'verified_at' => Carbon::now(),
            'supervisor_notes' => $validated['supervisor_notes'],
        ]);

        // Disposisi ke Sistem Penunjang (PESU PELUH) jika diaktifkan dan tindakan NETRAL
        $pesupeluhTicketNumber = null;
        if ($validated['action_type'] === 'NETRAL' && !empty($validated['forward_to_pesupeluh']) && !empty($validated['pesupeluh_category_id'])) {
            $pesupeluhService = app(\App\Services\PesupeluhService::class);
            $dispatchResult = $pesupeluhService->dispatchReport($report, [
                'category_id' => $validated['pesupeluh_category_id'],
                'pesupeluh_room_id' => $validated['pesupeluh_room_id'] ?? null,
                'priority' => $validated['pesupeluh_priority'] ?? 'ROUTINE',
                'supervisor_notes' => $validated['supervisor_notes'],
            ], $request->user());

            if (!empty($dispatchResult['ticket_number'])) {
                $pesupeluhTicketNumber = $dispatchResult['ticket_number'];
            }
        }

        // Sync report staff & update staff KPI logs directly on User model
        $selectedStaffIds = $validated['selected_staff_ids'] ?? [];
        foreach ($selectedStaffIds as $staffId) {
            $staff = User::find($staffId);
            if (!$staff) continue;

            ReportStaff::updateOrCreate(
                ['report_id' => $report->id, 'user_id' => $staff->id],
                ['action_type' => $validated['action_type'], 'points' => $points]
            );

            // Update Staff Balance Points only for PENAMBAHAN / PEMOTONGAN
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
                'user_id' => $staff->id,
                'report_id' => $report->id,
                'verified_by' => $request->user() ? $request->user()->id : null,
                'action_type' => $validated['action_type'],
                'points' => $points,
                'note' => $validated['supervisor_notes'] ?? 'Verifikasi laporan aduan/apresiasi unit.',
                'logged_at' => Carbon::now(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'pesupeluh_ticket_number' => $pesupeluhTicketNumber,
                'message' => 'Laporan berhasil diverifikasi.' . ($pesupeluhTicketNumber ? " Diteruskan ke PESU PELUH dengan No. Tiket: {$pesupeluhTicketNumber}" : ''),
            ]);
        }

        return redirect()->back()->with([
            'success' => 'Laporan berhasil diverifikasi.',
            'pesupeluh_ticket_number' => $pesupeluhTicketNumber,
        ]);
    }

    /**
     * Display Logbook KPI history of staff in unit.
     */
    public function logbook(Request $request): Response
    {
        $user = $request->user();

        $query = User::where('role_id', Role::STAFF)->with(['room', 'unit', 'kpiLogs.report', 'kpiLogs.verifier'])->where('is_active', true);
        $userRoomId = $user ? ($user->room_id ?? $user->unit_id) : null;
        if ($userRoomId && !$user->isSuperAdmin()) {
            $query->where('room_id', $userRoomId);
        }

        $staffLogbooks = $query->get()->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'nip' => $s->nip ?? '-',
                'role' => $s->role ?? 'STAFF',
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
