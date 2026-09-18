# 🛡️ Standar Arsitektur Produksi, Aturan SQL Server, & Panduan Troubleshooting — SIPUAS

Dokumen ini adalah **panduan acuan resmi (Standing Operational Standards)** pengembangan dan pemeliharaan sistem **SIPUAS**. Seluruh developer dan sysadmin wajib mengacu pada dokumen ini untuk mencegah terulangnya insiden *500 Server Error* dan ketidaksinkronan antara lingkungan *Development* (SQLite/Local) dengan *Production* (Docker + Microsoft SQL Server + Nginx SSL).

---

## 📌 1. Prinsip Utama Deploy & Modifikasi Kode

> [!CAUTION]
> **DILARANG KERAS** melakukan edit kode langsung (*hot-patching*) di server production tanpa melalui git tracking.
> Modifikasi langsung di server rawan menimpa file controller/channel, memicu bentrok deklarasi class, dan memutus rantai sinkronisasi git.

* **Alur Perubahan:** `Local Dev / Branch` ➔ `Uji Test Suite (php artisan test)` ➔ `Build Frontend (npm run build)` ➔ `Git Commit & Push` ➔ `Git Pull di Production` ➔ `Restart / Optimize Service`.

---

## 🗄️ 2. Aturan Wajib Database Microsoft SQL Server (`sqlsrv`)

Lingkungan production SIPUAS menggunakan **Microsoft SQL Server (`sqlsrv`)** yang memiliki aturan validasi dan tipe data yang jauh lebih ketat dibandingkan MySQL / SQLite.

### A. Larangan Tipe Data `mediumText` pada Migrasi
* **Aturan:** Jangan pernah menggunakan `$table->mediumText()` di file migrasi. SQL Server tidak memiliki tipe data `mediumText` dan akan melempar error:
  `SQLSTATE[42000]: Cannot find data type MEDIUMTEXT`.
* **Solusi Wajib:** Gunakan `$table->text()` yang otomatis dipetakan menjadi `nvarchar(max)` oleh driver SQL Server di Laravel.

### B. Proteksi Truncation Error (Panjang Karakter)
* **Aturan:** SQL Server menerapkan mode strict secara default. Jika panjang string yang di-insert melebihi batas kolom (misal insert 25 karakter ke `varchar(20)`), SQL Server akan langsung memutus transaksi dengan error fatal:
  `SQLSTATE[22001]: String or binary data would be truncated`.
* **Solusi Wajib:**
  * Potong nilai teks dinamis (terutama output AI seperti `ai_confidence`, `ai_category`, `reporter_name`) dengan `mb_substr($val, 0, limit)` sebelum disimpan.
  * Di `ReportController::store()`, pertahankan mekanisme **Primary Attempt + Minimal Fallback Insert** agar laporan aduan masyarakat selalu tersimpan di database dalam kondisi darurat apa pun.

### C. Type Conversion Mismatch (Pencarian ID Skalar)
* **Aturan:** Jangan pernah menjalankan klausa `WHERE id = $value` jika `$value` bisa berupa string (misal kode tiket `LP-2026-0001` atau `[object Object]`). SQL Server akan berusaha mengonversi string ke integer dan melempar error:
  `Conversion failed when converting the varchar value '...' to data type int`.
* **Solusi Wajib:**
  ```php
  // BENAR: Cek apakah ID bernilai numerik sebelum query ke kolom integer 'id'
  if (is_numeric($id)) {
      $query->where(function ($q) use ($id) {
          $q->where('ticket_number', (string)$id)->orWhere('id', (int)$id);
      });
  } else {
      $query->where('ticket_number', (string)$id);
  }
  ```

### D. Tanda Kutip ANSI SQL: Jangan Gunakan Double Quote untuk String Literal
* **Aturan:** Dalam query `selectRaw` atau `whereRaw`, gunakan **single quote (`'`)** untuk nilai teks. Double quote (`"`) di SQL Server dianggap sebagai identifier nama kolom.
* **Contoh:**
  * ❌ `SUM(CASE WHEN ai_sentiment = "POSITIF" THEN 1 ELSE 0 END)` (SQL Server mencari kolom `[POSITIF]`)
  * ✅ `SUM(CASE WHEN ai_sentiment = 'POSITIF' THEN 1 ELSE 0 END)`

### E. Hindari Alias Fungsi dalam `ORDER BY`
* **Aturan:** SQL Server melarang pengurutan berdasarkan alias fungsi agregat yang ambigu dalam klausa `GROUP BY`.
  * ❌ `->selectRaw('ai_category, count(*) as count')->groupBy('ai_category')->orderByDesc('count')`
  * ✅ `->selectRaw('ai_category, count(*) as count')->groupBy('ai_category')->orderByRaw('COUNT(*) DESC')`

### F. Struktur Skema Riil Tabel `users`
* Tabel `users` di SIPUAS **hanya memiliki kolom `room_id`** (relasi foreign key ke tabel `rooms`).
* ❌ **JANGAN** membuat query `->where('room_id', $val)->orWhere('unit_id', $val)` karena kolom `unit_id` tidak ada di database dan akan memicu error `Invalid column name 'unit_id'`.

---

## 📲 3. Standar Integrasi WhatsApp Gateway di Docker

### A. Wajib Non-blocking (Queue Asinkron)
* Pengiriman notifikasi WhatsApp ke petugas Kasi maupun masyarakat pelapor **TIDAK BOLEH** dijalankan secara sinkron di siklus HTTP request form.
* Panggilan HTTP ke gateway WhatsApp wajib dimasukkan ke dalam antrean background job:
  ```php
  try {
      dispatch(function () use ($room, $ticketNumber, $validated, $sentiment) {
          WaGatewayChannel::sendDirect($phone, $message);
      });
  } catch (\Throwable $e) {
      Log::warning('Dispatch WA notice: ' . $e->getMessage());
  }
  ```

### B. Resolusi Host Jaringan Docker vs Localhost
* **Masalah:** Di file `.env`, `WA_LOCAL_URL` diisi `http://127.0.0.1:3000/send`. Di dalam kontainer Docker `sipuas-php`, IP `127.0.0.1` mengarah ke dirinya sendiri, sehingga koneksi ke microservice WhatsApp pasti gagal (`cURL error 7`).
* **Solusi Baku:** Selalu gunakan helper terpusat di `WaGatewayChannel`:
  ```php
  // Menggunakan helper statis (otomatis mengarahkan ke http://wa-gateway:3000 jika di Docker)
  WaGatewayChannel::sendDirect($phoneNumber, $messageContent);
  ```

---

## 👥 4. Form Publik Masyarakat (Kiosk / QR Code) & Toleransi Sesi

1. **Pengecualian CSRF di [bootstrap/app.php](file:///c:/Project/sipuas/bootstrap/app.php):**
   * Form publik `/report` dan `/report/*` wajib dikecualikan dari `validateCsrfTokens`.
   * **Alasan:** Pasien/masyarakat memindai QR code dari smartphone dan sering membutuhkan waktu 3–10 menit untuk mengetik laporan serta memotret bukti. Sesi browser sering habis/tidur, sehingga penegakan CSRF ketat akan menyebabkan kegagalan submit (Error 419).
2. **Sanitasi Komponen Vue [Create.vue](file:///c:/Project/sipuas/resources/js/Pages/Report/Create.vue):**
   * Sebelum memasukkan data pilihan ruangan ke `FormData`, selalu pastikan nilai yang diambil adalah ID skalar, bukan object referensi dropdown:
     ```javascript
     const rawUnit = (typeof form.value.unit_id === 'object' && form.value.unit_id !== null) 
         ? (form.value.unit_id.id || form.value.unit_id.name || '') 
         : (form.value.unit_id || '');
     formData.append('unit_id', rawUnit);
     formData.append('room_id', rawUnit);
     ```
3. **Alur Lacak Laporan (Track) & Privasi Catatan Internal:**
   * Alur tracking publik disederhanakan menjadi **3 Langkah Nyata**:
     1. *Laporan Masuk & Diterima Sistem*
     2. *Verifikasi Kepala Ruangan / KASI*
     3. *Selesai Ditangani & Ditutup* (Begitu Kasi melakukan verifikasi, status `VERIFIED` dihitung selesai penuh).
   * **Privasi Catatan Internal:** Kolom `supervisor_notes` adalah catatan pembinaan manajerial internal RS (Kasi ke staf/direksi), **DILARANG** ditampilkan ke publik masyarakat. Response tracking di `ReportController::track()` hanya menyajikan status dan timestamp penanganan.

---

## 🌐 5. Konfigurasi Nginx & WebSocket Reverb

1. **`fastcgi_intercept_errors off;` di [nginx/sipuas.conf](file:///c:/Project/sipuas/nginx/sipuas.conf):**
   * Wajib disetel `off` agar pesan kesalahan format JSON dari Laravel diteruskan utuh ke frontend Vue dan tidak digantikan oleh halaman HTML default Nginx.
2. **WebSocket Reverb Port di HTTPS:**
   * Di file [resources/js/app.js](file:///c:/Project/sipuas/resources/js/app.js), port koneksi WebSocket pada protokol HTTPS wajib disetel ke `wssPort: 443`. Nginx di server bertindak sebagai reverse proxy yang meneruskan path `/app/` dan `/apps/` ke `reverb:8080`.

---

## ✅ 6. Checklist Verifikasi Sebelum Rilis Update

Jalankan perintah ini di lingkungan development sebelum melakukan push/sinkronisasi ke server production:

```bash
# 1. Jalankan seluruh automated feature & unit tests (Wajib lulus 100%)
php artisan test

# 2. Periksa apakah rute dan controller tidak ada konflik duplikasi class
php artisan route:list

# 3. Jalankan kompilasi aset frontend Vite
npm run build

# 4. Di server production (setelah git pull):
docker compose exec php php artisan optimize:clear
docker compose exec php php artisan optimize
docker compose restart queue-worker
```
