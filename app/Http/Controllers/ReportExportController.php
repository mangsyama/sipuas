<?php

namespace App\Http\Controllers;

use App\Exports\ReportsExport;
use App\Models\Report;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    /**
     * Display Report Center page with filters and data preview.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'start_date' => $request->input('start_date', ''),
            'end_date'   => $request->input('end_date', ''),
            'room_id'    => $request->input('room_id', ''),
            'sentiment'  => $request->input('sentiment', ''),
            'category'   => $request->input('category', ''),
            'status'     => $request->input('status', ''),
            'shift'      => $request->input('shift', ''),
            'search'     => $request->input('search', ''),
        ];

        $rooms = Room::where('is_active', true)
            ->select(['id', 'name', 'building_name', 'location_floor'])
            ->orderBy('name')
            ->get();

        // Standard categories known by AI Triase
        $standardCategories = [
            'Sarana & Fasilitas',
            'Pelayanan Medis',
            'Sikap & Komunikasi Staf',
            'Sikap & Keramahan Staf',
            'Sarana & Prasarana',
            'Farmasi & Obat',
            'Waktu Tunggu & Antrean',
            'Administrasi & Keuangan',
            'Kebersihan',
            'Pelayanan Umum',
        ];

        // Merge with distinct categories already present in database
        $dbCategories = Report::whereNotNull('ai_category')
            ->where('ai_category', '!=', '')
            ->distinct()
            ->pluck('ai_category')
            ->toArray();

        $mergedCategories = collect(array_merge($standardCategories, $dbCategories))
            ->unique()
            ->filter()
            ->sort()
            ->values();

        $categories = $mergedCategories->map(fn($cat) => [
            'id'   => $cat,
            'name' => $cat,
        ])->all();

        $sentiments = [
            ['id' => 'POSITIF', 'name' => 'Positif (Apresiasi / Pujian)'],
            ['id' => 'NEGATIF', 'name' => 'Negatif (Keluhan / Aduan)'],
            ['id' => 'NETRAL', 'name' => 'Netral (Masukan / Saran)'],
        ];

        $statuses = [
            ['id' => 'PENDING', 'name' => 'Menunggu Verifikasi'],
            ['id' => 'VERIFIED', 'name' => 'Terverifikasi (Poin Diberikan)'],
            ['id' => 'RESOLVED', 'name' => 'Tuntas / Selesai'],
            ['id' => 'REJECTED', 'name' => 'Laporan Ditolak'],
        ];

        $shifts = [
            ['id' => 'Pagi', 'name' => 'Shift Pagi'],
            ['id' => 'Siang', 'name' => 'Shift Siang'],
            ['id' => 'Malam', 'name' => 'Shift Malam'],
        ];

        // Query for reports table
        $query = Report::with(['room', 'verifier'])->orderByDesc('created_at');
        $this->applyFilters($query, $request);
        $reports = $query->paginate(10)->withQueryString();

        // Query for summary stats
        $statsQuery = Report::query();
        $this->applyFilters($statsQuery, $request);

        $stats = [
            'total'     => (clone $statsQuery)->count(),
            'positive'  => (clone $statsQuery)->where('ai_sentiment', 'POSITIF')->count(),
            'negative'  => (clone $statsQuery)->where('ai_sentiment', 'NEGATIF')->count(),
            'verified'  => (clone $statsQuery)->whereIn('status', ['VERIFIED', 'RESOLVED'])->count(),
            'pending'   => (clone $statsQuery)->where('status', 'PENDING')->count(),
        ];

        return Inertia::render('ReportExport/Index', [
            'filters'    => $filters,
            'rooms'      => $rooms,
            'categories' => $categories,
            'sentiments' => $sentiments,
            'statuses'   => $statuses,
            'shifts'     => $shifts,
            'reports'    => $reports,
            'stats'      => $stats,
        ]);
    }

    /**
     * Export filtered reports to PDF (A4 Landscape).
     */
    public function exportPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', 300);

        $query = Report::with(['room', 'verifier', 'attachments'])->orderByDesc('created_at');
        $this->applyFilters($query, $request);
        $reports = $query->get();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $roomName = null;
        if ($request->filled('room_id')) {
            $roomName = Room::find($request->input('room_id'))?->name;
        }

        $sentimentName = $request->input('sentiment');

        $logoPath = public_path('images/logo-sidebar.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $fontDir = storage_path('fonts');
        if (!file_exists($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'font_dir' => $fontDir,
            'font_cache' => $fontDir,
            'temp_dir' => sys_get_temp_dir(),
            'chroot' => [public_path(), storage_path(), base_path()],
            'is_remote_enabled' => true,
            'is_html5_parser_enabled' => true,
        ], true);

        $pdf->loadView('exports.reports_pdf', [
            'reports'       => $reports,
            'exportedAt'    => now()->translatedFormat('d F Y H:i') . ' WITA',
            'startDate'     => $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : null,
            'endDate'       => $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : null,
            'roomName'      => $roomName,
            'sentimentName' => $sentimentName,
            'logoBase64'    => $logoBase64,
            'logoPath'      => $logoPath,
        ])
        ->setPaper('a4', 'landscape');

        return $pdf->download('laporan_sipuas_' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Export filtered reports to Excel (.xlsx).
     */
    public function exportExcel(Request $request)
    {
        return Excel::download(
            new ReportsExport(null, $request->all()),
            'laporan_sipuas_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Export filtered reports to CSV (.csv).
     */
    public function exportCsv(Request $request)
    {
        return Excel::download(
            new ReportsExport(null, $request->all()),
            'laporan_sipuas_' . now()->format('Ymd_His') . '.csv',
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Helper method to apply search and filter constraints.
     */
    private function applyFilters($query, Request $request): void
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($startDate)->startOfDay(),
                \Carbon\Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
        } elseif ($endDate) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        if ($request->filled('sentiment') && $request->input('sentiment') !== 'ALL') {
            $query->where('ai_sentiment', $request->input('sentiment'));
        }

        if ($request->filled('category') && $request->input('category') !== 'ALL') {
            $query->where('ai_category', $request->input('category'));
        }

        if ($request->filled('status') && $request->input('status') !== 'ALL') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('shift') && $request->input('shift') !== 'ALL') {
            $query->where('shift_info', 'like', '%' . $request->input('shift') . '%');
        }

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('isi_laporan', 'like', "%{$s}%")
                  ->orWhere('reporter_name', 'like', "%{$s}%")
                  ->orWhere('target_object', 'like', "%{$s}%");
            });
        }
    }
}
