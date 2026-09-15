<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Unit;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
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
        $verifiedReports = Report::whereIn('status', ['VERIFIED', 'RESOLVED'])->count();
        $positiveReports = Report::where('ai_sentiment', 'POSITIF')->count();
        $negativeReports = Report::where('ai_sentiment', 'NEGATIF')->count();
        $neutralReports = Report::where('ai_sentiment', 'NETRAL')->count();

        $completionRate = $totalReports > 0 ? round(($verifiedReports / $totalReports) * 100) : 100;
        $satisfactionRate = $totalReports > 0 ? round(($positiveReports / $totalReports) * 100) : 100;

        $statsData = [
            [
                'label' => 'Total Suara Masuk',
                'value' => $totalReports,
                'desc' => 'Seluruh masukan pasien RS',
                'isPositive' => true,
            ],
            [
                'label' => 'Menunggu Verifikasi',
                'value' => $pendingReports,
                'desc' => $pendingReports > 0 ? 'Perlu tindakan staf/kasi' : 'Semua tiket telah ditindaklanjuti',
                'isPositive' => $pendingReports === 0,
            ],
            [
                'label' => 'Terverifikasi (Selesai)',
                'value' => $verifiedReports,
                'desc' => $completionRate . '% tingkat penyelesaian',
                'isPositive' => true,
            ],
            [
                'label' => 'Indeks Kepuasan RS',
                'value' => $satisfactionRate . '%',
                'desc' => $positiveReports . ' apresiasi positif diterima',
                'isPositive' => true,
            ],
        ];

        // 2. Trend Chart (14 Hari Terakhir)
        $trendChart = $this->generateTrendSeries();

        // 3. Sentiment Chart
        $positivePercent = $totalReports > 0 ? round(($positiveReports / $totalReports) * 100) : 0;
        $negativePercent = $totalReports > 0 ? round(($negativeReports / $totalReports) * 100) : 0;
        $neutralPercent = $totalReports > 0 ? round(($neutralReports / $totalReports) * 100) : 0;

        $sentimentChart = [
            'labels' => ['Apresiasi (Positif)', 'Keluhan (Negatif)', 'Saran (Netral)'],
            'data' => [$positiveReports, $negativeReports, $neutralReports],
            'percentages' => [$positivePercent, $negativePercent, $neutralPercent],
            'satisfaction_index' => $satisfactionRate . '%',
        ];

        // 4. Category Breakdown
        $categoryChart = $this->generateCategoryBreakdown();

        // 5. Recent Live Reports (consistent with Kasi Aktivitas Terkini)
        $recentReports = Report::with(['room', 'unit', 'attachments'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->ticket_number,
                    'created_at_human' => $report->created_at ? $report->created_at->diffForHumans() : 'Baru saja',
                    'created_at_formatted' => $report->created_at ? $report->created_at->translatedFormat('d M, H:i') : '-',
                    'reporter_name' => $report->is_anonymous ? 'Pasien Anonim' : ($report->reporter_name ?: 'Pasien / Keluarga'),
                    'target_object' => $report->target_object ?: ($report->room ? $report->room->name : ($report->unit ? $report->unit->name : 'Pelayanan')),
                    'isi_laporan' => $report->isi_laporan,
                    'ai_sentiment' => $report->ai_sentiment,
                    'ai_category' => $report->ai_category ?? 'Pelayanan',
                    'status' => $report->status,
                    'unit' => $report->room ? $report->room->name : ($report->unit ? $report->unit->name : 'Unit Umum'),
                ];
            });

        // 6. Red Zone Breakdown by Unit
        $totalComplaints = $negativeReports > 0 ? $negativeReports : 1;
        $units = Unit::where('is_active', true)
            ->withCount(['reports as complaints_count' => function ($q) {
                $q->where('ai_sentiment', 'NEGATIF');
            }])
            ->with(['reports' => function ($q) {
                $q->whereIn('status', ['VERIFIED', 'RESOLVED'])->whereNotNull('verified_at');
            }])
            ->get();

        $redZoneBreakdown = $units->map(function ($u) use ($totalComplaints) {
            $count = $u->complaints_count;
            $percent = round(($count / $totalComplaints) * 100);
            $status = $count >= 3 ? 'HIGH_RISK' : ($count >= 1 ? 'MEDIUM_RISK' : 'LOW_RISK');

            $verified = $u->reports;
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

            return [
                'unit' => $u->name,
                'count' => $count,
                'percent' => $percent,
                'status' => $status,
                'avg_response' => $avgResponse,
            ];
        })->sortByDesc('count')->values();

        return Inertia::render('Dashboard/Index', [
            'user' => $user,
            'statsData' => $statsData,
            'trendChart' => $trendChart,
            'sentimentChart' => $sentimentChart,
            'categoryChart' => $categoryChart,
            'recentReports' => $recentReports,
            'redZoneBreakdown' => $redZoneBreakdown,
        ]);
    }

    private function generateTrendSeries(): array
    {
        $startDate = Carbon::now()->subDays(13)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        $labels = [];
        $incomingData = [];
        $verifiedData = [];

        foreach ($period as $date) {
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();
            $labels[] = $date->translatedFormat('d M');

            $incomingData[] = Report::whereBetween('created_at', [$dayStart, $dayEnd])->count();
            $verifiedData[] = Report::whereBetween('verified_at', [$dayStart, $dayEnd])
                ->whereIn('status', ['VERIFIED', 'RESOLVED'])
                ->count();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Suara Masuk',
                    'data' => $incomingData,
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Telah Selesai',
                    'data' => $verifiedData,
                    'borderColor' => '#0284c7',
                    'backgroundColor' => 'rgba(2, 132, 199, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];
    }

    private function generateCategoryBreakdown(): array
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

        $dbCats = Report::whereNotNull('ai_category')
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
            $positiveData[] = Report::where('ai_category', $cat)->where('ai_sentiment', 'POSITIF')->count();
            $negativeData[] = Report::where('ai_category', $cat)->where('ai_sentiment', 'NEGATIF')->count();
            $neutralData[] = Report::where('ai_category', $cat)->where('ai_sentiment', 'NETRAL')->count();
        }

        return [
            'labels' => $labels,
            'positive' => $positiveData,
            'negative' => $negativeData,
            'neutral' => $neutralData,
        ];
    }
}
