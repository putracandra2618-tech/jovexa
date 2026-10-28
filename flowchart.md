# CATATAN PROJECT — PLATFORM PENYEDIA JASA

## 1. Gambaran Umum

Project ini adalah website penyedia jasa digital, seperti:
- Jasa tugas sekolah
- Jasa coding
- Jasa desain
- Jasa digital lainnya

Alur utama:
Customer memilih jasa → membuat pesanan → melakukan pembayaran → Admin memverifikasi → pesanan masuk antrean → Worker mengklaim dan mengerjakan → hasil dikirim ke Customer.

Role dipisahkan menjadi **Super Admin**, **Admin**, **Worker**, dan **Customer**. Admin menangani operasional jasa/pesanan; Super Admin menangani akun, role, dan impersonation; Worker hanya menangani pesanan yang diklaimnya.

---

## 2. Role Pengguna

### Customer
Customer dapat:
- Melihat daftar jasa
- Melihat detail jasa
- Membuat pesanan
- Mengisi detail kebutuhan pesanan
- Mengupload file yang diperlukan
- Melakukan pembayaran
- Melihat status pesanan
- Menerima hasil pekerjaan
- Mengunduh hasil pekerjaan

### Admin
Admin dapat:
- Melihat seluruh pesanan masuk
- Memverifikasi pembayaran
- Meminta klarifikasi jika informasi belum lengkap
- Melepas pesanan ke antrean Worker (`queued`)
- Melakukan revisi, menyelesaikan, dan mengirim hasil
- Mengunggah hasil pekerjaan
- Mengelola jasa dan status pesanan operasional

### Super Admin
Super Admin dapat:
- Mengakses seluruh fitur Admin
- Mengelola pengguna dan role
- Menghapus akun non-Super Admin
- Melakukan impersonation ke Admin/Worker/Customer
- Melihat audit impersonation

### Worker
Worker dapat:
- Mengaktifkan status online/offline
- Melihat antrean pesanan `queued`
- Mengklaim maksimal 5 pesanan aktif
- Mengerjakan pesanan yang di-assign kepadanya
- Meminta klarifikasi, revisi, menyelesaikan, dan mengirim hasil

### Payment Gateway
Payment Gateway digunakan untuk:
- Memproses pembayaran Customer
- Mengembalikan status pembayaran
- Memberitahu sistem jika pembayaran berhasil atau gagal

---

## 3. Alur Customer

1. Customer membuka website.
2. Customer melihat daftar jasa yang tersedia.
3. Customer memilih salah satu jasa.
4. Customer melihat detail jasa:
   - Nama jasa
   - Deskripsi
   - Harga
   - Estimasi pengerjaan
   - Persyaratan
5. Customer membuat pesanan.
6. Customer mengisi detail pekerjaan yang ingin dikerjakan.
7. Customer dapat mengupload file pendukung.
8. Customer melakukan pembayaran melalui Payment Gateway.
9. Sistem menerima status pembayaran.
10. Jika pembayaran gagal:
    - Status pembayaran menjadi `payment_failed`
    - Customer dapat mencoba membayar kembali.
11. Jika pembayaran berhasil:
    - Status pembayaran menjadi `paid`
    - Pesanan masuk ke tahap verifikasi Admin.
12. Customer menunggu proses verifikasi.
13. Customer dapat melihat perkembangan/status pesanan.
14. Setelah pekerjaan selesai dan hasil dikirim:
    - Customer menerima hasil pekerjaan.
15. Customer dapat mengunduh hasil pekerjaan.

---

## 4. Alur Admin

1. Admin melihat pesanan baru yang sudah dibayar.
2. Admin memeriksa data Customer, jasa, detail pesanan, file, dan status pembayaran.
3. Admin menentukan apakah pesanan valid dan informasinya lengkap.

### Jika pesanan belum lengkap:
- Admin meminta Customer memberikan informasi tambahan.
- Status menjadi `waiting_customer`.
- Setelah Customer mengirim info dan belum ada Worker, pesanan kembali ke `waiting_verification`.

### Jika pesanan valid:
- Admin melepas pesanan ke antrean.
- Status menjadi `queued`.
- Admin **tidak** boleh langsung mengubah `queued` menjadi `in_progress`.

4. Worker mengambil pesanan dari antrean; status menjadi `in_progress`.
5. Jika perlu revisi, Admin atau Worker memindahkan pesanan ke `revision`.
6. Setelah selesai, Admin atau Worker mengunggah hasil dan mengirimkannya.
7. Status akhir menjadi `delivered`.

## 4A. Alur Worker

1. Worker mengaktifkan status online.
2. Worker membuka antrean `queued`.
3. Worker mengklaim pesanan jika kuota aktif masih di bawah 5.
4. Sistem mengunci order dan Worker, lalu meng-assign order ke Worker tersebut.
5. Status berubah menjadi `in_progress`.
6. Worker menyelesaikan pekerjaan dan mengunggah hasil.
7. Worker mengirim hasil ke Customer.
8. Worker hanya dapat mengakses pesanan miliknya.

## 4B. Alur Super Admin

1. Super Admin membuka halaman pengguna.
2. Super Admin mengubah role Customer/Worker/Admin atau menghapus akun non-Super Admin.
3. Worker dengan pesanan aktif tidak dapat dihapus atau diturunkan role-nya.
4. Super Admin dapat memulai impersonation ke akun non-Super Admin.
5. Setiap sesi impersonation dicatat dan dapat diakhiri lewat tombol kembali.

---

## 5. Alur Payment Gateway

1. Customer memilih untuk membayar.
2. Sistem membuat transaksi pembayaran.
3. Customer melakukan pembayaran melalui Payment Gateway.
4. Payment Gateway mengirimkan status pembayaran ke sistem.

### Pembayaran berhasil
- Payment status: `paid`
- Order dapat masuk ke proses verifikasi Admin.

### Pembayaran gagal
- Payment status: `failed`
- Customer dapat melakukan pembayaran kembali.

Sistem harus menggunakan callback/webhook dari Payment Gateway untuk memastikan status pembayaran, bukan hanya mengandalkan halaman redirect Customer.

---

## 6. Status Pesanan

Gunakan status pesanan berikut:

`pending_payment`
→ `paid`
→ `waiting_verification`
→ `queued`
→ `in_progress`
→ `completed`
→ `delivered`

Cabang: `waiting_customer`, `revision`, `expired`, dan `cancelled`.

Penjelasan:

### pending_payment
Pesanan sudah dibuat tetapi belum dibayar.

### paid
Pembayaran sudah berhasil.

### waiting_verification
Pesanan menunggu pemeriksaan Admin.

### queued
Pesanan sudah diverifikasi dan menunggu diklaim Worker.

### waiting_customer
Admin atau Worker membutuhkan informasi tambahan dari Customer.

### in_progress
Pesanan sedang dikerjakan.

### revision
Pekerjaan perlu diperbaiki sebelum dikirim.

### completed
Pekerjaan sudah selesai dan hasil sudah tersedia.

### delivered
Hasil pekerjaan sudah dikirim dan dapat diakses Customer.

---

## 7. Alur Status Pembayaran

Gunakan status pembayaran:

- `pending`
- `paid`
- `failed`
- `expired`

Hubungan dasar:

`pending_payment`
→ pembayaran berhasil
→ `paid`

Jika pembayaran gagal:

`pending_payment`
→ `payment_failed`

Jika transaksi kadaluarsa:

`pending_payment`
→ `expired`

---

## 8. Data Utama yang Dibutuhkan

### User
Minimal:
- id
- name
- email
- password
- role
- timestamps

Role:
- `customer`
- `worker`
- `admin`
- `super_admin`

Tambahan untuk Worker:
- `is_online` (boolean)

### Service
Menyimpan jasa yang tersedia.

Minimal:
- id
- name
- slug
- description
- price
- estimated_duration
- requirements
- status
- timestamps

### Order
Menyimpan pesanan Customer.

Minimal:
- id
- user_id
- service_id
- order_number
- description
- status
- total_price
- timestamps

### Order Files
Menyimpan file yang berkaitan dengan pesanan.

Minimal:
- id
- order_id
- file_name
- file_path
- file_type
- uploaded_by
- timestamps

### Payment
Menyimpan informasi pembayaran.

Minimal:
- id
- order_id
- transaction_id
- payment_method
- amount
- status
- paid_at
- timestamps

---

## 9. Relasi Data Dasar

User:
- memiliki banyak Order

Service:
- memiliki banyak Order

Order:
- dimiliki oleh satu User
- menggunakan satu Service
- memiliki banyak File
- memiliki informasi Payment

Struktur sederhananya:

User
└── Orders
    ├── Service
    ├── Order Files
    └── Payment

---

## 10. Halaman Customer

Minimal halaman:

### Public
- Home
- Daftar Jasa
- Detail Jasa
- Login
- Register

### Customer
- Dashboard
- Pesanan Saya
- Detail Pesanan
- Pembayaran
- Status Pesanan
- Download Hasil

---

## 11. Halaman Admin, Worker, dan Super Admin

Admin:
- Dashboard
- Daftar Pesanan
- Detail Pesanan
- Verifikasi Pesanan
- Pelepasan ke Antrean
- Pesanan Sedang Dikerjakan
- Upload Hasil
- Pesanan Revisi
- Riwayat Pesanan
- Kelola Jasa

Worker:
- Dashboard + status online + kuota
- Antrean Pesanan
- Pesanan Saya
- Detail Pesanan
- Upload Hasil
- Riwayat Saya

Super Admin:
- Kelola Pengguna
- Ubah Role
- Hapus Akun
- Login Sebagai (impersonation)
- Audit Impersonation

---

## 12. Aturan Penting

1. Customer tidak dapat menganggap pesanan aktif sebelum pembayaran berhasil.
2. Pesanan yang belum dibayar tidak boleh masuk ke proses pengerjaan.
3. Admin harus memverifikasi pesanan setelah pembayaran berhasil.
4. Jika informasi Customer kurang, gunakan status `waiting_customer`.
5. Admin melepas pesanan ke `queued`; hanya Worker yang dapat mengubahnya ke `in_progress` lewat klaim.
6. Worker wajib online dan memiliki kuota kurang dari 5 untuk mengklaim pesanan.
7. Customer dapat melihat status pesanan tetapi tidak dapat mengubah status secara manual.
8. File hasil pekerjaan hanya dapat diakses oleh Customer yang memiliki pesanan tersebut.
9. Gunakan order number yang unik untuk setiap pesanan.
10. Status pembayaran harus dikonfirmasi dari Payment Gateway melalui webhook/callback.
11. Simpan riwayat perubahan status agar aktivitas pesanan dapat dilacak.
12. Hanya Super Admin yang dapat mengelola pengguna dan melakukan impersonation.

---

## 13. Alur Utama Singkat

CUSTOMER
↓
Lihat Jasa
↓
Pilih Jasa
↓
Buat Pesanan
↓
Upload Detail/File
↓
Bayar
↓
PAYMENT GATEWAY
↓
Pembayaran Berhasil
↓
ADMIN
↓
Verifikasi Pesanan
↓
Pesanan Valid?
├── Tidak → Minta Informasi Customer → Verifikasi Kembali
└── Ya
    ↓
    Lepas ke Antrean (queued)
    ↓
    WORKER ONLINE
    ↓
    Klaim Pesanan (kuota < 5)
    ↓
    Kerjakan Pesanan (in_progress)
    ↓
    Perlu Revisi?
    ├── Ya → Revisi → Periksa Kembali
    └── Tidak
        ↓
        Upload Hasil
        ↓
        Kirim ke Customer
        ↓
        Customer Menerima & Mengunduh Hasil
        ↓
        SELESAI

---

## 14. Catatan untuk Implementasi

Bangun sistem secara bertahap.

Prioritas:
1. Authentication dan role
2. CRUD Service
3. Customer membuat Order
4. Upload file Order
5. Payment Gateway
6. Webhook pembayaran
7. Dashboard Customer
8. Dashboard Admin
9. Verifikasi Order dan pelepasan ke antrean
10. Dashboard Worker, status online, kuota, dan klaim order
11. Proses pengerjaan
12. Upload hasil
13. Delivery hasil
14. Status dan riwayat Order
15. Kelola pengguna dan impersonation Super Admin

Jangan membuat fitur yang belum diperlukan sebelum alur utama berjalan dengan baik.

Tujuan versi awal adalah membuat alur:
**Pilih Jasa → Order → Bayar → Verifikasi Admin → Antrean → Klaim Worker → Kerjakan → Upload Hasil → Kirim ke Customer**
berjalan dengan stabil.


catatan: 
-payment gateway pakai tripay
-template admin udah aku tambahkan di project (spark admin) tinggal sesuaikan saja