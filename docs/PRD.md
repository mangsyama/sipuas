# **Dokumen Kebutuhan Produk (PRD)**

## **SI-PUAS: Sistem Informasi Suara Pasien untuk Akuntabilitas Staf (Versi Mandiri)**

## **1\. Ringkasan Produk (*Product Overview*)**

**SI-PUAS** adalah aplikasi web berbasis kecerdasan buatan (AI) yang memproses umpan balik (keluhan dan pujian) dari pasien atau keluarga pasien secara langsung (*real-time*).  
Sistem ini mentransformasi laporan mentah pasien menjadi indikator kinerja terukur (*KPI / Digital Logbook*) bagi staf rumah sakit, sekaligus menyediakan dasbor pemantauan tingkat manajerial (*Executive Dashboard*) bagi jimpinan tanpa memerlukan integrasi kompleks ke SIMRS (*standalone*).

### **Tujuan Utama**

> 1. **Mempermudah Pasien:** Menyediakan sarana pelaporan tanpa registrasi/login (*Guest Flow*).  
> 2. **Otomatisasi Analisis:** Memanfaatkan AI/NLP untuk analisis sentimen dan pengkategorian aduan secara otomatis.  
> 3. **Akuntabilitas Berjenjang:** Menghubungkan aduan langsung ke proses penilaian kinerja oleh Kepala Seksi (Kasi) / Kepala Ruangan.  
> 4. **Pengawasan Manajerial:** Memberikan visibilitas penuh kepada Kepala Bidang (Kabid) Pelayanan terkait tren keluhan unit dan kecepatan respons supervisor.

## **2\. Peran Pengguna (*User Roles & Access Level*)**

| Peran Pengguna | Tingkat Akses | Autentikasi (Login) | Deskripsi Tugas |
| :---- | :---- | :---- | :---- |
| **Pasien / Keluarga** | Public Guest | **Tidak Perlu** | Mengirim laporan keluhan/pujian via QR Code dan menerima bukti receipt laporan. |
| **Kepala Seksi / Ruangan (Kasi)** | Supervisor Unit | **Wajib Login** | Menerima notifikasi, memverifikasi kesesuaian *shift* staf, serta mengeksekusi pemotongan/penambahan poin KPI. |
| **Kabid Pelayanan** | Executive Management | **Wajib Login** | Memantau peta zona merah unit, tingkat responsivitas Kasi, dan *leaderboard* kinerja staf. |
| **Administrator System** | System Owner | **Wajib Login** | Mengelola master data (unit, akun Kasi/Kabid, pendaftaran staf, bobot KPI). |

## **3\. Arsitektur Halaman & Struktur Aplikasi (*Sitemap*)**

Aplikasi SI-PUAS  
├── 🌐 Area Publik (Tanpa Login)  
│   ├── Halaman 1: Formulir Laporan Pasien (via QR Code)  
│   └── Halaman 2: Konfirmasi / Struk Laporan Digital  
│  
└── 🔒 Portal Internal / Admin (Wajib Login)  
    ├── Halaman 3: Portal Login  
    │  
    ├── 👨‍💼 Modul Kepala Seksi (Kasi)  
    │   ├── Halaman 4: Dashboard & Feed Aduan Masuk  
    │   ├── Halaman 5: Detail Laporan & Verifikasi Shift Staf  
    │   └── Halaman 6: Logbook Kinerja Staf Unit  
    │  
    └── 👑 Modul Kabid Pelayanan (Executive)  
        ├── Halaman 7: Executive Command Center (Dashboard)  
        ├── Halaman 8: Laporan Responsivitas Kasi  
        └── Halaman 9: Leaderboard & Peringkat Staf

## **4\. Rincian Fitur & Spesifikasi Halaman**

### **A. Area Publik (Pasien)**

#### **1\. Formulir Laporan Pasien (/report)**

> * **Akses:** Akses terbuka via pemindaian QR Code di lokasi pelayanan.  
> * **Fitur Utama:**  
  * **Auto-Select Unit:** URL parameter membawa ID unit (contoh: sipuas.rs.go.id/report?unit=FARMASI), otomatis memilih unit tujuan.  
  * **Input Keluhan:** *Textarea* bebas bagi pasien untuk mengetikkan laporan.  
  * **Keamanan & Proteksi Spam:** Penanganan *Rate Limiting* berbasis IP/Session serta *Invisible reCAPTCHA* untuk mencegah serangan *bot*.  
  * **Payload Data Terkirim:**  
    {  
      "unit\_id": "FARMASI",  
      "isi\_laporan": "Ambil obat lama sekali, sudah antre 2 jam petugasnya malah asyik ngobrol.",  
      "timestamp": "2026-08-06 14:30:00"  
    }

#### **2\. Konfirmasi Laporan Digital (/report/success)**

> * **Akses:** Tampil otomatis setelah submit laporan berhasil.  
> * **Fitur Utama:**  
  * Penampil ID Laporan Unik (contoh: LP-2026-08-001).  
  * Informasi stempel waktu dan status penerimaan laporan.  
  * Penyimpanan ID Laporan di Local Storage browser HP pasien untuk pelacakan mandiri tanpa login.

### **B. Portal Internal (Kasi & Eksekutif)**

#### **3\. Portal Login (/login)**

> * Form otentikasi standar (Username/Email & Password).  
> * *Role-Based Redirection:* Mengarahkan Kasi ke Dashboard Kasi dan Kabid ke Executive Dashboard.

#### **4\. Dashboard & Feed Aduan Kasi (/kasi/dashboard)**

> * **Pengguna:** Kepala Seksi / Kepala Ruangan.  
> * **Fitur Utama:**  
  * **Kartu Ringkasan:** Total Laporan Masuk, Laporan Pending (Belum Divalidasi), Total Poin Dipotong/Ditambah.  
  * **Tabel Feed Laporan:**  
    * ID Laporan & Waktu Kejadian.  
    * Potongan Teks Keluhan.  
    * **Tag Hasil Analisis AI:** Label Sentimen (*Negatif/Positif/Netral*) dan Kategori (*Disiplin, Waktu Tunggu, Sarpras, Pelayanan*).  
    * Status Laporan: Perlu Verifikasi atau Selesai Divalidasi.  
  * Tombol Aksi: "Verifikasi Laporan".

#### **5\. Detail Laporan & Verifikasi Shift Staf (/kasi/verify/:id)**

> * **Pengguna:** Kepala Seksi / Kepala Ruangan.  
> * **Fitur Utama:**  
  * **Rincian Laporan & AI Insight:** Menampilkan teks lengkap aduan dan rekomendasi kategori dari AI.  
  * **Panel Pencocokan Shift (Manual/Digital):**  
    * Tampilan data *shift* kerja internal pada hari dan jam kejadian.  
    * Multi-select checkbox untuk memilih staf yang bertugas (contoh: \[x\] Petugas A, \[x\] Petugas B).  
  * **Eksekusi Poin KPI:**  
    * Pilihan Aksi: Pemotongan Poin (Komplain) atau Penambahan Poin (Pujian).  
    * Value Poin Default: ![][image1] poin (komplain) atau ![][image2] poin (pujian).  
    * Form catatan supervisor.  
  * **Tombol Eksekusi:** "Validasi & Update Logbook Staf".

#### **6\. Executive Command Center (/executive/dashboard)**

> * **Pengguna:** Kabid Pelayanan.  
> * **Fitur Utama:**  
  * **Filter Periode:** 7 Hari Terakhir, 30 Hari Terakhir, Custom Date Range.  
  * **Peta Zona Merah Unit:** Grafik batang/donut yang memperlihatkan unit dengan tingkat keluhan tertinggi.  
  * **Persentase Tren Sentimen:** Diagram lingkaran rasio aduan positif vs negatif.  
  * **Meting Metrik Utama:** Total aduan RS, rata-rata kecepatan respons Kasi, dan akumulasi poin KPI.

#### **7\. Laporan Responsivitas Kasi (/executive/kasi-responsiveness)**

> * **Pengguna:** Kabid Pelayanan.  
> * **Fitur Utama:**  
  * Tabel pemantauan akuntabilitas seluruh Kasi/Kepala Ruangan.  
  * Kolom Data: Nama Kasi, Unit Kerja, Total Komplain Masuk, Komplain Divalidasi, Pending/Diabaikan, Tingkat Respons (%).  
  * Formula Tingkat Respons:  
    ![][image3]  
  * Indikator Peringatan (*Warning Highlight*) untuk Kasi dengan skor responsivitas di bawah ambang batas (misal: ![][image4]).

#### **8\. Leaderboard & Peringkat Kinerja Staf (/executive/leaderboard)**

> * **Pengguna:** Kabid Pelayanan.  
> * **Fitur Utama:**  
  * **Top Performers (5 Staf Teratas):** Staf dengan poin KPI tertinggi berdasarkan akumulasi pujian pasien.  
  * **Bottom Performers (5 Staf Terbawah):** Staf dengan pemotongan poin terbanyak untuk indikasi pembinaan teknis.  
  * Fitur ekspor laporan ke format PDF/Excel.

## **5\. Spesifikasi Alur Kerja AI & Data**

\[Input Teks Pasien\]  
       │  
       ▼  
\[Mesin AI / Natural Language Processing (NLP)\]  
   ├── 1\. Klasifikasi Sentimen (Positif / Negatif / Netral)  
   └── 2\. Ekstraksi Kata Kunci & Kategori (Misal: "Lama" \-\> Waktu Tunggu)  
       │  
       ▼  
\[Routing Notification Engine\]  
   └── Mengirim Push Notification / Alert ke Dashboard & Mobile Kasi Terkait  
       │  
       ▼  
\[Verifikasi Manual & Validasi oleh Kasi\]  
   └── Pembaruan Poin KPI Otomatis di Digital Logbook Staf Terkait

## **6\. Persyaratan Non-Fungsional (*Non-Functional Requirements*)**

> 1. **Performa:** Waktu respons submit formulir publik ![][image5] detik.  
> 2. **Skalabilitas:** Mampu menangani hingga 1.000 aduan per hari tanpa degradasi performa.  
> 3. **Kompatibilitas:** Tampilan formulir publik dioptimalkan penuh untuk *Mobile Web View* (iOS & Android).  
> 4. **Keamanan:** Menerapkan HTTPS encryption, *sanitasi input* untuk mencegah SQL Injection / XSS, serta batasan request per IP address.