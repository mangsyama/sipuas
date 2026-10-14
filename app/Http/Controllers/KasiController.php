<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStaff;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\StaffKpiLog;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Room;
use Carbon\CarbonPeriod;
use App\Channels\WaGatewayChannel;

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
        $canFilterRooms = $isElevated || ($user && empty($userRoomId));

        // Selected Room Filter
        $roomId = $request->input('room_id');
        if (!$canFilterRooms || empty($roomId)) {
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
            ->selectRaw("ai_category, count(*) as count,
                SUM(CASE WHEN ai_sentiment = 'POSITIF' THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE WHEN ai_sentiment = 'NEGATIF' THEN 1 ELSE 0 END) as negative_count,
                SUM(CASE WHEN ai_sentiment = 'NETRAL' THEN 1 ELSE 0 END) as neutral_count")
            ->groupBy('ai_category')
            ->orderByRaw('COUNT(*) DESC')
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
                'created_at_human' => $r->created_at_human,
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
        $rooms = $canFilterRooms ? Room::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get() : [];

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
        $canFilterRooms = $isElevated || ($user && empty($userRoomId));

        // Selected Room Filter
        $roomId = $request->input('room_id');
        if (!$canFilterRooms || empty($roomId)) {
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
                'created_at_human' => $r->created_at_human,
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
                'verified_action_type' => $r->resolution_notes === 'DIBATALKAN' ? 'DIBATALKAN' : null,
                'verified_by' => $r->verifier ? $r->verifier->name : null,
            ];
        });

        $totalReports = $reports->count();
        $pendingReports = $reports->where('status', 'PENDING')->count();
        $verifiedReports = $reports->whereIn('status', ['VERIFIED', 'RESOLVED'])->count();

        $currentRoom = $effectiveRoomId ? Room::find($effectiveRoomId) : null;
        $rooms = $canFilterRooms ? Room::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get() : [];

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
            $query = Report::with(['room', 'unit', 'attachments', 'staff', 'hospitalStaff', 'reportStaff', 'verifier']);
            if (is_numeric($id)) {
                $query->where(function ($q) use ($id) {
                    $q->where('ticket_number', (string)$id)->orWhere('id', (int)$id);
                });
            } else {
                $query->where('ticket_number', (string)$id);
            }
            $report = $query->first();
        }

        if (!$report) {
            $report = Report::with(['room', 'unit', 'attachments', 'staff', 'hospitalStaff', 'reportStaff', 'verifier'])->latest()->first();
        }

        // Tandai notifikasi laporan ini otomatis terbaca saat dibuka/diverifikasi oleh user secara eksplisit
        if ($report && $id && !$request->header('X-Inertia-Partial-Data')) {
            \App\Services\NotificationService::markAsRead('report-' . $report->id, $request);
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

        // 1. Ambil seluruh staf dari Master Staff Management yang BENAR-BENAR ditugaskan di ruangan ini (Many-to-Many)
        $hospitalStaffInRoom = $roomId 
            ? Staff::where('is_active', true)
                ->whereHas('rooms', function ($q) use ($roomId) {
                    $q->where('rooms.id', $roomId);
                })
                ->get()
            : collect();

        // 2. Ambil juga pengguna sistem dengan role staf di ruangan ini (kompatibilitas akun sistem)
        $userStaffInRoom = $roomId 
            ? User::where('room_id', $roomId)
                ->where('role_id', Role::STAFF)
                ->where('is_active', true)
                ->get()
            : collect();

        // Gabungkan staf master dan staf user (hindari duplikasi berdasarkan user_id atau nip)
        $combinedStaff = collect();
        $seenNips = [];
        $seenUserIds = [];

        foreach ($hospitalStaffInRoom as $hs) {
            $combinedStaff->push([
                'type' => 'STAFF_MASTER',
                'user_id' => $hs->user_id,
                'id' => $hs->id,
                'name' => $hs->name,
                'nip' => $hs->nip,
                'role' => $hs->position ?: 'Staf Pelayanan',
                'total_points' => $hs->total_points,
                'is_on_duty' => false,
            ]);
            if ($hs->nip) $seenNips[] = $hs->nip;
            if ($hs->user_id) $seenUserIds[] = $hs->user_id;
        }

        foreach ($userStaffInRoom as $us) {
            if (($us->nip && in_array($us->nip, $seenNips)) || in_array($us->id, $seenUserIds)) {
                continue;
            }
            $combinedStaff->push([
                'type' => 'USER',
                'user_id' => $us->id,
                'id' => $us->id,
                'name' => $us->name,
                'nip' => $us->nip,
                'role' => $us->role ?: 'Staf Pelayanan',
                'total_points' => $us->total_points,
                'is_on_duty' => (bool)$us->is_on_duty,
            ]);
        }

        // Ambil ID staf yang sudah tertaut pada laporan ini (jika laporan sudah pernah diverifikasi)
        $linkedStaffIds = $report ? $report->reportStaff->pluck('staff_id')->filter()->toArray() : [];
        $linkedUserIds = $report ? $report->reportStaff->pluck('user_id')->filter()->toArray() : [];

        $staffList = $combinedStaff->map(function ($s) use ($report, $reportTime, $attendancesOnDate, $linkedStaffIds, $linkedUserIds) {
            $isLinked = ($s['type'] === 'STAFF_MASTER' && in_array($s['id'], $linkedStaffIds))
                || ($s['user_id'] && in_array($s['user_id'], $linkedUserIds));
            $isPending = $report && $report->status === 'PENDING';

            // Evaluasi presensi nyata (Clock-In) jika staf ini memiliki data user/presensi
            $userAtts = $s['user_id'] ? $attendancesOnDate->get($s['user_id'], collect()) : collect();
            $activeAtTime = false;
            $attendanceType = 'ASSIGNED';
            $attendanceLabel = 'Terdaftar di Ruangan Ini';
            $clockInTime = null;

            foreach ($userAtts as $att) {
                $in = $att->check_in_at ? Carbon::parse($att->check_in_at) : null;
                $out = $att->check_out_at ? Carbon::parse($att->check_out_at) : null;

                if ($in && $reportTime) {
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

            if (!$activeAtTime && !empty($s['is_on_duty'])) {
                $attendanceLabel = 'Sedang On-Duty';
            }

            // Otomatis centang staf yang tercatat berdinas saat kejadian jika aduan belum diverifikasi
            $autoSelected = $isPending && $activeAtTime;

            return [
                'id' => $s['id'],
                'type' => $s['type'],
                'user_id' => $s['user_id'],
                'name' => $s['name'],
                'nip' => (string) ($s['nip'] ?? '-'),
                'role' => $s['role'] ?? 'Staf Pelayanan',
                'total_points' => $s['total_points'],
                'is_on_duty' => !empty($s['is_on_duty']) || $activeAtTime,
                'active_at_time' => $activeAtTime,
                'attendance_type' => $attendanceType,
                'attendance_label' => $attendanceLabel,
                'clock_in_time' => $clockInTime,
                'selected' => $isLinked || $autoSelected,
            ];
        })->values();

        // Format attachments
        $attachments = $report ? $report->attachments->where('category', '!=', 'VERIFICATION')->values()->map(function ($att) {
            return [
                'id' => $att->id,
                'file_name' => $att->file_name,
                'file_type' => $att->file_type,
                'mime_type' => $att->mime_type,
                'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                'file_size' => $att->file_size_bytes ? round($att->file_size_bytes / 1024, 1) . ' KB' : '-',
            ];
        }) : [];

        // Format verification attachments (SP, Surat Teguran, Berita Acara, dll)
        $verificationAttachments = $report ? $report->attachments->where('category', 'VERIFICATION')->values()->map(function ($att) {
            return [
                'id' => $att->id,
                'file_name' => $att->file_name,
                'file_type' => $att->file_type,
                'mime_type' => $att->mime_type,
                'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                'file_size' => $att->file_size_bytes ? round($att->file_size_bytes / 1024, 1) . ' KB' : '-',
                'uploaded_by' => $att->uploadedBy ? $att->uploadedBy->name : null,
                'created_at' => $att->created_at ? $att->created_at->format('d M Y, H:i') : null,
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
            if ($report->resolution_notes === 'DIBATALKAN') {
                $verifiedActionType = 'DIBATALKAN';
                $verifiedPoints = 0;
            } else {
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

                if (!$verifiedActionType) {
                    $verifiedActionType = 'NETRAL';
                    $verifiedPoints = 0;
                }
            }

            // Pastikan staf yang terkait saat verifikasi selalu ada dalam daftar staf
            $existingStaffIds = $staffList->pluck('id')->toArray();
            if ($report->reportStaff->isNotEmpty()) {
                foreach ($report->reportStaff as $rs) {
                    if ($rs->staff_id) {
                        $st = Staff::find($rs->staff_id);
                        if ($st && !in_array($st->id, $existingStaffIds)) {
                            $staffList->push([
                                'id' => $st->id,
                                'type' => 'STAFF_MASTER',
                                'user_id' => $st->user_id,
                                'name' => $st->name,
                                'nip' => (string) ($st->nip ?? '-'),
                                'role' => $st->position ?? 'Staf Pelayanan',
                                'total_points' => $st->total_points,
                                'is_on_duty' => false,
                                'active_at_time' => false,
                                'attendance_type' => 'NONE',
                                'attendance_label' => 'Staf Terkait',
                                'clock_in_time' => null,
                                'selected' => true,
                            ]);
                            $existingStaffIds[] = $st->id;
                        }
                    } elseif ($rs->user_id) {
                        $usr = User::find($rs->user_id);
                        if ($usr && !in_array($usr->id, $existingStaffIds)) {
                            $staffList->push([
                                'id' => $usr->id,
                                'type' => 'USER',
                                'user_id' => $usr->id,
                                'name' => $usr->name,
                                'nip' => (string) ($usr->nip ?? '-'),
                                'role' => $usr->role ?? 'Staf Pelayanan',
                                'total_points' => $usr->total_points,
                                'is_on_duty' => (bool)$usr->is_on_duty,
                                'active_at_time' => false,
                                'attendance_type' => 'NONE',
                                'attendance_label' => 'Staf Terkait',
                                'clock_in_time' => null,
                                'selected' => true,
                            ]);
                            $existingStaffIds[] = $usr->id;
                        }
                    }
                }
            } elseif ($report->staff->isNotEmpty()) {
                foreach ($report->staff as $linkedStaff) {
                    if (!in_array($linkedStaff->id, $existingStaffIds)) {
                        $staffList->push([
                            'id' => $linkedStaff->id,
                            'type' => 'USER',
                            'user_id' => $linkedStaff->id,
                            'name' => $linkedStaff->name,
                            'nip' => (string) ($linkedStaff->nip ?? '-'),
                            'role' => $linkedStaff->role ?? 'Staf Pelayanan',
                            'total_points' => $linkedStaff->total_points,
                            'is_on_duty' => (bool)$linkedStaff->is_on_duty,
                            'active_at_time' => false,
                            'attendance_type' => 'NONE',
                            'attendance_label' => 'Staf Terkait',
                            'clock_in_time' => null,
                            'selected' => true,
                        ]);
                    }
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
            'created_at_human' => $report->created_at_human,
            'shift_info' => $report->created_at ? $report->created_at->format('H:i') . ' WITA' : 'Waktu Aduan',
            'status' => $report->status,
            'priority' => $report->priority,
            'supervisor_notes' => $report->supervisor_notes,
            'verified_at' => $report->verified_at ? $report->verified_at->format('d M Y, H:i') : null,
            'verified_by' => $report->verifier ? $report->verifier->name : null,
            'verified_action_type' => $verifiedActionType,
            'verified_points' => $verifiedPoints,
            'verified_kpi_category' => $report->verified_kpi_category,
            'verified_severity_level' => $report->verified_severity_level,
            'pesupeluh_ticket_id' => $report->pesupeluh_ticket_id,
            'pesupeluh_ticket_number' => $report->pesupeluh_ticket_number,
            'dispatched_to_pesupeluh_at' => $report->dispatched_to_pesupeluh_at ? $report->dispatched_to_pesupeluh_at->format('d M Y, H:i') . ' WITA' : null,
            'attachments' => $attachments,
            'verification_attachments' => $verificationAttachments,
        ] : null;

        $pesupeluhService = app(\App\Services\PesupeluhService::class);
        $pesupeluhCategories = $pesupeluhService->getCategories();
        $pesupeluhRooms = $pesupeluhService->getRooms();

        $kpiCategories = [
            [
                'id' => 'KERAMAHAN',
                'name' => 'Keramahan & Komunikasi',
                'icon' => 'HeartHandshake',
                'description' => 'Senyum, Salam, Sapa, Sopan, Santun (5S), nada bicara, empati & kesabaran melayani pasien/keluarga.',
            ],
            [
                'id' => 'KEDISIPLINAN',
                'name' => 'Kedisiplinan & Waktu Tanggap',
                'icon' => 'Clock',
                'description' => 'Kecepatan respon pelayanan, ketepatan waktu hadir/visite, respon cepat terhadap panggilan bel/keluhan.',
            ],
            [
                'id' => 'SOP_PELAYANAN',
                'name' => 'Kepatuhan SOP & Profesionalisme',
                'icon' => 'ClipboardCheck',
                'description' => 'Kepatuhan prosedur klinis & administrasi, ketelitian tindakan, edukasi pasien, kebersihan & higienitas.',
            ],
            [
                'id' => 'INTEGRITAS',
                'name' => 'Integritas & Etika Profesi',
                'icon' => 'ShieldCheck',
                'description' => 'Kejujuran, penolakan gratifikasi/pungli, perlindungan privasi medis pasien, dan etika profesi RS.',
            ],
        ];

        $kpiTemplates = [
            'PEMOTONGAN' => [
                ['severity' => 'RINGAN', 'label' => 'Ringan', 'points' => 2, 'desc' => 'Teguran lisan / kekurangramahan minor'],
                ['severity' => 'SEDANG', 'label' => 'Sedang', 'points' => 5, 'desc' => 'Keterlambatan respon / kelalaian SOP administratif'],
                ['severity' => 'BERAT', 'label' => 'Berat', 'points' => 10, 'desc' => 'Ketidaksopanan fatal / pengabaian pasien / pelanggaran etika'],
                ['severity' => 'CUSTOM', 'label' => 'Kustom', 'points' => null, 'desc' => 'Poin ditentukan sendiri oleh Kepala Seksi'],
            ],
            'PENAMBAHAN' => [
                ['severity' => 'APRESIASI', 'label' => 'Apresiasi Ramah', 'points' => 3, 'desc' => 'Pujian sikap ramah & pelayanan komunikatif'],
                ['severity' => 'BINTANG', 'label' => 'Bintang Layanan', 'points' => 5, 'desc' => 'Pelayanan cepat, tanggap & memuaskan pasien'],
                ['severity' => 'TELADAN', 'label' => 'Kinerja Teladan', 'points' => 10, 'desc' => 'Dedikasi luar biasa / penanganan situasi darurat kritis'],
                ['severity' => 'CUSTOM', 'label' => 'Kustom', 'points' => null, 'desc' => 'Poin ditentukan sendiri oleh Kepala Seksi'],
            ],
        ];

        $allHospitalStaff = Staff::where('is_active', true)
            ->with('rooms')
            ->orderBy('name')
            ->get()
            ->map(function ($s) {
                $roomNames = $s->rooms->pluck('name')->join(', ');
                return [
                    'id' => $s->id,
                    'type' => 'STAFF_MASTER',
                    'name' => $s->name,
                    'nip' => (string) ($s->nip ?? '-'),
                    'role' => $s->position ?? 'Staf Pelayanan',
                    'unit_name' => $roomNames ?: null,
                    'total_points' => $s->total_points,
                ];
            })->values()->toArray();

        // Gabungkan juga data User bertipe Staf jika ada yang belum terdaftar di Master Staff
        $existingNames = array_map('strtolower', array_column($allHospitalStaff, 'name'));
        $userStaffAll = User::where('role_id', Role::STAFF)
            ->where('is_active', true)
            ->with('room')
            ->orderBy('name')
            ->get();

        foreach ($userStaffAll as $us) {
            if (!in_array(strtolower($us->name), $existingNames)) {
                $allHospitalStaff[] = [
                    'id' => $us->id,
                    'type' => 'USER',
                    'name' => $us->name,
                    'nip' => (string) ($us->nip ?? '-'),
                    'role' => $us->role ?? 'Staf Pelayanan',
                    'unit_name' => $us->room ? $us->room->name : null,
                    'total_points' => $us->total_points,
                ];
            }
        }

        return Inertia::render('Kasi/Verify', [
            'id' => $report ? $report->ticket_number : ($id ?? ''),
            'reportDetail' => $reportDetail,
            'staffList' => $staffList,
            'staffMembers' => $staffList,
            'allHospitalStaff' => $allHospitalStaff,
            'pesupeluhCategories' => $pesupeluhCategories,
            'pesupeluhRooms' => $pesupeluhRooms,
            'kpiCategories' => $kpiCategories,
            'kpiTemplates' => $kpiTemplates,
        ]);
    }

    /**
     * Process verification and update KPI logbook.
     */
    public function processVerification(Request $request, $id)
    {
        $report = is_numeric($id)
            ? Report::where('ticket_number', (string)$id)->orWhere('id', (int)$id)->firstOrFail()
            : Report::where('ticket_number', (string)$id)->firstOrFail();

        if ($report->status === 'VERIFIED') {
            return redirect()->route('kasi.feed');
        }

        $isNeutralOrCancelled = in_array($request->input('action_type'), ['NETRAL', 'DIBATALKAN']);
        $isCancelled = $request->input('action_type') === 'DIBATALKAN';

        $validated = $request->validate([
            'selected_staff_ids' => $isNeutralOrCancelled ? 'nullable|array' : 'required|array|min:1',
            'action_type' => 'required|string|in:PENAMBAHAN,PEMOTONGAN,NETRAL,DIBATALKAN',
            'kpi_category' => $isNeutralOrCancelled ? 'nullable|string|max:50' : 'required|string|in:KERAMAHAN,KEDISIPLINAN,SOP_PELAYANAN,INTEGRITAS',
            'severity_level' => 'nullable|string|max:50',
            'points' => 'required|integer',
            'supervisor_notes' => 'required|string|min:3',
            'forward_to_pesupeluh' => 'nullable|boolean',
            'pesupeluh_category_id' => 'nullable|integer',
            'pesupeluh_room_id' => 'nullable|integer',
            'pesupeluh_priority' => 'nullable|string|in:ROUTINE,URGENT',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx',
            'attachment' => 'nullable|file|max:10240|mimes:jpeg,png,jpg,webp,pdf,doc,docx,xls,xlsx',
        ]);

        $points = (int) $validated['points'];
        if ($validated['action_type'] === 'NETRAL' || $validated['action_type'] === 'DIBATALKAN') {
            $points = 0;
        } elseif ($validated['action_type'] === 'PEMOTONGAN' && $points > 0) {
            $points = -$points;
        }

        $kpiCategory = $isNeutralOrCancelled ? null : ($validated['kpi_category'] ?? null);
        $severityLevel = $isNeutralOrCancelled ? null : ($validated['severity_level'] ?? null);

        // Update Report (simpan DIBATALKAN di resolution_notes secara aman tanpa migrasi DB)
        $report->update([
            'status' => 'VERIFIED',
            'verified_by' => $request->user() ? $request->user()->id : null,
            'verified_at' => Carbon::now(),
            'verified_kpi_category' => $kpiCategory,
            'verified_severity_level' => $severityLevel,
            'supervisor_notes' => $validated['supervisor_notes'],
            'resolution_notes' => $validated['action_type'] === 'DIBATALKAN' ? 'DIBATALKAN' : null,
        ]);

        // Simpan lampiran berkas verifikasi (Surat Peringatan, Berita Acara, dll) jika diunggah
        $uploadedFiles = [];
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            $uploadedFiles = is_array($files) ? $files : [$files];
        } elseif ($request->hasFile('attachment')) {
            $uploadedFiles = [$request->file('attachment')];
        }

        foreach ($uploadedFiles as $file) {
            if ($file && $file->isValid()) {
                $path = $file->store('verification_attachments/' . date('Y/m'), 'public');
                $mime = $file->getMimeType();
                $ext = strtolower($file->getClientOriginalExtension());

                $type = 'document';
                if (str_starts_with($mime, 'image/')) {
                    $type = 'image';
                } elseif ($mime === 'application/pdf' || $ext === 'pdf') {
                    $type = 'pdf';
                }

                ReportAttachment::create([
                    'report_id' => $report->id,
                    'category' => 'VERIFICATION',
                    'uploaded_by' => $request->user() ? $request->user()->id : null,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $type,
                    'mime_type' => $mime,
                    'file_size_bytes' => $file->getSize(),
                ]);
            }
        }

        // Disposisi ke Sistem Penunjang (PESU PELUH) jika diaktifkan dan tindakan NETRAL
        $pesupeluhTicketNumber = null;
        if ($validated['action_type'] === 'NETRAL' && !empty($validated['forward_to_pesupeluh']) && !empty($validated['pesupeluh_category_id'])) {
            try {
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
            } catch (\Throwable $pesuErr) {
                \Illuminate\Support\Facades\Log::warning('Pesupeluh dispatch notice in processVerification: ' . $pesuErr->getMessage());
            }
        }

        // Sync report staff & update staff KPI logs (mendukung Master Staf dan User Staf)
        if ($validated['action_type'] !== 'DIBATALKAN') {
            $selectedStaffIds = $validated['selected_staff_ids'] ?? [];
            foreach ($selectedStaffIds as $staffId) {
                // 1. Cek apakah ini staf dari Master Staff Management
                $staffMember = Staff::find($staffId);
                $userStaff = null;

                if ($staffMember) {
                    if ($validated['action_type'] === 'PENAMBAHAN') {
                        $staffMember->increment('praise_count');
                        $staffMember->increment('total_points', abs($points));
                    } elseif ($validated['action_type'] === 'PEMOTONGAN') {
                        $staffMember->increment('complaint_count');
                        $staffMember->decrement('total_points', abs($points));
                    }
                    $staffMember->update(['last_point_update_at' => Carbon::now()]);

                    if ($staffMember->user_id) {
                        $userStaff = User::find($staffMember->user_id);
                    } elseif (!empty($staffMember->nip)) {
                        $userStaff = User::where('nip', $staffMember->nip)->first();
                        if ($userStaff) {
                            $staffMember->update(['user_id' => $userStaff->id]);
                        }
                    }
                } else {
                    // 2. Fallback cek apakah ini ID dari model User
                    $userStaff = User::find($staffId);
                }

                if ($userStaff) {
                    if ($validated['action_type'] === 'PENAMBAHAN') {
                        $userStaff->increment('praise_count');
                        $userStaff->increment('total_points', abs($points));
                    } elseif ($validated['action_type'] === 'PEMOTONGAN') {
                        $userStaff->increment('complaint_count');
                        $userStaff->decrement('total_points', abs($points));
                    }
                    $userStaff->update(['last_point_update_at' => Carbon::now()]);
                }

                // Catat ke ReportStaff (dengan staff_id atau user_id)
                ReportStaff::updateOrCreate(
                    [
                        'report_id' => $report->id,
                        'staff_id' => $staffMember ? $staffMember->id : null,
                        'user_id' => $userStaff ? $userStaff->id : ($staffMember?->user_id ?? null)
                    ],
                    [
                        'action_type' => $validated['action_type'],
                        'kpi_category' => $kpiCategory,
                        'severity_level' => $severityLevel,
                        'points' => $points,
                    ]
                );

                // Tambahkan KPI Log
                StaffKpiLog::create([
                    'staff_id' => $staffMember ? $staffMember->id : null,
                    'user_id' => $userStaff ? $userStaff->id : ($staffMember?->user_id ?? null),
                    'report_id' => $report->id,
                    'verified_by' => $request->user() ? $request->user()->id : null,
                    'action_type' => $validated['action_type'],
                    'kpi_category' => $kpiCategory,
                    'severity_level' => $severityLevel,
                    'points' => $points,
                    'note' => $validated['supervisor_notes'] ?? 'Verifikasi laporan aduan/apresiasi unit.',
                    'logged_at' => Carbon::now(),
                ]);
            }
        }

        // Kirim Notifikasi WhatsApp Ucapan Terima Kasih & Laporan Selesai ke Pelapor (jika nomor HP diisi)
        if (!empty($report->reporter_phone)) {
            try {
                $report->loadMissing('room');
                $reporterPhone = $report->reporter_phone;
                $reporterName = $report->reporter_name;
                $ticketNumber = $report->ticket_number;
                $roomName = $report->room 
                    ? ($report->room->location_info ? "{$report->room->name} ({$report->room->location_info})" : $report->room->name) 
                    : 'Pelayanan Rumah Sakit';

                dispatch(function () use ($reporterPhone, $reporterName, $ticketNumber, $roomName) {
                    try {
                        $hasName = !empty($reporterName) && strtolower(trim($reporterName)) !== 'anonim';
                        $greeting = $hasName ? "Halo *{$reporterName}*," : "Halo,";

                        $waMsg = "{$greeting}\n\n"
                            . "Terima kasih atas laporan/aspirasi yang telah Anda sampaikan melalui sistem *SIPUAS*.\n\n"
                            . "Berikut adalah rincian penanganan tiket aduan Anda:\n"
                            . "*Nomor Tiket :* *{$ticketNumber}*\n"
                            . "*Ruangan :* {$roomName}\n"
                            . "*Status :* Selesai Ditangani & Diverifikasi\n\n"
                            . "Laporan Anda telah selesai ditinjau dan divalidasi oleh manajemen pelayanan rumah sakit untuk peningkatan mutu pelayanan rumah sakit.\n\n"
                            . "Setiap masukan dari Anda sangat berarti bagi perbaikan layanan kami.\n\n"
                            . "Salam sehat,\n_Tim Manajemen Pelayanan Rumah Sakit_";

                        WaGatewayChannel::sendDirect($reporterPhone, $waMsg);
                    } catch (\Throwable $sendErr) {
                        \Illuminate\Support\Facades\Log::warning('WA notification send notice: ' . $sendErr->getMessage());
                    }
                })->afterResponse();
            } catch (\Throwable $waErr) {
                \Illuminate\Support\Facades\Log::info('Reporter completion WA notification notice: ' . $waErr->getMessage());
            }
        }

        // Real-Time Notification Event
        try {
            event(new \App\Events\ReportStatusUpdated($report));
        } catch (\Throwable $bErr) {}

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
     * Display Logbook KPI history of staff in unit (mendukung Master Staf dan User Staf).
     */
    public function logbook(Request $request): Response
    {
        $user = $request->user();

        // 1. Ambil seluruh staf dari Master Staff (Staff) aktif
        $staffMasterQuery = Staff::where('is_active', true)
            ->with([
                'rooms',
                'user',
                'kpiLogs' => fn($q) => $q->latest('logged_at'),
                'kpiLogs.report.attachments',
                'kpiLogs.report.room',
                'kpiLogs.verifier'
            ]);

        $staffList = $staffMasterQuery->orderBy('name')->get();

        $existingNips = $staffList->pluck('nip')->filter()->map(fn($n) => strtolower(trim($n)))->toArray();
        $existingUserIds = $staffList->pluck('user_id')->filter()->toArray();

        // 2. Ambil juga pengguna sistem dengan role Staf yang belum terdaftar di Master Staff
        $userStaffQuery = User::where('role_id', Role::STAFF)
            ->with([
                'room',
                'unit',
                'kpiLogs' => fn($q) => $q->latest('logged_at'),
                'kpiLogs.report.attachments',
                'kpiLogs.report.room',
                'kpiLogs.verifier'
            ])
            ->where('is_active', true);

        $extraUserStaff = $userStaffQuery->get()->filter(function ($u) use ($existingNips, $existingUserIds) {
            if (in_array($u->id, $existingUserIds)) return false;
            if ($u->nip && in_array(strtolower(trim($u->nip)), $existingNips)) return false;
            return true;
        });

        // 3. Mapping data logbook terpadu
        $staffLogbooks = collect();

        foreach ($staffList as $s) {
            $roomName = $s->rooms->pluck('name')->join(', ');
            $roomIds = $s->rooms->pluck('id')->toArray();
            $logs = $s->kpiLogs;

            // Jika staf memiliki akun user, gabungkan juga kpiLogs yang tercatat via user_id
            if ($s->user_id) {
                $existingLogIds = $logs->pluck('id')->toArray();
                $extraLogs = StaffKpiLog::where('user_id', $s->user_id)
                    ->whereNotIn('id', $existingLogIds)
                    ->with(['report.attachments', 'report.room', 'verifier'])
                    ->latest('logged_at')
                    ->get();
                if ($extraLogs->isNotEmpty()) {
                    $logs = $logs->concat($extraLogs)->sortByDesc('logged_at')->values();
                }
            }

            $staffLogbooks->push([
                'id' => 'staff-' . $s->id,
                'staff_id' => $s->id,
                'user_id' => $s->user_id,
                'name' => $s->name,
                'nip' => $s->nip ?? '-',
                'role' => $s->position ?? 'Staf Pelayanan',
                'room_name' => $roomName ?: null,
                'room_ids' => $roomIds,
                'total_points' => $s->total_points,
                'praise_count' => $s->praise_count,
                'complaint_count' => $s->complaint_count,
                'last_update' => $s->last_point_update_at ? $s->last_point_update_at->format('Y-m-d H:i') : ($s->updated_at ? $s->updated_at->format('Y-m-d H:i') : '-'),
                'history' => $this->formatKpiHistory($logs),
            ]);
        }

        foreach ($extraUserStaff as $u) {
            $roomName = $u->room ? $u->room->name : ($u->unit ? $u->unit->name : null);
            $roomIds = array_values(array_filter([$u->room_id, $u->unit_id]));
            $staffLogbooks->push([
                'id' => 'user-' . $u->id,
                'staff_id' => null,
                'user_id' => $u->id,
                'name' => $u->name,
                'nip' => $u->nip ?? '-',
                'role' => $u->role ?? 'Staf Pelayanan',
                'room_name' => $roomName ?: null,
                'room_ids' => $roomIds,
                'total_points' => $u->total_points,
                'praise_count' => $u->praise_count,
                'complaint_count' => $u->complaint_count,
                'last_update' => $u->last_point_update_at ? $u->last_point_update_at->format('Y-m-d H:i') : ($u->updated_at ? $u->updated_at->format('Y-m-d H:i') : '-'),
                'history' => $this->formatKpiHistory($u->kpiLogs),
            ]);
        }

        // Ambil semua daftar ruangan aktif RS
        $rooms = Room::where('is_active', true)
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Kasi/Logbook', [
            'staffLogbooks' => $staffLogbooks->values(),
            'rooms' => $rooms,
        ]);
    }

    /**
     * Helper to format KPI logs collection into uniform logbook history structure.
     */
    private function formatKpiHistory($logs)
    {
        return $logs->map(function ($log) {
            $report = $log->report;
            $verificationAttachments = ($report && $report->attachments)
                ? $report->attachments->where('category', 'VERIFICATION')->values()->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'file_name' => $att->file_name,
                        'file_type' => $att->file_type,
                        'mime_type' => $att->mime_type,
                        'file_size' => $att->file_size_bytes ? round($att->file_size_bytes / 1024, 1) . ' KB' : '-',
                        'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                    ];
                }) : [];

            $evidenceAttachments = ($report && $report->attachments)
                ? $report->attachments->where('category', '!=', 'VERIFICATION')->values()->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'file_name' => $att->file_name,
                        'file_type' => $att->file_type,
                        'mime_type' => $att->mime_type,
                        'file_size' => $att->file_size_bytes ? round($att->file_size_bytes / 1024, 1) . ' KB' : '-',
                        'url' => \Illuminate\Support\Facades\Storage::url($att->file_path),
                    ];
                }) : [];

            return [
                'id' => $log->id,
                'ticket_number' => $report ? $report->ticket_number : 'EVALUASI_MANUAL',
                'type' => $log->action_type,
                'kpi_category' => $log->kpi_category,
                'severity_level' => $log->severity_level,
                'points' => $log->points,
                'note' => $log->note,
                'verifier_name' => $log->verifier ? $log->verifier->name : 'Supervisor Kasi',
                'date' => $log->logged_at ? $log->logged_at->format('d M Y, H:i') : ($log->created_at ? $log->created_at->format('d M Y, H:i') : '-'),
                'has_attachment' => count($verificationAttachments) > 0,
                'attachments' => $verificationAttachments,
                'report_detail' => $report ? [
                    'ticket_number' => $report->ticket_number,
                    'isi_laporan' => $report->isi_laporan,
                    'room_name' => $report->room ? $report->room->name : ($report->unit ? $report->unit->name : '-'),
                    'ai_sentiment' => $report->ai_sentiment,
                    'ai_category' => $report->ai_category,
                    'status' => $report->status,
                    'supervisor_notes' => $report->supervisor_notes,
                    'verified_at' => $report->verified_at ? $report->verified_at->format('d M Y, H:i') : '-',
                    'created_at' => $report->created_at ? $report->created_at->format('d M Y, H:i') : '-',
                    'reporter_name' => $report->is_anonymous ? 'Anonim (Pasien/Pengunjung)' : ($report->reporter_name ?: 'Pasien/Pengunjung'),
                    'verification_attachments' => $verificationAttachments,
                    'evidence_attachments' => $evidenceAttachments,
                ] : null,
            ];
        })->values();
    }

    /**
     * Soft delete a report (Administrator only).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->isAdministrator()) {
            abort(403, 'Hanya Administrator yang memiliki wewenang untuk menghapus laporan.');
        }

        $report = is_numeric($id)
            ? Report::where('ticket_number', (string)$id)->orWhere('id', (int)$id)->firstOrFail()
            : Report::where('ticket_number', (string)$id)->firstOrFail();

        $ticket = $report->ticket_number;
        $report->delete(); // Soft delete via SoftDeletes trait

        // Sinkronkan notifikasi sistem
        \App\Services\NotificationService::markAsRead('report-' . $report->id, $request);

        return redirect()->back()->with('success', "Laporan dengan No. Tiket {$ticket} berhasil dihapus.");
    }
}

