# AuditFlow Enterprise - Retail Internal Audit Management System

Sistem Manajemen Audit Internal Retail berbasis Laravel 11, Inertia.js, Vue 3, Tailwind CSS, dan Filament Admin.

---

## 🚀 Fitur Utama
1. **Multi-Role Portals:**
   - **Administrator (`/admin` & Filament `/admin/filament`):** Manajemen user, master toko/cabang, master SOP & SE, master kategori audit, action plan monitor, dan rekapitulasi laporan.
   - **Koordinator Audit (`/coordinator`):** Review & penyesuaian tingkat *Severity* temuan, penguncian severity (*Severity Locking*), pemantauan jadwal audit, dan ekspor laporan.
   - **Auditor Lapangan (`/auditor`):** Perencanaan audit, pembuatan temuan lapangan (*Findings*), kalkulasi nominal kerugian (*Loss Amount*), verifikasi bukti tindak lanjut (*Evidence Verification*).
   - **Auditee / Store Team (`/auditee`):** Input rencana perbaikan (*Action Plan*), respon toko, pengunggahan foto bukti perbaikan (*Evidence Upload*).

2. **WhatsApp Gateway Notification (WagHub API):**
   - Pengiriman notifikasi otomatis via WhatsApp saat ada temuan baru yang membutuhkan respon toko atau perubahan status severity.

3. **Excel & Spreadsheet Export (.xls):**
   - Ekspor seluruh data temuan audit lapangan (*All Findings*).
   - Ekspor rekapitulasi temuan dan akumulasi kerugian per cabang (*Store Losses*).
   - Ekspor ringkasan eksekutif (*Executive Summary Matrix*).

4. **Modern Clean Enterprise UI:**
   - Desain split-screen korporat yang bersih (*clean navy & slate*).
   - Sidebar responsif (mode ciut / *collapsed icon-only* & mobile drawer).
   - Dropdown avatar profil pengguna.

---

## 🛠️ Instalasi & Menjalankan Project

### 1. Clone Repositori
```bash
git clone https://github.com/reza-programer/Audit-System.git
cd Audit-System
```

### 2. Install Dependency
```bash
composer install
npm install
```

### 3. Konfigurasi Environment
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan database dan token WhatsApp di `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=auditflow
DB_USERNAME=root
DB_PASSWORD=

WAG_URL=https://waghub.mekayastudio.com/
WAG_TOKEN=your_waghub_token_here
```

### 4. Migrasi & Seeder Database
```bash
php artisan migrate:fresh --seed
```

### 5. Jalankan Aplikasi
```bash
# Terminal 1 (Laravel Server)
php artisan serve

# Terminal 2 (Vite Frontend)
npm run dev
```

---

## 🔑 Akun Demo (Password Default: `password`)
- **Administrator:** `admin@auditflow.com`
- **Koordinator Audit:** `kordinator@auditflow.com`
- **Auditor Lapangan:** `auditor@auditflow.com`
- **Store Team (Auditee):** `auditee@auditflow.com`
