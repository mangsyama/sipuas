<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display main hospital dashboard with live data.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Staf pelayanan diarahkan langsung ke halaman presensi utama
        if ($user && $user->isStaff()) {
            return redirect()->route('staff.attendance');
        }

        // 1. Calculate Aggregate Stats
        $totalReports = Report::count();
        $pendingReports = Report::where('status', 'PENDING')->count();
        $verifiedReports = Report::where('status', 'VERIFIED')->count();
        $positiveReports = Report::where('ai_sentiment', 'POSITIF')->count();
        $negativeReports = Report::where('ai_sentiment', 'NEGATIF')->count();

        $statsData = [
            [
                'label' => 'Total Suara Masuk',
                'value' => $totalReports,
                'trend' => '+12% bln ini',
                'isPositive' => true,
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'value' => $pendingReports,
                'trend' => $pendingReports > 0 ? 'Perlu tindakan' : 'Semua beres',
                'isPositive' => $pendingReports === 0,
            ],
            [
                'label' => 'Terverifikasi (Selesai)',
                'value' => $verifiedReports,
                'trend' => $totalReports > 0 ? round(($verifiedReports / $totalReports) * 100) . '% tingkat selesai' : '0%',
                'isPositive' => true,
            ],
            [
                'label' => 'Sentimen Positif',
                'value' => $positiveReports,
                'trend' => ($totalReports > 0 ? round(($positiveReports / $totalReports) * 100) : 0) . '% kepuasan',
                'isPositive' => true,
            ],
        ];

        // 2. Recent Live Reports
        $recentReports = Report::with(['unit', 'attachments'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->ticket_number,
                    'time' => $report->created_at ? $report->created_at->diffForHumans() : 'Baru saja',
                    'unit' => $report->unit ? $report->unit->name : 'Unit Umum',
                    'content' => $report->isi_laporan,
                    'sentiment' => $report->ai_sentiment,
                    'category' => $report->ai_category ?? 'Pelayanan',
                    'status' => $report->status,
                ];
            });

        // 3. Red Zone Breakdown by Unit
        $totalComplaints = $negativeReports > 0 ? $negativeReports : 1;
        $units = Unit::withCount(['reports as complaints_count' => function ($q) {
            $q->where('ai_sentiment', 'NEGATIF');
        }])->get();

        $redZoneBreakdown = $units->map(function ($u) use ($totalComplaints) {
            $count = $u->complaints_count;
            $percent = round(($count / $totalComplaints) * 100);
            $status = $count >= 3 ? 'HIGH_RISK' : ($count >= 1 ? 'MEDIUM_RISK' : 'LOW_RISK');

            return [
                'unit' => $u->name,
                'count' => $count,
                'percent' => $percent,
                'status' => $status,
            ];
        })->sortByDesc('count')->values();

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'statsData' => $statsData,
            'recentReports' => $recentReports,
            'redZoneBreakdown' => $redZoneBreakdown,
        ]);
    }
}
