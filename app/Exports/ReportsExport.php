<?php

namespace App\Exports;

use App\Models\Report;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private int $rowNumber = 0;

    public function __construct(public mixed $reportsOrUser = null, public array $filters = [])
    {
    }

    public function collection()
    {
        if ($this->reportsOrUser instanceof Collection) {
            return $this->reportsOrUser;
        }

        $query = Report::with(['room', 'verifier'])->orderByDesc('created_at');

        // Apply filters
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;

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

        if (!empty($this->filters['room_id'])) {
            $query->where('room_id', $this->filters['room_id']);
        }

        if (!empty($this->filters['sentiment']) && $this->filters['sentiment'] !== 'ALL') {
            $query->where('ai_sentiment', $this->filters['sentiment']);
        }

        if (!empty($this->filters['category']) && $this->filters['category'] !== 'ALL') {
            $query->where('ai_category', $this->filters['category']);
        }

        if (!empty($this->filters['status']) && $this->filters['status'] !== 'ALL') {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['shift']) && $this->filters['shift'] !== 'ALL') {
            $query->where('shift_info', 'like', '%' . $this->filters['shift'] . '%');
        }

        if (!empty($this->filters['search'])) {
            $s = $this->filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('isi_laporan', 'like', "%{$s}%")
                  ->orWhere('reporter_name', 'like', "%{$s}%")
                  ->orWhere('target_object', 'like', "%{$s}%");
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Tiket',
            'Tanggal & Jam',
            'Ruangan / Unit',
            'Gedung & Lantai',
            'Nama Pelapor / Pasien',
            'No. Telepon',
            'Sentimen AI',
            'Kategori AI',
            'Skor AI',
            'Shift Pelayanan',
            'Objek Yang Dilaporkan',
            'Isi Laporan Pasien',
            'Status Laporan',
            'Prioritas',
            'Tanggal Verifikasi',
            'Diverifikasi Oleh',
            'Catatan Supervisor / Kasi',
        ];
    }

    public function map($report): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $report->ticket_number,
            $report->created_at ? $report->created_at->format('d/m/Y H:i') : '-',
            $report->room ? $report->room->name : '-',
            $report->room ? $report->room->location_info : '-',
            $report->reporter_name ?: 'Anonim',
            $report->reporter_phone ?: '-',
            $report->ai_sentiment ?: 'NETRAL',
            $report->ai_category ?: '-',
            $report->ai_score !== null ? $report->ai_score : '-',
            $report->shift_info ?: '-',
            $report->target_object ?: '-',
            $report->isi_laporan,
            $report->status,
            $report->priority ?: 'NORMAL',
            $report->verified_at ? $report->verified_at->format('d/m/Y H:i') : '-',
            $report->verifier ? $report->verifier->name : '-',
            $report->supervisor_notes ?: ($report->resolution_notes ?: '-'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF059669'], // Emerald 600
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
