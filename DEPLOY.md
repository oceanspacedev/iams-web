# Panduan Deployment Aplikasi Audit-Flow (IAMS) Menggunakan Docker

Dokumentasi ini menjelaskan langkah-langkah mudah untuk mendeploy aplikasi **Audit-Flow** ke server VPS (Ubuntu / Debian / CentOS) atau lingkungan lokal menggunakan Docker dan Docker Compose.

---

## Prasyarat Server
Pastikan server Anda telah terpasang:
1. **Git**
2. **Docker Engine** (versi 24.0+)
3. **Docker Compose** (versi v2+)

> **Tips Cepat Install Docker di Ubuntu/Debian (jika belum ada):**
> ```bash
> curl -fsSL https://get.docker.com -o get-docker.sh
> sudo sh get-docker.sh
> ```

---

## Langkah 1: Clone Repository ke Server

Masuk ke direktori server Anda dan clone project:
```bash
git clone <URL_REPOSITORY_ANDA> /var/www/audit-flow
cd /var/www/audit-flow
```

---

## Langkah 2: Siapkan File Konfigurasi `.env`

Salin template konfigurasi khusus Docker:
```bash
cp .env.docker.example .env
```

Buka dan sesuaikan `.env` (misal `APP_URL`, password database, atau port jika ingin diubah):
```bash
nano .env
```
*(Tekan `CTRL + X`, lalu `Y` dan `ENTER` untuk menyimpan jika menggunakan nano)*.

---

## Langkah 3: Build dan Jalankan Container (1 Perintah)

Jalankan perintah berikut untuk mengompilasi asset frontend dan menjalankan seluruh container di background:
```bash
docker compose up -d --build
```

> **Catatan Otomatisasi:**  
> Script inisialisasi (`docker/entrypoint.sh`) akan secara otomatis:
> - Menunggu database MySQL siap menerima koneksi
> - Menjalankan `php artisan storage:link`
> - Menjalankan migrasi tabel database (`php artisan migrate --force`)
> - Mengaktifkan cache route dan konfigurasi produksi

---

## Langkah 4: Generate App Key & Isi Data Awal (Seeder)

Jalankan perintah berikut sekali saja saat pertama kali deploy:

1. **Generate Enkripsi Key Laravel:**
   ```bash
   docker compose exec app php artisan key:generate
   ```

2. **Isi Data Awal (Akun Pengguna, Role, Kategori Audit CSA, Toko):**
   ```bash
   docker compose exec app php artisan db:seed --force
   ```

---

## Selesai! Aplikasi Sudah Online

Buka browser Anda dan akses:
```
http://IP_SERVER_ANDA
```
atau domain yang telah diarahkan ke IP server Anda.

### Akun Login Bawaan (Default Password: `password`):
- **Admin**: `admin@auditflow.com`
- **Chief Auditor**: `chief@auditflow.com`
- **Asmen**: `asmen@auditflow.com`
- **Koordinator**: `kordinator@auditflow.com`
- **Auditor**: `auditor@auditflow.com`

---

## Perintah Penting untuk Pemeliharaan (Maintenance)

### 1. Melihat Status Container yang Sedang Berjalan
```bash
docker compose ps
```

### 2. Memantau Log Aplikasi Secara Realtime
```bash
# Log seluruh layanan
docker compose logs -f

# Log Laravel aplikasi saja
docker compose logs -f app

# Log web server Nginx saja
docker compose logs -f web
```

### 3. Merestart Layanan
```bash
docker compose restart
```

### 4. Melakukan Update Kode Aplikasi (CI/CD atau Manual Git Pull)
Jika ada pembaruan kode di GitHub/GitLab:
```bash
git pull origin main
docker compose up -d --build
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan optimize
```

### 5. Masuk ke Terminal Container (Bash CLI)
```bash
docker compose exec app sh
```

### 6. Backup Database MySQL ke File `.sql`
```bash
docker compose exec db mysqldump -u audit_user -psecret123 audit_flow > backup_$(date +%F).sql
```

### 7. Menghentikan Seluruh Container
```bash
docker compose down
```
*(Data database dan file upload dokumen tetap aman tersimpan di Docker Volume `db_data` dan `storage_uploads`)*.
