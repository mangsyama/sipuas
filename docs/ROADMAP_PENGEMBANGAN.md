# Roadmap & Rencana Pengembangan Lanjutan (Backlog) SIPUAS
**Sistem Informasi Suara Pasien untuk Akuntabilitas Staf**  
*Terakhir Diperbarui: September 2026*

Dokumen ini berfungsi sebagai pengingat, panduan, dan acuan teknis mengenai bagian-bagian sistem yang **belum selesai** (*pending*), masih berupa *placeholder*, atau perlu disempurnakan pada aplikasi **SIPUAS**.

---

## 📌 1. Ringkasan Status Sistem Saat Ini

Aplikasi SIPUAS telah memiliki fondasi utama yang sudah berjalan:
- [x] **Formulir Publik Pasien (`/report`)**: Laporan instan tanpa login, pemilihan unit/ruangan, upload bukti foto/video, serta klasifikasi sentimen & kategori otomatis berbasis AI (Gemini/Groq/OpenAI).
- [x] **Tanda Terima Digital (`/report/success`)**: Penomoran tiket unik (`LP-YYYY-MM-XXXX`) dan penyimpanan tiket di penyimpanan lokal (*Local Storage*).
- [x] **Pelacakan Status Aduan (`/report/track`)**: Pengecekan progres tindak lanjut pengaduan oleh pasien secara mandiri.
- [x] **Modul Kasi / Kepala Ruangan (`/kasi/*`)**: Dashboard antrean laporan, verifikasi shift staf bertugas, penambahan/pemotongan poin KPI, dan Digital Logbook staf.
- [x] **Modul Kabid Pelayanan (`/executive/*`)**: Command Center eksekutif, analisis sentimen RS, pemantauan zona merah unit, responsivitas Kasi, dan Leaderboard staf.
- [x] **Modul Staf Pelayanan (`/staff/*`)**: Presensi kehadiran dinas serta Dashboard KPI personal.
- [x] **Master Data & Konfigurasi**: Persetujuan pendaftaran akun baru, manajemen pengguna, master data ruangan, pengaturan AI multi-provider, dan WhatsApp Gateway (Baileys).

---

## 🚀 2. Daftar Fitur & Bagian yang Belum Selesai (Pending Backlog)

---

### 🏷️ Prioritas 1: Generator & Template Siap Cetak QR Code Ruangan (`/admin/qr-generator`) — [SELESAI ✅]
* **Status:** Sudah Terimplementasi Lengkap & Terintegrasi di menu *System / Integrasi*.
* **Fitur yang Tersedia:**
  - Dynamic URL origin otomatis (`window.location.origin`).
  - Mode **Link Global RS** (`/report`) & Mode **Ruangan Spesifik (Auto-Select)** (`/report?room_id=xx`).
  - Live ultra-crisp QR canvas (1200px) dengan logo SIPUAS resmi dan pilihan skema warna / custom hex picker.
  - Salin tautan cepat & tombol unduh gambar PNG.
  - Pratinjau dan cetak poster Standee Akrilik Meja / A4 dengan template resmi RS & panduan 3 langkah scan.
* **Rincian yang Perlu Dibuat:**
  1. **Halaman Manajemen QR Code (`/admin/qr-generator`)**:
     - Tabel seluruh unit/ruangan pelayanan aktif.
     - Pratinjau (*preview*) QR Code per ruangan secara instan.
     - Tombol unduh file gambar QR Code (PNG/SVG).
  2. **Template Desain Siap Cetak (Printable Standee / Sticker)**:
     - Tata letak cetak ramah ukuran kertas A4, A5, dan stiker akrilik meja (10x15 cm).
     - Memuat identitas visual rumah sakit (Kop/Logo RS, judul *"Sampaikan Suara & Penilaian Pelayanan Anda"*).
     - Panduan singkat 3 langkah bagi pasien (*1. Pindai QR -> 2. Tulis Masukan -> 3. Kirim*).
     - Tombol *"Cetak Semua"* atau *"Cetak Ruangan Terpilih"* via print preview browser (`window.print()` / PDF export).

---

### 📊 Prioritas 2: Ekspor Laporan Rekapitulasi ke PDF & Excel (Modul Kabid & Kasi) — [SELESAI ✅]
* **Status:** Sudah Terimplementasi Lengkap & Terintegrasi di menu *Modul Kabid* (`/reports`).
* **Fitur yang Tersedia:**
  - **Halaman Pusat Rekapitulasi (`/reports`)**: Menampilkan ringkasan metrik (Total Laporan, Apresiasi Positif, Keluhan Negatif, Terverifikasi), filter rentang tanggal (Flatpickr ID), ruangan RS, sentimen AI, kategori AI, status laporan, shift pelayanan, dan pencarian kata kunci.
  - **Ekspor Dokumen PDF Resmi (A4 Landscape)**: Desain identik dengan Pesupeluh lengkap dengan logo RS, kop instansi resmi, ringkasan metrik periode, badge sentimen & status, catatan tindak lanjut Kasi/Supervisor, dan kolom tanda tangan Kabid.
  - **Ekspor Berkas Excel (.xlsx)**: Menggunakan Maatwebsite Excel dengan header warna hijau emerald, auto size kolom, format tanggal dan waktu, serta detail atribut laporan lengkap.
  - **Ekspor Berkas CSV (.csv)**: Data mentah siap impor/olah ke Google Sheets atau software analitik lainnya.
  - **Pratinjau Data Tabel & Paginasi**: Tabel responsif dengan status badge berwarna, rincian ruangan/lantai, dan kontrol paginasi navigasi.

---

### 💬 Prioritas 3: Notifikasi WhatsApp Otomatis ke Pasien (Pemberitahuan Hasil Tindak Lanjut)
* **Status Saat Ini:**
  WhatsApp Gateway sudah dapat mengirimkan peringatan siaga ke Kasi saat ada laporan baru. Namun, **pasien yang mencantumkan nomor telepon belum menerima notifikasi otomatis** ketika laporannya telah ditindaklanjuti.
* **Mengapa Fitur Ini Penting:**
  Meningkatkan kepercayaan dan kepuasan pasien (*patient engagement*). Pasien merasa suaranya benar-benar didengar dan diselesaikan oleh pihak rumah sakit tanpa harus membuka web pelacak secara berulang-ulang.
* **Rincian yang Perlu Dibuat:**
  1. Integrasi pada `KasiController::processVerification`:
     - Jika `reporter_phone` terisi dan valid, picu pengiriman pesan via `WaGatewayChannel`.
  2. Format pesan ramah dan informatif:
     > *"Halo Bpk/Ibu [Nama], terima kasih telah membantu meningkatkan mutu pelayanan RS. Laporan Anda dengan nomor tiket *#[No Tiket]* terkait *[Nama Ruangan]* telah selesai diverifikasi dengan tindak lanjut resmi:*  
     > *'Catatan tindak lanjut dari Kepala Ruangan...'*  
     > *Pantau perkembangan lengkap laporan Anda di: [Link Lacak Tiket]"*

---

### ⏱️ Prioritas 4: Kalkulasi Real Data SLA, Filter Periode Dinamis, & Dashboard Manajemen Modern (Kabid & Kasi) — [SELESAI ✅]
* **Status:** Sudah Terimplementasi Lengkap & Terintegrasi pada Modul Kabid (`/executive/dashboard`, `/executive/kasi-responsiveness`) dan Modul Kasi (`/kasi/dashboard`).
* **Fitur yang Tersedia:**
  - **Kalkulasi Data Riil SLA (Service Level Agreement)**:
    - Menghitung durasi selisih jam/menit riil antara waktu aduan masuk (`created_at`) dan diverifikasi (`verified_at`).
    - Menghapus simulasi `mt_rand` pada `KabidController.php`.
    - Persentase kepatuhan SLA (< 24 jam) rumah sakit dan masing-masing unit kerja secara akurat.
  - **Filter Periode Dinamis & Unit Terintegrasi**:
    - Filter cepat: *Hari Ini (`today`)*, *7 Hari Terakhir (`7d`)*, *30 Hari Terakhir (`30d`)*, *Bulan Ini (`this_month`)*, *Tahun Ini (`this_year`)*, *Semua*, serta *Rentang Tanggal Khusus (Custom Date Range Modal)*.
    - Sinkronisasi realtime ke seluruh indikator metrik kartu, tabel akuntabilitas, dan visualisasi grafik.
  - **Suite Visualisasi Grafik Manajemen Eksekutif (Chart.js)**:
    - **Grafik Tren Garis/Area (TrendAreaChart)**: Volume suara pasien masuk vs laporan selesai diverifikasi harian/berkala dengan kurva halus & gradasi warna modern.
    - **Grafik Donut Sentimen Pasien (SentimentDonutChart)**: Proporsi sentimen Positif, Negatif, dan Netral beserta label indeks kepuasan di tengah.
    - **Grafik Batang Kategori Masalah (CategoryBarChart)**: Perbandingan keluhan vs pujian per kategori masalah rumah sakit (Sikap Staf, Waktu Tunggu, Sarana, dll).
    - **Peta Zona Risiko Unit (Red Zone Matrix)**: Peringkat ruangan berisiko komplain tinggi dengan status badge (Merah/Kuning/Hijau) dan waktu respons riil.
  - **Dashboard Manajemen Kasi yang Ditingkatkan**:
    - Panel analitik unit kerja (Grafik tren 7 hari unit & donut sentimen ruangan).
    - Peringatan dini (*Urgent SLA Alert Banner*) jika ada tiket pending melebihi 24 jam.
    - Indikator SLA Timer pada setiap tiket di antrean tabel dan mode kartu.

---

### 🧹 Prioritas 5: Pembersihan & Perapian Route / Menu Placeholder
* **Status Saat Ini:**
  Pada `routes/web.php` masih terdapat sisa route warisan (*legacy / dummy*) yang dialihkan ke dashboard (`services.*`, `technicians.*`, `reports-management.*`, `service-management.*`).
* **Mengapa Fitur Ini Penting:**
  Mencegah kebingungan pengembang dan menjaga kerapian basis kode (*clean code*), serta memastikan seluruh tautan sidebar dan breadcrumb hanya memuat fitur aktif SIPUAS.
* **Rincian yang Perlu Dibuat:**
  - Menghapus route dummy yang tidak digunakan lagi dari `routes/web.php`.
  - Memastikan *sidebar* di `AuthenticatedLayout.vue` bersih dari referensi route yang sudah usang.

---

## 📋 3. Rangkuman Matriks Prioritas

| Fitur / Modul | Tingkat Prioritas | Dampak Operasional | Tingkat Kesulitan |
| :--- | :---: | :---: | :---: |
| **1. Generator & Cetak QR Code Ruangan** | 🔴 Tinggi | Kritis untuk implementasi lapangan | Menengah |
| **2. Ekspor PDF/Excel (Kabid & Kasi)** | 🔴 Tinggi | Kritis untuk pelaporan pimpinan | Menengah |
| **3. Notifikasi WA Hasil Laporan ke Pasien** | 🟡 Sedang | Meningkatkan kepuasan pasien | Mudah - Menengah |
| **4. Real SLA & Filter Tanggal Kabid** | 🟡 Sedang | Akurasi analisis manajemen | Mudah |
| **5. Cleanup Route & Menu Placeholder** | 🟢 Rendah | Kebersihan kode & arsitektur | Sangat Mudah |

---

> *Catatan: Dokumen ini dapat diperbarui sewaktu-waktu seiring dengan penambahan kebutuhan dan penyelesaian fitur.*
