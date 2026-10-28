<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tripay API Credentials
    |--------------------------------------------------------------------------
    |
    | Kredensial ini hanya dipakai di backend. Token API dan private key tidak
    | boleh pernah dikirim ke browser atau disimpan di repository.
    |
    | Ambil dari menu API & Integrasi > Simulator > Merchant > Detail pada
    | akun sandbox, atau menu Merchant > Opsi > Edit untuk production.
    |
    */

    'api_key' => env('TRIPAY_API_KEY'),

    'merchant_code' => env('TRIPAY_MERCHANT_CODE'),

    'private_key' => env('TRIPAY_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Base URL
    |--------------------------------------------------------------------------
    |
    | Tripay menyajikan endpoint pembayaran pada host yang sama dengan path
    | berbeda untuk sandbox dan production. Host subdomain seperti
    | api-sandbox.tripay.id tidak resolving, jadi jangan dicampur di sini.
    |
    | Host ini adalah satu-satunya penentu sandbox atau production, dibaca
    | langsung dari TRIPAY_IS_PRODUCTION. Jangan tambah key config lain untuk
    | hal yang sama, karena akan ada dua sumber kebenaran yang bisa berbeda.
    |
    */

    'base_url' => env('TRIPAY_IS_PRODUCTION', false)
        ? 'https://tripay.co.id/api'
        : 'https://tripay.co.id/api-sandbox',

    /*
    |--------------------------------------------------------------------------
    | Payment Channel Cache
    |--------------------------------------------------------------------------
    |
    | Daftar channel aktif diambil dari API Tripay lalu disimpan di cache
    | selama beberapa jam supaya halaman pembayaran tidak memanggil gateway
    | pada setiap request.
    |
    */

    'channel_cache_seconds' => (int) env('TRIPAY_CHANNEL_CACHE_SECONDS', 21600),

    /*
    |--------------------------------------------------------------------------
    | Fallback Channels
    |--------------------------------------------------------------------------
    |
    | Dipakai saat gateway sedang tidak terjangkau sehingga halaman pembayaran
    | tetap menampilkan opsi. Kode channel mengikuti tabel resmi Tripay, dan
    | hanya channel dengan tipe direct maupun redirect yang bisa dipakai lewat
    | checkout URL.
    |
    */

    'fallback_channels' => [
        'QRIS' => 'QRIS',
        'QRIS2' => 'QRIS',
        'BRIVA' => 'BRI Virtual Account',
        'BCAVA' => 'BCA Virtual Account',
        'BNIVA' => 'BNI Virtual Account',
        'MANDIRIVA' => 'Mandiri Virtual Account',
        'PERMATAVA' => 'Permata Virtual Account',
        'CIMBVA' => 'CIMB Niaga Virtual Account',
        'BSIVA' => 'BSI Virtual Account',
        'ALFAMART' => 'Alfamart',
        'INDOMARET' => 'Indomaret',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Expiry
    |--------------------------------------------------------------------------
    |
    | Batas waktu pembayaran dalam menit. Tripay mengizinkan 10 sampai 4320
    | menit tergantung channel, dan memakai 1440 menit (24 jam) bila kosong.
    |
    */

    'expiry_minutes' => (int) env('TRIPAY_EXPIRY_MINUTES', 1440),

];
