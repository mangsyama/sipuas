<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Suara Pasien — SIPUAS</title>
    <style>
        /* ── LOCAL POPPINS FONTS (IDENTIK DENGAN PESU PELUH) ── */
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 300;
            src: url("{{ public_path('fonts/poppins/Poppins-Light.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Regular.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: italic;
            font-weight: 400;
            src: url("{{ public_path('fonts/poppins/Poppins-Italic.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 500;
            src: url("{{ public_path('fonts/poppins/Poppins-Medium.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            src: url("{{ public_path('fonts/poppins/Poppins-SemiBold.ttf') }}") format("truetype");
        }
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            src: url("{{ public_path('fonts/poppins/Poppins-Bold.ttf') }}") format("truetype");
        }

        /* ── MARGIN FISIK MULTI-PAGE DOMPDF ── */
        @page {
            margin: 8mm 10mm 8mm 10mm;
        }

        body {
            font-family: 'Poppins', sans-serif;
            font-size: 6px;
            color: #0f172a;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* ── HEADER IDENTITAS DOKUMEN (KIRI ATAS TABEL) ── */
        .pdf-header {
            margin-bottom: 8px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 44px;
            padding-right: 8px;
        }

        .logo-cell img {
            width: 36px;
            height: 36px;
            display: block;
            margin-top: 4px;
        }

        .brand-title {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            line-height: 0.95;
            letter-spacing: 0.8px;
            margin: 0;
            padding: 0;
        }

        .brand-sub {
            font-size: 6.5px;
            color: #059669;
            font-weight: 600;
            margin-top: 1px;
            line-height: 1.0;
            padding: 0;
        }

        .meta-info {
            font-size: 6px;
            color: #64748b;
            margin-top: 1px;
            line-height: 1.0;
            font-weight: 400;
            padding: 0;
        }

        /* ── PENGATURAN REPEAT HEADER DI SETIAP HALAMAN ── */
        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        /* ── DATA TABLE (GARIS ABU-ABU NETRAL TIPIS & RAPI SEPERTI PESU PELUH) ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }

        .data-table th {
            background-color: #059669;
            color: #ffffff;
            font-size: 5.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 2.5px;
            border: 0.25pt solid #cbd5e1;
            text-align: center;
            vertical-align: middle;
            line-height: 1;
            white-space: nowrap !important;
        }

        .data-table td {
            font-size: 5.5px;
            padding: 3.5px 2.5px;
            border: 0.25pt solid #e2e8f0;
            vertical-align: middle;
            color: #1e293b;
            line-height: 1.25;
        }

        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .data-table tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        /* Lebar Kolom & Aturan Wrap */
        .col-no { width: 14px; text-align: center; white-space: nowrap !important; }
        .col-kode { width: 66px; font-weight: 600; color: #059669; text-align: center; white-space: nowrap !important; }
        .col-tgl { width: 54px; text-align: center; white-space: nowrap !important; }
        .col-ruangan { width: 75px; text-align: center; white-space: normal; word-wrap: break-word; }
        .col-pelapor { width: 62px; white-space: normal; word-wrap: break-word; }
        .col-sentimen { width: 44px; text-align: center; white-space: nowrap !important; }
        .col-kategori { width: 60px; text-align: center; white-space: normal; word-wrap: break-word; }
        .col-isi { width: auto; white-space: normal; word-wrap: break-word; }
        .col-status { width: 48px; text-align: center; white-space: nowrap !important; }
        .col-tindaklanjut { width: 85px; white-space: normal; word-wrap: break-word; }
        .col-lampiran { width: 36px; text-align: center; vertical-align: middle; white-space: nowrap !important; padding: 2px 1px !important; }

        /* Akses Warna Teks Status & Sentimen (Bold) */
        .text-green { color: #059669; font-weight: 700; }
        .text-blue { color: #2563eb; font-weight: 700; }
        .text-amber { color: #d97706; font-weight: 700; }
        .text-purple { color: #7c3aed; font-weight: 700; }
        .text-red { color: #dc2626; font-weight: 700; }
        .text-dash { text-align: center !important; color: #94a3b8; font-weight: 400; }

        /* Photos Layout Stacked */
        .photo-stack { text-align: center; margin: 0 auto; }
        .photo-box { margin-bottom: 2px; }
        .photo-box:last-child { margin-bottom: 0; }
        .photo-label { font-size: 4.5px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 1px; line-height: 1; text-align: center; }
        .photo-thumb {
            width: 17px;
            height: 17px;
            object-fit: cover;
            border-radius: 2px;
            border: 0.25pt solid #cbd5e1;
            margin: 0 auto;
            display: block;
        }
        .photo-dash { font-size: 5.5px; color: #94a3b8; font-weight: 400; line-height: 1; text-align: center; display: block; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 24px;
            color: #64748b;
            font-style: italic;
            font-size: 8px;
            border: 0.25pt solid #cbd5e1;
        }
    </style>
</head>
<body>

{{-- ═══════ HEADER IDENTITAS SIPUAS (KIRI ATAS TABEL - IDENTIK PESU PELUH) ═══════ --}}
<div class="pdf-header">
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if(isset($logoBase64) && $logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo">
                @elseif(isset($logoPath) && file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="brand-title">SIPUAS</div>
                <div class="brand-sub">Sistem Informasi Suara Pasien untuk Akuntabilitas Staf Rumah Sakit</div>
                <div class="meta-info">
                    Laporan Suara Pasien &mdash; @if(isset($roomName) && $roomName){{ $roomName }}@else Semua Ruangan / Unit @endif
                    &nbsp;|&nbsp; Generated at: {{ $exportedAt }}
                    @if(isset($startDate) && $startDate) &nbsp;|&nbsp; Periode: {{ $startDate }} - {{ $endDate ?: 'Sekarang' }} @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════ DATA TABLE ═══════ --}}
@if($reports->isEmpty())
    <div class="empty-state">Belum ada data laporan suara pasien yang sesuai kriteria filter.</div>
@else
<table class="data-table">
    <thead>
        <tr>
            <th class="col-no">NO</th>
            <th class="col-kode">NO. TIKET</th>
            <th class="col-tgl">TANGGAL</th>
            <th class="col-ruangan">RUANGAN / UNIT</th>
            <th class="col-pelapor">PELAPOR</th>
            <th class="col-sentimen">SENTIMEN</th>
            <th class="col-kategori">KATEGORI</th>
            <th class="col-isi">ULASAN / KELUHAN PASIEN</th>
            <th class="col-status">STATUS</th>
            <th class="col-tindaklanjut">TINDAK LANJUT KASI</th>
            <th class="col-lampiran">BUKTI</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reports as $i => $report)
        @php
            $roomLabel = $report->room?->name;
            if ($report->room && $report->room->location_floor) {
                $roomLabel .= ' (Lt. ' . $report->room->location_floor . ')';
            }

            // HASIL SELALU HURUF KAPITAL (SEPERTI PESU PELUH)
            $statusMap = [
                'VERIFIED' => ['label' => 'TERVERIFIKASI', 'class' => 'text-green'],
                'RESOLVED' => ['label' => 'SELESAI',       'class' => 'text-blue'],
                'PENDING'  => ['label' => 'PENDING',       'class' => 'text-amber'],
                'REJECTED' => ['label' => 'DITOLAK',       'class' => 'text-red'],
            ];
            $st = $statusMap[$report->status] ?? ['label' => strtoupper($report->status), 'class' => ''];

            $sentimentMap = [
                'POSITIF' => ['label' => 'POSITIF', 'class' => 'text-green'],
                'NEGATIF' => ['label' => 'NEGATIF', 'class' => 'text-red'],
                'NETRAL'  => ['label' => 'NETRAL',  'class' => 'text-dash'],
            ];
            $sn = $sentimentMap[$report->ai_sentiment] ?? ['label' => strtoupper($report->ai_sentiment ?: 'NETRAL'), 'class' => 'text-dash'];

            $tindakLanjut = $report->resolution_notes ?: $report->supervisor_notes;

            // Resolve image attachment candidate
            $resolveImg = function($attsList) {
                if (!$attsList || $attsList->count() === 0) return null;
                foreach ($attsList as $att) {
                    $rawPath = $att->file_path;
                    if (!$rawPath) continue;

                    if (str_starts_with($rawPath, 'data:image')) {
                        return $rawPath;
                    }

                    $normalized = str_replace('\\', '/', $rawPath);
                    $cleanPath = ltrim(str_replace(['/storage/', 'storage/', 'public/'], '', $normalized), '/');

                    $candidates = [
                        storage_path('app/public/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                        storage_path('app/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                        public_path('storage/' . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                        public_path(str_replace('/', DIRECTORY_SEPARATOR, $cleanPath)),
                        $rawPath,
                    ];

                    foreach ($candidates as $cand) {
                        if ($cand && file_exists($cand) && is_file($cand)) {
                            $ext = strtolower(pathinfo($cand, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'])) {
                                return $cand;
                            }
                        }
                    }
                }
                return null;
            };

            $reportImg = $resolveImg($report->attachments ?? collect());
        @endphp
        <tr>
            {{-- 1. NO --}}
            <td class="col-no">{{ $i + 1 }}</td>

            {{-- 2. KODE TIKET --}}
            <td class="col-kode">
                {{ $report->ticket_number }}
                @if($report->shift_info)
                    <div style="font-size: 4.5px; color: #64748b; font-weight: normal;">{{ $report->shift_info }}</div>
                @endif
            </td>

            {{-- 3. TANGGAL --}}
            <td class="col-tgl">{{ $report->created_at ? $report->created_at->format('d/m/Y H:i') : '-' }}</td>

            {{-- 4. RUANGAN --}}
            @if($roomLabel)
                <td class="col-ruangan">{{ $roomLabel }}</td>
            @else
                <td class="col-ruangan text-dash">-</td>
            @endif

            {{-- 5. PELAPOR --}}
            <td class="col-pelapor">
                <div>{{ $report->reporter_name ?: 'Anonim' }}</div>
                @if($report->reporter_phone)
                    <div style="font-size: 4.5px; color: #64748b;">{{ $report->reporter_phone }}</div>
                @endif
            </td>

            {{-- 6. SENTIMEN --}}
            <td class="col-sentimen">
                <span class="{{ $sn['class'] }}">{{ $sn['label'] }}</span>
            </td>

            {{-- 7. KATEGORI --}}
            @if($report->ai_category)
                <td class="col-kategori">{{ $report->ai_category }}</td>
            @else
                <td class="col-kategori text-dash">-</td>
            @endif

            {{-- 8. ULASAN / KELUHAN PASIEN --}}
            @if($report->isi_laporan)
                <td class="col-isi">{{ $report->isi_laporan }}</td>
            @else
                <td class="col-isi text-dash">-</td>
            @endif

            {{-- 9. STATUS --}}
            <td class="col-status">
                <span class="{{ $st['class'] }}">{{ $st['label'] }}</span>
            </td>

            {{-- 10. TINDAK LANJUT KASI --}}
            @if($tindakLanjut)
                <td class="col-tindaklanjut">
                    {{ $tindakLanjut }}
                    @if($report->verifier)
                        <div style="font-size: 4.5px; color: #64748b; font-weight: normal; margin-top: 1px;">Oleh: {{ $report->verifier->name }}</div>
                    @endif
                </td>
            @else
                <td class="col-tindaklanjut text-dash">-</td>
            @endif

            {{-- 11. BUKTI LAMPIRAN --}}
            <td class="col-lampiran">
                <div class="photo-stack">
                    <div class="photo-box">
                        <div class="photo-label">FOTO</div>
                        @if($reportImg)
                            <img src="{{ $reportImg }}" class="photo-thumb" alt="Foto">
                        @else
                            <span class="photo-dash">-</span>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

</body>
</html>
