<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting, WithTitle
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            [
                'Ns. Siti Aminah, S.Kep',
                '199005122015032001',
                '081234567890',
                'Perawat Pelaksana',
                'IGD (Instalasi Gawat Darurat), ICU (Intensive Care Unit)',
                'Aktif',
            ],
            [
                'dr. Bambang Irawan, Sp.PD',
                '198503142010011002',
                '082198765432',
                'Dokter Spesialis Penyakit Dalam',
                'Poli Interna',
                'Aktif',
            ],
            [
                'Dewi Lestari, A.Md.Keb',
                '199511082020012003',
                '081398761234',
                'Bidan Pelaksana',
                'Kamar Bersalin (VK)',
                'Aktif',
            ],
            [
                'Ahmad Fauzi',
                '199811202022011004',
                '',
                'Tenaga Kebersihan (CS)',
                'Area Publik & Ruang Tunggu Utama',
                'Aktif',
            ],
            [
                'Joko Susanto',
                '199607152021021005',
                '085234567891',
                'Petugas Keamanan (Satpam)',
                '',
                'Aktif',
            ],
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama Staf',
            'NIP',
            'Nomor HP',
            'Jabatan',
            'Ruangan',
            'Status Aktif',
        ];
    }

    /**
     * Format kolom NIP dan Nomor HP sebagai Text murni agar tidak terpotong atau
     * terkonversi menjadi format eksponensial (scientific) di Microsoft Excel.
     */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    /**
     * Styling spreadsheet template
     */
    public function styles(Worksheet $sheet): array
    {
        // Styling baris header
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald 600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(28);

        // Styling baris data contoh
        $sheet->getStyle('A2:F6')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ]);

        // Alignment kolom NIP, Nomor HP, dan Status
        $sheet->getStyle('B2:C6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F2:F6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function title(): string
    {
        return 'Template Staf RS';
    }
}
