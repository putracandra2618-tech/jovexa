# Jovexa

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
* **Filter Kategori Pesanan**: Tabel pesanan menggunakan kategori *Semua*, *Berjalan*, dan *Selesai*. Status yang masih membutuhkan tindakan ikut kategori *Selesai*.

### 2. Sisi Pengerja (Worker)
* **Antrean Tugas (Job Pool)**: Mengklaim pesanan yang sudah terbayar dan masuk ke antrean pengerjaan.
* **Filter Kategori Pesanan**: Antrean worker memakai kategori *Semua*, *Berjalan*, dan *Selesai* yang sama dengan sisi admin dan customer.
* **Manajemen Beban Kerja**: Dasbor khusus memantau pesanan aktif yang sedang dikerjakan dan riwayat tugas yang telah selesai.
* **Permintaan Informasi Tambahan**: Meminta file/detail prasyarat lanjutan kepada pelanggan langsung dari sistem.
* **Pengiriman Deliverable**: Mengunggah file hasil akhir pengerjaan ke pelanggan dengan konfirmasi status otomatis.

### 3. Sisi Administrasi (Admin & Super Admin)
* **Katalog & Layanan**: Mengelola jasa (tambah, edit, *publish*, *archive*) dan memindahkannya ke *Soft Delete* untuk dipulihkan atau dihapus permanen.
* **Monitoring & Pengawasan Pesanan**: Memantau transaksi, antrean pengerjaan, revisi, dan riwayat melalui tab kategori *Semua*, *Berjalan*, *Selesai*. Status yang butuh tindakan ikut kategori *Selesai*; aksi **Batalkan** tetap tersedia.
* **Peringatan Antrean Terabaikan**: Dashboard admin menampilkan alert *"Ada antrean yang belum di ambil worker"* untuk pesanan yang menunggu lebih dari 2 hari. Pemeriksaan dijalankan tiap 5 menit oleh `orders:notify-unclaimed-queue`.
* **Penetapan & Pengalihan Worker**: Menetapkan atau memindahkan pengerjaan pesanan kepada worker tertentu.
* **Super Admin - Manajemen Pengguna**: Mengelola akun (tambah, edit, atur role) dan memindahkannya ke *Soft Delete* beserta data pesanan terkait; pemulihan tersedia di menu *Soft Delete > Pengguna*.
* **Fitur Impersonasi (Login Sebagai)**: Super Admin dapat masuk sementara sebagai akun pengguna lain untuk kemudahan audit dan troubleshooting tanpa memerlukan password pengguna.
* **Pengaturan Profil Admin**: Pembaruan kredensial dan kata sandi mandiri.

> **Catatan**: Menu *Soft Delete* hanya menyediakan **Pengguna** dan **Jasa**. Pesanan tidak dihapus dari tabel admin; gunakan aksi **Batalkan** untuk menghentikan pesanan.

---

## 🚀 Memulai

Butuh PHP 8.3+, Composer, Node.js, dan MySQL/SQLite.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Isi `.env` dari `.env.example` untuk database, URL aplikasi, dan kredensial Tripay. Kredensial Admin/Super Admin sengaja tidak disimpan di `.env`.

Untuk membuat akun privileged pertama, jalankan:

```bash
php artisan bootstrap:admin
```

Perintah ini meminta email, nama, role, dan password secara tersembunyi. Ganti password setelah login dan jangan simpan kredensial di file atau command.

## Penjadwalan

Beberapa fitur otomatis bergantung pada scheduler Laravel. Untuk pengembangan, jalankan di terminal terpisah:

```bash
php artisan schedule:work
```

Penjadwalan yang aktif:

* `tripay:expire-stale-payments` setiap 5 menit untuk menutup pembayaran yang kedaluwarsa.
* `tripay:reconcile-payments` setiap jam untuk rekonsiliasi status transaksi ke Tripay.
* `orders:notify-stale-verification` setiap 5 menit untuk mengingatkan customer jika verifikasi belum dilakukan setelah 30 menit.
* `orders:notify-unclaimed-queue` setiap 5 menit untuk memberi alert kepada admin jika antrean worker belum diambil lebih dari 2 hari.

Di produksi, daftarkan cron yang menjalankan `php artisan schedule:run` setiap menit.

---

## 📄 Lisensi

Aplikasi Jovexa dirancang untuk penyediaan layanan digital terpercaya. Hak Cipta dilindungi undang-undang.
