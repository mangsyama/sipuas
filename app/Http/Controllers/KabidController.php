<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Role;
use App\Models\Room;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KabidController extends Controller
{
    /**
     * Executive Command Center Dashboard
     */
    public function dashboard(Request $request): Response
    {
        [$startDate, $endDate, $periodKey, $periodLabel, $roomId] = $this->parsePeriodFilter($request);

        // Base Query with Period & Optional Room Filter
        $reportQuery = Report::query();
        if ($startDate && $endDate) {
            $reportQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        if (!empty($roomId)) {
            $reportQuery->where('room_id', $roomId);
        }

        // Summary Counts
        $totalRsReports = (clone $reportQuery)->count();
        $totalPositive = (clone $reportQuery)->where('ai_sentiment', 'POSITIF')->count();
        $totalNegative = (clone $reportQuery)->where('ai_sentiment', 'NEGATIF')->count();
        $totalNeutral = (clone $reportQuery)->where('ai_sentiment', 'NETRAL')->count();
        $totalPending = (clone $reportQuery)->where('status', 'PENDING')->count();
        $totalVerified = (clone $reportQuery)->whereIn('status', ['VERIFIED', 'RESOLVED'])->count();

        $satisfactionIndex = $totalRsReports > 0 ? round(($totalPositive / $totalRsReports) * 100, 1) . '%' : '100%';
        $positivePercent = $totalRsReports > 0 ? round(($totalPositive / $totalRsReports) * 100, 1) : 0;
        $negativePercent = $totalRsReports > 0 ? round(($totalNegative / $totalRsReports) * 100, 1) : 0;
        $neutralPercent = $totalRsReports > 0 ? round(($totalNeutral / $totalRsReports) * 100, 1) : 0;
        $completionRate = $totalRsReports > 0 ? round(($totalVerified / $totalRsReports) * 100, 1) : 100;
        $totalKpiPoints = User::where('role_id', Role::STAFF)->sum('total_points');

        // Real Response Time Calculation (Calculated from real difference between created_at and verified_at)
        $avgResponseFormatted = $this->calculateRealAvgResponse(clone $reportQuery);

        $executiveStats = [
            'total_rs_reports' => $totalRsReports,
            'total_verified' => $totalVerified,
            'total_pending' => $totalPending,
            'avg_kasi_response_hours' => $avgResponseFormatted,
            'completion_rate' => $completionRate,
            'satisfaction_index' => $satisfactionIndex,
            'total_kpi_points' => (int) $totalKpiPoints,
            'positive_count' => $totalPositive,
            'positive_percent' => $positivePercent,
            'negative_count' => $totalNegative,
            'negative_percent' => $negativePercent,
            'neutral_count' => $totalNeutral,
            'neutral_percent' => $neutralPercent,
        ];

        // Chart 1: Time Series Trend (Incoming vs Verified)
        $trendChart = $this->generateTrendSeries($startDate, $endDate, $periodKey, $roomId);

        // Chart 2: Top Categories with Sentiment Breakdown
        $categoryChart = $this->generateCategoryBreakdown(clone $reportQuery);

        // Chart 3: Sentiment Doughnut Data
        $sentimentChart = [
            'labels' => ['Apresiasi (Positif)', 'Keluhan (Negatif)', 'Saran (Netral)'],
            'data' => [$totalPositive, $totalNegative, $totalNeutral],
            'percentages' => [$positivePercent, $negativePercent, $neutralPercent],
        ];

        // Red Zone Unit Risk Matrix & Response Rate
        $redZoneUnits = $this->generateRedZoneUnits($startDate, $endDate, $totalNegative);

        // Available rooms for optional filter
        $rooms = Room::where('is_active', true)
            ->select(['id', 'name', 'building_name', 'location_floor'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Kabid/Dashboard', [
            'executiveStats' => $executiveStats,
            'redZoneUnits' => $redZoneUnits,
            'trendChart' => $trendChart,
            'categoryChart' => $categoryChart,
            'sentimentChart' => $sentimentChart,
            'rooms' => $rooms,
            'filters' => [
                'period' => $periodKey,
                'period_label' => $periodLabel,
                'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
                'room_id' => $roomId,
            ],
        ]);
    }

    /**
     * Kasi Responsiveness & Unit Accountability Monitoring
     */
    public function kasiResponsiveness(Request $request): Response
    {
        [$startDate, $endDate, $periodKey, $periodLabel, $roomId] = $this->parsePeriodFilter($request);

        $unitsQuery = Unit::where('is_active', true)
            ->with(['reports' => function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            }, 'reports.verifier']);

        if (!empty($roomId)) {
            $unitsQuery->where('id', $roomId);
        }

        $units = $unitsQuery->orderBy('name')->get();

        $kasiData = $units->map(function ($u) {
            $reports = $u->reports;
            $totalReports = $reports->count();
            $verifiedReports = $reports->whereIn('status', ['VERIFIED', 'RESOLVED'])->values();
            $verifiedCount = $verifiedReports->count();
            $pendingCount = $reports->where('status', 'PENDING')->count();
            $verificationRate = $totalReports > 0 ? round(($verifiedCount / $totalReports) * 100) : 100;

            // Real Average Response Calculation for this specific unit
            $verifiedWithTimes = $verifiedReports->filter(fn($r) => !empty($r->verified_at));
            if ($verifiedWithTimes->count() > 0) {
                $totalMinutes = $verifiedWithTimes->reduce(function ($carry, $r) {
                    $created = Carbon::parse($r->created_at);
                    $verified = Carbon::parse($r->verified_at);
                    return $carry + max(0, $created->diffInMinutes($verified));
                }, 0);

                $avgMins = round($totalMinutes / $verifiedWithTimes->count());
                if ($avgMins < 60) {
                    $avgResponseFormatted = $avgMins . ' Menit';
                } else {
                    $avgResponseFormatted = round($avgMins / 60, 1) . ' Jam';
                }
            } else {
                $avgResponseFormatted = $verifiedCount > 0 ? '< 1 Jam' : '-';
            }

            $kasiUser = User::where('room_id', $u->id)->where('role_id', Role::KEPALA_SEKSI)->first();

            // Status responsivitas unit: berdasarkan tingkat penyelesaian verifikasi
            $status = $verificationRate >= 80
                ? 'EXCELLENT'
                : ($verificationRate >= 50 ? 'WARNING' : 'CRITICAL');

            return [
                'unit_id' => $u->id,
                'unit_name' => $u->name,
                'kasi_name' => $kasiUser ? $kasiUser->name : 'Plt. Kepala Ruangan',
                'role' => 'Kepala Ruangan / Kasi',
                'total_reports' => $totalReports,
                'total_incoming' => $totalReports,
                'verified_reports' => $verifiedCount,
                'verified_count' => $verifiedCount,
                'pending_reports' => $pendingCount,
                'pending_count' => $pendingCount,
                'avg_response_hours' => $avgResponseFormatted,
                'verification_percentage' => $verificationRate,
                'status' => $status,
            ];
        });

        // Overall summary
        $totalAllReports = $kasiData->sum('total_reports');
        $totalAllVerified = $kasiData->sum('verified_reports');
        $totalAllPending = $kasiData->sum('pending_reports');
        $avgVerificationAll = $kasiData->count() > 0 ? round($kasiData->avg('verification_percentage')) : 100;

        $rooms = Room::where('is_active', true)->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Kabid/KasiResponsiveness', [
            'kasiData' => $kasiData,
            'summary' => [
                'total_reports' => $totalAllReports,
                'total_verified' => $totalAllVerified,
                'total_pending' => $totalAllPending,
                'avg_verification_rate' => $avgVerificationAll,
            ],
            'rooms' => $rooms,
            'filters' => [
                'period' => $periodKey,
                'period_label' => $periodLabel,
                'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
                'room_id' => $roomId,
            ],
        ]);
    }

    /**
     * Leaderboard & Rankings
     */
    public function leaderboard(Request $request): Response
    {
        $topPerformers = User::where('role_id', Role::STAFF)
            ->with('unit')
            ->where('is_active', true)
            ->orderByDesc('total_points')
            ->take(5)
            ->get()
            ->values()
            ->map(function ($s, $idx) {
                $rank = $idx + 1;
                $badge = match ($rank) {
                    1 => '🥇 Top Performer #1',
                    2 => '🥈 Top Performer #2',
                    3 => '🥉 Top Performer #3',
                    default => 'Top #' . $rank,
                };

                return [
                    'rank' => $rank,
                    'name' => $s->name,
                    'unit' => $s->unit ? $s->unit->name : 'Unit Umum',
                    'points' => $s->total_points,
                    'praise_count' => $s->praise_count,
                    'badge' => $badge,
                ];
            });

        $bottomPerformers = User::where('role_id', Role::STAFF)
            ->with('unit')
            ->where('is_active', true)
            ->where('complaint_count', '>', 0)
            ->orderBy('total_points')
            ->take(5)
            ->get()
            ->values()
            ->map(function ($s, $idx) {
                return [
                    'rank' => $idx + 1,
                    'name' => $s->name,
                    'unit' => $s->unit ? $s->unit->name : 'Unit Umum',
                    'complaint_deductions' => $s->complaint_count,
                    'points' => $s->total_points,
                    'note' => 'Diperlukan Pembinaan & Evaluasi Pelayanan',
                ];
            });

        return Inertia::render('Kabid/Leaderboard', [
            'topPerformers' => $topPerformers,
            'bottomPerformers' => $bottomPerformers,
        ]);
    }

    /**
     * Helper: Parse Period Filter into start and end Carbon dates
     */
    private function parsePeriodFilter(Request $request): array
    {
        $period = $request->input('period', 'all');
        $roomId = $request->input('room_id');
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hari Ini (' . $now->translatedFormat('d M Y') . ')';
                break;
            case '7d':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '7 Hari Terakhir (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                break;
            case '30d':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '30 Hari Terakhir (' . $startDate->format('d/m') . ' - ' . $endDate->format('d/m/Y') . ')';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $periodLabel = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $periodLabel = 'Tahun Ini (' . $now->format('Y') . ')';
                break;
            case 'custom':
                $startInput = $request->input('start_date');
                $endInput = $request->input('end_date');
                if ($startInput && $endInput) {
                    $startDate = Carbon::parse($startInput)->startOfDay();
                    $endDate = Carbon::parse($endInput)->endOfDay();
                    $periodLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                } else {
                    $startDate = null;
                    $endDate = null;
                    $periodLabel = 'Semua Periode';
                    $period = 'all';
                }
                break;
            case 'all':
            default:
                $period = 'all';
                $startDate = null;
                $endDate = null;
                $periodLabel = 'Semua Periode';
                break;
        }

        return [$startDate, $endDate, $period, $periodLabel, $roomId];
    }

    /**
     * Helper: Real Average Response Duration Calculation
     */
    private function calculateRealAvgResponse($query): string
    {
        $verifiedReports = (clone $query)
            ->whereIn('status', ['VERIFIED', 'RESOLVED'])
            ->whereNotNull('verified_at')
            ->get(['created_at', 'verified_at']);

        if ($verifiedReports->count() === 0) {
            return '-';
        }

        $totalMinutes = 0;
        $countWithTimes = 0;

        foreach ($verifiedReports as $r) {
            if ($r->created_at && $r->verified_at) {
                $created = Carbon::parse($r->created_at);
                $verified = Carbon::parse($r->verified_at);
                $diff = max(0, $created->diffInMinutes($verified));
                $totalMinutes += $diff;
                $countWithTimes++;
            }
        }

        if ($countWithTimes === 0) {
            return '< 1 Jam';
        }

        $avgMinutes = round($totalMinutes / $countWithTimes);

        if ($avgMinutes < 60) {
            return $avgMinutes . ' Menit';
        }

        return round($avgMinutes / 60, 1) . ' Jam';
    }

    /**
     * Helper: Generate time-series trend data for Chart.js
     */
    private function generateTrendSeries($startDate, $endDate, string $periodKey, $roomId = null): array
    {
        $labels = [];
        $incomingData = [];
        $verifiedData = [];

        // If 'all' or no dates, default to last 30 days
        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->subDays(29)->startOfDay();
        }

        $diffDays = $startDate->diffInDays($endDate);

        if ($periodKey === 'today') {
            $intervals = [
                ['00:00', '03:59', '00:00 - 04:00'],
                ['04:00', '07:59', '04:00 - 08:00'],
                ['08:00', '11:59', '08:00 - 12:00'],
                ['12:00', '15:59', '12:00 - 16:00'],
                ['16:00', '19:59', '16:00 - 20:00'],
                ['20:00', '23:59', '20:00 - 24:00'],
            ];

            foreach ($intervals as [$startH, $endH, $label]) {
                $labels[] = $label;
                $startInterval = $startDate->copy()->setTimeFromTimeString($startH);
                $endInterval = $startDate->copy()->setTimeFromTimeString($endH);

                $inQ = Report::whereBetween('created_at', [$startInterval, $endInterval]);
                $verQ = Report::whereBetween('verified_at', [$startInterval, $endInterval])
                    ->whereIn('status', ['VERIFIED', 'RESOLVED']);

                if ($roomId) {
                    $inQ->where('room_id', $roomId);
                    $verQ->where('room_id', $roomId);
                }

                $incomingData[] = $inQ->count();
                $verifiedData[] = $verQ->count();
            }
        } elseif ($diffDays <= 31) {
            $period = CarbonPeriod::create($startDate, '1 day', $endDate);
            foreach ($period as $date) {
                $dayStart = $date->copy()->startOfDay();
                $dayEnd = $date->copy()->endOfDay();
                $labels[] = $date->translatedFormat('d M');

                $inQ = Report::whereBetween('created_at', [$dayStart, $dayEnd]);
                $verQ = Report::whereBetween('verified_at', [$dayStart, $dayEnd])
                    ->whereIn('status', ['VERIFIED', 'RESOLVED']);

                if ($roomId) {
                    $inQ->where('room_id', $roomId);
                    $verQ->where('room_id', $roomId);
                }

                $incomingData[] = $inQ->count();
                $verifiedData[] = $verQ->count();
            }
        } else {
            $period = CarbonPeriod::create($startDate, '1 month', $endDate);
            foreach ($period as $date) {
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();
                $labels[] = $date->translatedFormat('M Y');

                $inQ = Report::whereBetween('created_at', [$monthStart, $monthEnd]);
                $verQ = Report::whereBetween('verified_at', [$monthStart, $monthEnd])
                    ->whereIn('status', ['VERIFIED', 'RESOLVED']);

                if ($roomId) {
                    $inQ->where('room_id', $roomId);
                    $verQ->where('room_id', $roomId);
                }

                $incomingData[] = $inQ->count();
                $verifiedData[] = $verQ->count();
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Suara Pasien Masuk',
                    'data' => $incomingData,
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Telah Diverifikasi Kasi',
                    'data' => $verifiedData,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    /**
     * Helper: Category breakdown for bar chart
     */
    private function generateCategoryBreakdown($query): array
    {
        $categories = [
            'Sikap & Keramahan Staf',
            'Waktu Tunggu & Antrean',
            'Sarana & Fasilitas',
            'Pelayanan Medis & Perawat',
            'Farmasi & Obat',
            'Kebersihan & Kenyamanan',
            'Administrasi & Kasir',
        ];

        $dbCats = (clone $query)
            ->whereNotNull('ai_category')
            ->where('ai_category', '!=', '')
            ->selectRaw('ai_category, count(*) as count')
            ->groupBy('ai_category')
            ->orderByDesc('count')
            ->limit(6)
            ->pluck('ai_category')
            ->toArray();

        $allCategories = array_values(array_unique(array_merge($dbCats, $categories)));
        $allCategories = array_slice($allCategories, 0, 7);

        $labels = [];
        $positiveData = [];
        $negativeData = [];
        $neutralData = [];

        foreach ($allCategories as $cat) {
            $labels[] = $cat;
            $pos = (clone $query)->where('ai_category', $cat)->where('ai_sentiment', 'POSITIF')->count();
            $neg = (clone $query)->where('ai_category', $cat)->where('ai_sentiment', 'NEGATIF')->count();
            $neu = (clone $query)->where('ai_category', $cat)->where('ai_sentiment', 'NETRAL')->count();
            $positiveData[] = $pos;
            $negativeData[] = $neg;
            $neutralData[] = $neu;
        }

        return [
            'labels' => $labels,
            'positive' => $positiveData,
            'negative' => $negativeData,
            'neutral' => $neutralData,
        ];
    }

    /**
     * Helper: Red Zone Units Ranking with real response duration
     */
    private function generateRedZoneUnits($startDate, $endDate, int $totalRsNegative): array
    {
        $totalNegative = max(1, $totalRsNegative);

        $units = Unit::where('is_active', true)
            ->with(['reports' => function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            }])
            ->get();

        return $units->map(function ($u) use ($totalNegative) {
            $reports = $u->reports;
            $total = $reports->count();
            $complaints = $reports->where('ai_sentiment', 'NEGATIF')->count();
            $positive = $reports->where('ai_sentiment', 'POSITIF')->count();
            $percent = round(($complaints / $totalNegative) * 100);

            // Real response time for this unit
            $verified = $reports->whereIn('status', ['VERIFIED', 'RESOLVED'])->filter(fn($r) => !empty($r->verified_at));
            if ($verified->count() > 0) {
                $totalMins = $verified->reduce(function ($carry, $r) {
                    $diff = max(0, Carbon::parse($r->created_at)->diffInMinutes(Carbon::parse($r->verified_at)));
                    return $carry + $diff;
                }, 0);
                $avgMins = round($totalMins / $verified->count());
                $avgResponse = $avgMins < 60 ? $avgMins . ' Menit' : round($avgMins / 60, 1) . ' Jam';
            } else {
                $avgResponse = '-';
            }

            $status = $complaints >= 3 ? 'HIGH_RISK' : ($complaints >= 1 ? 'MEDIUM_RISK' : 'LOW_RISK');

            return [
                'unit_id' => $u->id,
                'unit' => $u->name,
                'total_reports' => $total,
                'complaints' => $complaints,
                'positive' => $positive,
                'percentage' => $percent,
                'avg_response' => $avgResponse,
                'status' => $status,
            ];
        })->sortByDesc('complaints')->values()->all();
    }
}
