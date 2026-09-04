# 🚀 Panduan Lengkap Perintah Terminal Docker — SIPUAS

Dokumen ini berisi daftar perintah terminal Docker (*Cheat Sheet*) untuk server production **SIPUAS**, termasuk panduan akses via Alamat IP Server dan peralihan ke Domain SSL resmi.

---

## 🌐 1. Akses Sistem (Production HTTP Port 8081)

Sistem SIPUAS di server production berjalan via **HTTP Port 8081** (tanpa SSL):
* **Akses via IP Publik**: `http://103.19.230.110:8081`
* **Akses via IP Internal**: `http://10.10.30.3:8081`
* **Akses via Domain**: `http://sipuas.badungkab.go.id:8081`

### 🔒 Persiapan Jika Kelak Mengaktifkan SSL (HTTPS Port 443):
Jika sertifikat resmi Kominfo untuk domain `sipuas.badungkab.go.id` sudah siap:
1. Masukkan sertifikat (`all_in_one.crt` & `badungkab_go_id.key`) ke folder `ssl/`.
2. Buka `docker-compose.yml`, aktifkan kembali `- "443:443"` dan volume mount `- ./ssl:/etc/nginx/ssl:ro`.
3. Tambahkan server block `listen 443 ssl;` pada `nginx/sipuas.conf`.

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
