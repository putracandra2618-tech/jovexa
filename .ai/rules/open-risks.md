# Temuan Audit

Riwayat temuan dari audit pada 2026-10-06 dan perbaikannya. Ini **bukan aturan
aktif** — file ini hanya mencatat apa yang sudah diubah dan apa yang masih
menunggu tindakan manusia. Aturan yang harus dipatuhi ada di file lain.

Status per 2026-10-06: semua temuan sudah diperbaiki di kode, kecuali rotasi
kredensial di bagian terakhir. Suite `196` test lulus.

---

## 1. Race condition pada `PaymentService::initiate()`

**Status: sudah diperbaiki.** `Cache::lock` non-blocking + re-check di dalam lock.

Awalnya: `openPaymentFor()` read → `createTransaction()` (network) → `save()`.
Tidak ada lock dan tidak ada unique constraint, sehingga docblock di
`PaymentService.php:23-25` yang mengklaim "a double click never produces a second
transaction" tidak benar. `transaction_id` diberi suffix UUID
(`PaymentService.php:50`) sehingga tidak pernah bentrok.

**Catatan pengujian:** race sungguhan tidak bisa diuji di PHPUnit karena
`phpunit.xml:26` memakai `CACHE_STORE=array` yang lock-nya hanya in-process.
Diuji lewat test yang memegang lock secara manual, bukan lewat konkurensi nyata.
Lihat keputusan (b) di bawah.

## 2. Replay callback dan tidak adanya throttle

**Status: sudah diperbaiki.** Guard `isPaid()`, throttle, dan cek `merchant_code`.

Awalnya `notifikasi/tripay` CSRF-exempt (`bootstrap/app.php:29-31`) tanpa
`throttle`, dengan signature sebagai satu-satunya auth. Idempotensi status sudah
aman lewat guard `isPayable()`, tetapi `amount_received`, `total_fee`, dan
`paid_at` **ditimpa setiap replay** (`PaymentService.php:99-103`).

Guard replay sengaja hanya menyalin status `Paid`, bukan semua status terminal.
Kalau semua status terminal diguard, jalur `Expired → Paid` — customer yang bayar
di detik terakhir — akan ditolak dan uang hilang. Kode sudah menangani jalur itu
dengan benar (`OrderStatus.php:146`, `PaymentService.php:120`).

## 3. Tidak ada schedule sama sekali

**Status: sudah diperbaiki.** Dua command baru + schedule di `routes/console.php`.

Awalnya `routes/console.php` tidak punya `Schedule::` sama sekali, hanya command
`inspire`. `TripayClient::transactionStatus()` (`TripayClient.php:121`) adalah
dead code tanpa pemanggil, jadi tidak ada apa pun yang memulihkan order kalau
callback hilang — order nyangkut `pending_payment` selamanya.
`Payment::isStillOpen()` (`Payment.php:55`) hanya dipakai untuk petunjuk di Blade,
tidak pernah memicu expiry.

**Catatan operasional:** schedule butuh `php artisan schedule:work` berjalan
terus-menerus, atau cron `* * * * * php artisan schedule:run`.

**Catatan implementasi:** endpoint `transaction/detail` (dipakai rekonsiliasi,
bukan `check-status` yang hanya mengembalikan pesan) melaporkan tagihan sebagai
`amount` dan kadang `total_amount` berupa list, sedangkan notifikasi
mengembalikannya sebagai satu nilai. `applyCallback()` membandingkan angka itu
dengan tagihan, jadi `TripayReconcilePayments::normalize()` menormalkannya lebih
dulu. Kegagalan
per transaksi tidak boleh menghentikan batch, jadi tiap transaksi dibungkus
try/catch dan dicatat lewat `Log::warning` — kalau tidak, angka di ringkasan
adalah satu-satunya jejaknya.

## 4. `.jx-reveal` menyembunyikan konten saat JS gagal

**Status: sudah diperbaiki.** Penanda `jx-reveal-ready`, CSS di-scope ulang,
`try/catch`, dan fallback reduced-motion.

Awalnya `app.css` menyembunyikan `.jx-reveal` secara unconditional
(`opacity: 0`) tanpa fallback, dan `initAnimate()` keluar dini kalau body bukan
`playful`. Kalau `app.js` gagal dimuat, 21 elemen tak terlihat — termasuk H1 hero.
Tidak ada `@media (scripting: none)`, tidak ada `.no-js`, dan blok
`prefers-reduced-motion` yang ada hanya menetralkan durasi tanpa memulihkan
`opacity`.

Perbaikannya bukan penanda `html.js` di `<head>`. Percobaan pertama dengan cara
itu tetap gagal: inline `<head>` tetap berjalan meski bundle-nya diblokir, jadi
class tetap terpasang dan konten tetap tersembunyi (terbukti 11 elemen tidak
tampak saat `app-*.js` di-`abort`). Penanda sekarang `jx-reveal-ready`, dan
hanya ditambahkan `initReveal()` setelah observer benar-benar siap, sehingga
CSS tidak pernah menyembunyikan apa pun yang tidak ada yang akan memunculkannya.

Diverifikasi di browser: bundle di-`abort` → 21 elemen tampil, class tidak ada;
`prefers-reduced-motion: reduce` → 21 elemen tampil; scroll normal → 0 elemen
tersembunyi di dalam viewport.

## 5. `GATEWAY_ONLY` hanya konvensi controller

**Status: sudah diperbaiki.** Dipindahkan ke `OrderWorkflow::transitionTo()`.

Awalnya `GATEWAY_ONLY` (`OrderStatus.php:25`) hanya ditegakkan di
`UpdateOrderStatusRequest.php:49`. `OrderWorkflow::transitionTo()` menerima
`Paid` dan `Expired` dari caller mana pun.

**Penting:** penolakan blanko di `transitionTo()` akan merusak
`PaymentService::advanceOrder` (`PaymentService.php:121` dan `:138`, keduanya
mengirim `actor = null`). Karena itu jalur bypass-nya eksplisit lewat parameter
`confirmedByGateway`, bukan sekadar Lempar error.

## 6. Bobot mati di layout admin

**Status: sebagian diperbaiki.** Aset dihapus, komponen dibiarkan.

`layouts/admin.blade.php:14,15,48,49` memuat ApexCharts dan Flatpickr
(sekitar 712 KB) tanpa satu pun konsumen di seluruh blade admin. Sudah dihapus.

**Sengaja dibiarkan:** cabang `.sparkline-card-footer` di
`stat-card.blade.php:31-34` dan `card.blade.php:28-32` memang mati, tapi itu
escape-hatch yang disengaja dan nol byte overhead. Menghapusnya adalah refactor
terpisah yang tidak berhubungan dengan audit ini.

## 7. Higiene config dan env

**Status: sudah diperbaiki.**

- `TRIPAY_CHANNEL_CACHE_SECONDS` dobel di `.env.example:76` dan `:87`.
- `TRIPAY_EXPIRY_MINUTES` dibaca `config/tripay.php:100` tapi tidak pernah ada di
  `.env.example`.
- `tripay.is_production` (`config/tripay.php:34`) tidak dibaca kelas mana pun;
  `base_url` membaca env langsung di `:47`.
- Saat gateway mati, `fallback_channels` (`config/tripay.php:76-88`) dipakai,
  sehingga `supportsChannel()` tetap `true`, customer proceeded, lalu gateway
  menolak.

Yang terakhir **tidak** diubah jadi hard-fail karena akan mematahkan dua test
yang sudah ada. Diganti dengan peringatan di halaman bayar lewat
`TripayClient::channelsAreFallback()`, yang melihat cache kanal kosong sebagai
tanda fetch ke gateway gagal.

## 8. Test menembak API gateway sungguhan

**Status: sudah diperbaiki.**

`Http::fake()` di Laravel 13 mencocokkan `$request->url()` **termasuk query
string**. Pola fake yang ditulis tanpa wildcard akhir, seperti
`tripay.co.id/api-sandbox/transaction/check-status`, tidak akan match
`...?reference=...`, sehingga request benar-benar dikirim ke gateway sungguhan
dengan kredensial dari `.env` lokal. Gejalanya adalah `"Invalid API Key"` dari
server Tripay, bukan kegagalan fake.

Akibatnya suite secara diam-diam bergantung pada `.env` developersesi lokal dan
menembak jaringan. Test yangonisip eksplisit mengunci `tripay.base_url` dan
kredensialnya, dan pola fake memakai wildcard akhir.

---

## Keputusan Desain

### (a) `.sparkline-card-footer` dibiarkan

Nol byte runtime, dan `card.blade.php:4` mendeklarasikan `footer` sebagai prop,
jadi itu API komponen yang disengaja, bukan sisa kode rusak. Menghapusnya
menyempitkan komponen tanpa memberi manfaat nyata.

### (b) `CACHE_STORE` di `phpunit.xml` tidak diubah ke `database`

Pertimbangkan supaya test lock benar-benar atomik, tapi biayanya nyata: seluruh 176
test memakai store `array` dan suite berjalan 37 detik, sedangkan `database` di
sqlite `:memory:` menambah I/O ke setiap test.

Sebagai gantinya, `initiate()` memakai `lock()->get()` **non-blocking** alih-alih
`block()`. Ini justru lebih baik: tidak ada request yang menunggu selama
beberapa detik, dan kasus lock sedang dipegang bisa diuji secara deterministik
dengan test yang memegang lock secara manual. Trade-off yang sama, tanpa biaya.

---

## Tidak Bisa Diperbaiki Lewat Kode

**Rotasi kredensial Tripay.** `.env` memuat kredensial sandbox yang nyata dan
aktif untuk merchant `T52964`. `.env` sudah di-gitignore sehingga tidak ikut
ter-commit, tetapi key-nya masih hidup. Harus dirotasi lewat dashboard Tripay
(`API & Integrasi > Simulator > Merchant > Detail`), lalu diisi ulang di `.env`
lokal. Tidak ada yang bisa dikerjakan dari dalam repository ini.