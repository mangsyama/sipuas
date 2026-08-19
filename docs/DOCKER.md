# 🚀 Panduan Lengkap Perintah Terminal Docker — SIPUAS

Dokumen ini berisi daftar perintah terminal Docker (*Cheat Sheet*) untuk server production **SIPUAS**, termasuk panduan akses via Alamat IP Server dan peralihan ke Domain SSL resmi.

---

## 🌐 1. Akses Sistem (Mode Fleksibel: IP & SSL Ready)

Sistem SIPUAS saat ini sudah dikonfigurasi dalam mode **Dual Access**:
* **Akses via IP Server**: `http://IP_SERVER` (contoh: `http://103.19.230.110`) — Langsung terbuka lancar tanpa peringatan/layar merah di browser.
* **Akses via HTTPS**: `https://sipuas.badungkab.go.id` — Otomatis aktif dan aman jika dibuka via HTTPS.

### 🔒 Cara Mengaktifkan Paksa Redirect HTTPS (Jika Domain Sudah Dibuat):
Jika nanti domain `sipuas.badungkab.go.id` sudah aktif dari Kominfo dan Anda ingin semua akses HTTP otomatis dialihkan ke HTTPS, cukup:
1. Buka file `nginx/sipuas.conf`.
2. Hapus tanda pagar (`#`) pada blok redirect di bagian Port 80:
   ```nginx
   location / {
       return 301 https://$host$request_uri;
   }
   ```
3. Jalankan:
   ```bash
   docker compose exec nginx nginx -s reload
   ```

---

## ⚡ 2. Command Performa & Optimasi Kencang (Wajib Setelah Update Code)

Jalankan perintah ini agar sistem berjalan dengan kecepatan maksimal (Cache Nginx, Route, Config & Autoload PHP):

```bash
# A. Reload Nginx (Mengaktifkan config Nginx & Gzip baru)
docker compose exec nginx nginx -s reload

# B. Optimasi Cache Framework Laravel (Config, Route, View)
docker compose exec php php artisan optimize

# C. Optimasi Autoload Class PHP (Super Fast Class Loader)
docker compose exec php composer dump-autoload --optimize --classmap-authoritative

# D. Sekali Jalan (All-in-One Optimization Command)
docker compose exec php php artisan optimize && docker compose exec php composer dump-autoload --optimize
```

---

## 🏗️ 3. Command Deploy & Build Pertama Kali / Update Proyek

```bash
# Build dan jalankan seluruh container di background
docker compose up -d --build

# Cek status semua container (Pastikan berstatus 'Up')
docker compose ps
```

---

## 🔄 4. Command Restart Container

Gunakan jika Anda mengubah file `.env`, `sipuas.conf`, atau servis tertentu perlu direstart:

```bash
# Restart Nginx saja (Tanpa matikan container lain)
docker compose restart nginx

# Restart PHP-FPM saja
docker compose restart php

# Restart Reverb WebSocket
docker compose restart reverb

# Restart Queue Worker Notifikasi
docker compose restart queue-worker

# Restart Scheduler
docker compose restart scheduler

# Restart WA Gateway
docker compose restart wa-gateway

# Restart SELURUH Container Proyek
docker compose restart
```

---

## 📜 5. Command Cek Log Real-Time (Monitoring System)

Gunakan jika ingin melihat aktivitas atau melacak error di server:

```bash
# Cek log Nginx (Traffic web & error server)
docker compose logs -f nginx

# Cek log PHP (Error Laravel)
docker compose logs -f php

# Cek log Reverb WebSocket (Koneksi realtime)
docker compose logs -f reverb

# Cek log Queue Worker (Status notifikasi & background jobs)
docker compose logs -f queue-worker

# Cek log WA Gateway
docker compose logs -f wa-gateway

# Cek log SELURUH Container sekaligus
docker compose logs -f --tail=100
```

---

## 🛠️ 6. Command Maintenance & Perintah Artisan

Gunakan jika perlu menjalankan perintah Laravel Artisan di dalam container server:

```bash
# Jalankan Migrasi Database
docker compose exec php php artisan migrate --force

# Hapus Semua Cache Laravel
docker compose exec php php artisan optimize:clear

# Hubungkan Storage Public Link (Jika gambar storage tidak muncul)
docker compose exec php php artisan storage:link

# Masuk ke dalam Shell Terminal Container PHP (Interactive Bash)
docker compose exec php bash
```

---

## 🛑 7. Command Menghentikan Proyek

```bash
# Matikan seluruh container (Data tetap aman)
docker compose down

# Matikan seluruh container + hapus volume temporary
docker compose down -v
```

---

## 🧹 8. Command Bersih-Bersih Disk Server (Docker Cleanup)

Jika penyimpanan server terasa penuh karena sisa image lama:

```bash
# Hapus image/container bekas yang tidak terpakai
docker system prune -f
```
