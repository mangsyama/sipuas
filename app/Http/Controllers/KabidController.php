<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\User;
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
        $totalRsReports = Report::count();
        $totalPositive = Report::where('ai_sentiment', 'POSITIF')->count();
        $totalNegative = Report::where('ai_sentiment', 'NEGATIF')->count();
        $satisfactionIndex = $totalRsReports > 0 ? round(($totalPositive / $totalRsReports) * 100, 1) . '%' : '100%';
        $positivePercent = $totalRsReports > 0 ? round(($totalPositive / $totalRsReports) * 100, 1) : 0;
        $negativePercent = $totalRsReports > 0 ? round(($totalNegative / $totalRsReports) * 100, 1) : 0;
        $totalKpiPoints = Staff::sum('total_points');

        $executiveStats = [
            'total_rs_reports' => $totalRsReports,
            'avg_kasi_response_hours' => $totalRsReports > 0 ? '1.8 Jam' : '-',
            'satisfaction_index' => $satisfactionIndex,
            'total_kpi_points' => (int) $totalKpiPoints,
            'positive_count' => $totalPositive,
            'positive_percent' => $positivePercent,
            'negative_count' => $totalNegative,
            'negative_percent' => $negativePercent,
        ];

        $totalComplaints = Report::where('ai_sentiment', 'NEGATIF')->count();
        if ($totalComplaints === 0) $totalComplaints = 1;

        $redZoneUnits = Unit::withCount(['reports as complaints_count' => function ($q) {
            $q->where('ai_sentiment', 'NEGATIF');
        }])->get()->map(function ($u) use ($totalComplaints) {
            $count = $u->complaints_count;
            $percent = round(($count / $totalComplaints) * 100);
            $status = $count >= 3 ? 'HIGH_RISK' : ($count >= 1 ? 'MEDIUM_RISK' : 'LOW_RISK');

            return [
                'unit' => $u->name,
                'complaints' => $count,
                'percentage' => $percent,
                'status' => $status,
            ];
        })->sortByDesc('complaints')->values();

        return Inertia::render('Kabid/Dashboard', [
            'executiveStats' => $executiveStats,
            'redZoneUnits' => $redZoneUnits,
        ]);
    }

    /**
     * Kasi Responsiveness Overview
     */
    public function kasiResponsiveness(Request $request): Response
    {
        $units = Unit::with(['users' => function ($q) {
            $q->where('role', 'KASI');
        }, 'reports' => function ($q) {
            $q->where('status', 'VERIFIED')->whereNotNull('verified_at');
        }])->withCount(['reports as total_incoming', 'reports as verified_count' => function ($q) {
            $q->where('status', 'VERIFIED');
        }, 'reports as pending_count' => function ($q) {
            $q->where('status', 'PENDING');
        }])->get();

        $kasiData = $units->map(function ($u) {
            $kasiUser = $u->users->first();
            $total = $u->total_incoming;
            $verified = $u->verified_count;
            $pending = $u->pending_count;

            // Calculate real average response time
            $avgHoursStr = '-';
            if ($verified > 0) {
                $totalMinutes = 0;
                $countVerified = 0;
                foreach ($u->reports as $rep) {
                    if ($rep->created_at && $rep->verified_at) {
                        $totalMinutes += $rep->created_at->diffInMinutes($rep->verified_at);
                        $countVerified++;
                    }
                }
                if ($countVerified > 0) {
                    $avgHours = round(($totalMinutes / $countVerified) / 60, 1);
                    $avgHoursStr = $avgHours . ' Jam';
                }
            }

            if ($total > 0) {
                $rate = round(($verified / $total) * 100, 1);
                $status = $rate >= 90 ? 'EXCELLENT' : ($rate >= 75 ? 'GOOD' : 'WARNING');
            } else {
                $rate = null;
                $status = 'EMPTY';
            }

            return [
                'id' => $u->id,
                'name' => $kasiUser ? $kasiUser->name : ($u->pic_name ?: 'Belum Ditugaskan'),
                'role' => $kasiUser ? 'Kasi ' . $u->name : 'PIC Unit',
                'unit' => $u->name,
                'total_incoming' => $total,
                'verified_count' => $verified,
                'pending_count' => $pending,
                'avg_response' => $avgHoursStr,
                'response_rate' => $rate,
                'status' => $status,
            ];
        });

        return Inertia::render('Kabid/KasiResponsiveness', [
            'kasiData' => $kasiData,
        ]);
    }

    /**
     * Leaderboard & Rankings
     */
    public function leaderboard(Request $request): Response
    {
        $topPerformers = Staff::with('unit')
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

        $bottomPerformers = Staff::with('unit')
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
}
