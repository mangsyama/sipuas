# 🚀 Panduan Lengkap Perintah Terminal Docker — SIPUAS

Dokumen ini berisi daftar perintah terminal Docker (*Cheat Sheet*) untuk server production **SIPUAS**, termasuk panduan akses via Alamat IP Server dan peralihan ke Domain SSL resmi.

---

## 🌐 1. Akses Sistem Production (Domain Resmi SSL HTTPS Port 443)

Sistem SIPUAS di server production berjalan persis seperti **Pesu Peluh** dengan konfigurasi SSL penuh pada **Port 443**:
* **Akses via Domain Resmi (HTTPS)**: `https://sipuas.badungkab.go.id`
* **Akses via IP Publik / Internal**: Diblokir otomatis oleh Nginx (`return 444;` tanpa respon / drop connection) untuk keamanan server (*security hardening*).

### 🔒 Sertifikat SSL Aktif (Kominfo Badung Wildcard):
* Sertifikat: `/etc/nginx/ssl/all_in_one.crt`
* Private Key: `/etc/nginx/ssl/badungkab_go_id.key`
* Port Container: `443:443` (HTTPS SSL Standar Resmi)
* WebSocket Reverb: Proxy otomatis via `/app` & `/apps` ke `reverb:8080` (WSS Secure)

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

---

## 📖 9. Standar Arsitektur Produksi & Troubleshooting

Untuk panduan lengkap mengenai aturan database Microsoft SQL Server (`sqlsrv`), integrasi aman WhatsApp Gateway, konfigurasi CSRF form publik, dan pencegahan error 500 saat rilis update, silakan pelajari dokumen:
👉 **[STANDAR_PRODUKSI_DAN_SINKRONISASI.md](./STANDAR_PRODUKSI_DAN_SINKRONISASI.md)**

