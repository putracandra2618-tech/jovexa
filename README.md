# Jovexa

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/Tripay-Payment%20Gateway-009EF7?style=for-the-badge" alt="Tripay Gateway">
</p>

**Jovexa** adalah platform marketplace dan sistem manajemen pemesanan jasa digital (seperti pengerjaan skripsi, bantuan tugas kuliah, coding website, desain grafis/landing page, dan analisis data). Platform ini menghubungkan pelanggan (*customer*), pengerja (*worker*), serta pengelola (*admin & super admin*) dalam satu alur kerja transparan, aman, dan terotomatisasi.

---

## 🌟 Fitur Utama

### 1. Sisi Pelanggan (Customer / Public)
* **Katalog Jasa Interaktif**: Eksplorasi layanan digital lengkap dengan estimasi durasi, harga transparan, dan detail prasyarat pengerjaan.
* **Pemesanan Mudah**: Formulir pemesanan dengan dukungan unggah file prasyarat (lampiran dokumen/arsip).
* **Payment Gateway Tripay**: Pembayaran otomatis melalui QRIS, Virtual Account (BCA, Mandiri, BRI, BNI, dll.), E-Wallet, dan gerai retail.
* **Pelacakan Progres Real-Time**: Visualisasi tahapan pesanan menggunakan stepper progress bar interaktif.
* **Manajemen Revisi & Komunikasi**: Mengajukan permintaan revisi hasil pengerjaan serta mengunggah dokumen tambahan jika diminta oleh worker/admin.
* **Unduh Hasil Aman**: File hasil pengerjaan terlindungi dan hanya dapat diunduh setelah pesanan lunas dan dikirim.
* **Manajemen Profil**: Pengaturan nama, email, nomor WhatsApp, dan perubahan password.

### 2. Sisi Pengerja (Worker)
* **Antrean Tugas (Job Pool)**: Mengklaim pesanan yang sudah terbayar dan masuk ke antrean pengerjaan.
* **Manajemen Beban Kerja**: Dasbor khusus memantau pesanan aktif yang sedang dikerjakan dan riwayat tugas yang telah selesai.
* **Permintaan Informasi Tambahan**: Meminta file/detail prasyarat lanjutan kepada pelanggan langsung dari sistem.
* **Pengiriman Deliverable**: Mengunggah file hasil akhir pengerjaan ke pelanggan dengan konfirmasi status otomatis.

### 3. Sisi Administrasi (Admin & Super Admin)
* **Katalog & Layanan**: Manajemen jasa penuh (CRUD), pengaturan harga, estimasi durasi, penerbitan (*publish*), dan pengarsipan (*archive*).
* **Monitoring & Pengawasan Pesanan**: Memantau seluruh transaksi, antrean pengerjaan, antrean revisi, dan riwayat pesanan.
* **Penetapan & Pengalihan Worker**: Menetapkan atau memindahkan pengerjaan pesanan kepada worker tertentu.
* **Super Admin - Manajemen Pengguna**: Mengelola akun pengguna (tambah, edit, atur role: Customer, Worker, Admin, Super Admin).
* **Fitur Impersonasi (Login Sebagai)**: Super Admin dapat masuk sementara sebagai akun pengguna lain untuk kemudahan audit dan troubleshooting tanpa memerlukan password pengguna.
* **Pengaturan Profil Admin**: Pembaruan kredensial dan kata sandi mandiri.

---

## 🔄 Alur Status Pesanan (State Machine)

Pesanan dikontrol ketat oleh `OrderWorkflow` dan enum `OrderStatus` untuk mencegah transisi ilegal:

```
[Pending Payment] ───────────────► [Expired]
        │
        ▼ (Tripay Webhook Callback)
     [Paid]
        │
        ▼
[Waiting Verification] ◄───► [Waiting Customer] (Permintaan Info Tambahan)
        │
        ▼
     [Queued] (Antrean Pengerjaan)
        │
        ▼ (Worker Klaim)
   [In Progress] ◄───────────┐
        │                    │
        ▼                    │
   [Completed]               │ (Pengajuan Revisi)
        │                    │
        ▼                    │
   [Delivered] ──────────────┘
```

*Status Tambahan:*
* **Cancelled**: Pesanan yang dibatalkan sebelum pengiriman hasil akhir.

---

## 🎨 Arsitektur Tampilan & Desain

Proyek ini menerapkan pemisahan gaya desain yang tegas dan bersih:
1. **Playful Design System (`body.playful`)**:
   * Digunakan pada halaman publik, autentikasi (login/register), dan dasbor pelanggan.
   * Nuansa warna mint pastel segar (`--jx-mint`, `--jx-green`, `--jx-coral`), font Poppins / Plus Jakarta Sans, micro-animasi taktil, dan responsif ramah seluler.
2. **Spark / Metronic Dark Theme**:
   * Digunakan khusus pada dasbor Admin dan Worker.
   * Nuansa korporat modern (dark sidebar, soft cards, layout padat data), serta akselerasi navigasi menggunakan **Hotwire Turbo**.

---

## 🛠️ Tech Stack & Dependensi

* **Framework Backend**: Laravel 12.x
* **Bahasa Pemrograman**: PHP 8.3+
* **Basis Data**: MySQL 8.0+ / SQLite
* **Frontend**: Blade, Bootstrap 5.3, Bootstrap Icons, Hotwire Turbo
* **Asset Bundler**: Vite 8.x
* **Payment Gateway**: Tripay API (Sandbox & Production Support)
* **Code Quality & Testing**: Laravel Pint, PHPUnit 12

---

## 📋 Persyaratan Sistem

Pastikan lingkungan lokal atau server Anda memiliki:
* PHP `>= 8.3` dengan ekstensi: `pdo`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`/`imagick`
* Composer `>= 2.2`
* Node.js `>= 18.x` & npm
* MySQL / MariaDB (atau SQLite untuk pengembangan cepat)
* Laragon / XAMPP / Valet / Docker (opsional)

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi

### 1. Klon Repositori
```bash
git clone https://github.com/username/jovexa.git
cd jovexa
```

### 2. Instalasi Dependensi PHP & JavaScript
```bash
composer install
npm install
```

### 3. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```

Buka `.env` dan sesuaikan konfigurasi basis data dan URL aplikasi:
```env
APP_NAME=Jovexa
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jovexa
DB_USERNAME=root
DB_PASSWORD=

# Integrasi Payment Gateway Tripay
TRIPAY_API_KEY=your_tripay_api_key
TRIPAY_MERCHANT_CODE=your_merchant_code
TRIPAY_PRIVATE_KEY=your_private_key
TRIPAY_IS_PRODUCTION=false
TRIPAY_EXPIRY_MINUTES=1440
```

### 4. Generate Application Key & Migrasi Basis Data
```bash
php artisan key:generate
php artisan migrate --seed
```
*Perintah di atas mengeksekusi migrasi tabel dan seeding data awal (lookup data dan katalog jasa). Seeder Admin/Super Admin sengaja dilewati agar tidak ada password default yang tersimpan di `.env`.*

Pada instalasi baru, buat akun privileged sekali saja dengan kredensial sementara yang diberikan langsung ke proses — **jangan ditulis ke `.env`**:

```bash
ADMIN_EMAIL=admin@jovexa.test ADMIN_PASSWORD='password-kuat' \
  php artisan db:seed --class=AdminSeeder

SUPER_ADMIN_EMAIL=superadmin@jovexa.test SUPER_ADMIN_PASSWORD='password-kuat' \
  php artisan db:seed --class=SuperAdminSeeder
```

Gunakan `--class` agar `ServiceSeeder` tidak dijalankan ulang dan menimpa perubahan katalog oleh admin. `ADMIN_NAME` dan `SUPER_ADMIN_NAME` opsional (memiliki nilai default di seeder). Setelah login, ganti password melalui halaman profil. Jangan menjalankan seeder ini dengan kredensial lama lagi: `AdminSeeder` dan `SuperAdminSeeder` akan menimpa password yang sudah diganti.

### 5. Kompilasi Aset Frontend
Untuk mode pengembangan:
```bash
npm run dev
```
Atau untuk build produksi:
```bash
npm run build
```

### 6. Jalankan Server Lokal
```bash
php artisan serve
```
Buka peramban di `http://localhost:8000`.

---

## ⏰ Cron Jobs & Scheduled Tasks

Jovexa dilengkapi dengan otomatisasi rekonsiliasi dan kedaluwarsa pesanan:

| Command | Jadwal | Deskripsi |
| :--- | :--- | :--- |
| `php artisan tripay:expire-stale-payments` | Tiap 5 menit | Mengubah status transaksi lokal yang melewati batas bayar menjadi Expired |
| `php artisan tripay:reconcile-payments` | Tiap 1 jam | Menyelaraskan status pembayaran lokal dengan Tripay API bila ada callback tertunda |
| `php artisan tripay:sync-channels` | On-demand | Memperbarui daftar kanal pembayaran aktif dari Tripay |

Untuk menjalankan scheduler secara lokal:
```bash
php artisan schedule:work
```

---

## 🔒 Keamanan & Kebijakan Rate Limiting

Platform ini menerapkan pembatasan laju (*rate limiting*) pada endpoint sensitif:
* **Tripay Webhook Callback**: Diproteksi verifikasi signature SHA256 (`hash_equals`) terhadap raw payload serta rate limit 60 req/menit.
* **Checkout & Pembuatan Pesanan**: Dibatasi 10 req/menit per pengguna.
* **Registrasi Akun**: Dibatasi 5 req/menit per IP untuk mencegah spam akun palsu.
* **Unggah Lampiran**: Dibatasi 10 req/menit per pengguna dengan validasi ekstensi file dokumen dan gambar.
* **Impersonation Guard**: Pembatasan akses impersonasi khusus bagi role Super Admin dengan audit banner dan tombol kembali instan.

---

## 🧪 Pengujian (Testing) & Formatting

Menjalankan test suite:
```bash
php artisan test
```

Menjalankan linter & formatter kode (Laravel Pint):
```bash
vendor/bin/pint --dirty --format agent
```

---

## 📄 Lisensi

Aplikasi Jovexa dirancang untuk penyediaan layanan digital terpercaya. Hak Cipta dilindungi undang-undang.
