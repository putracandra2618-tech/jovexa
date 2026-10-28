<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kontak Publik
    |--------------------------------------------------------------------------
    |
    | Dipakai di header, footer, dan halaman detail jasa supaya pengunjung
    | punya jalur menghubungi tim Jovexa. Kosongkan nilai bila belum ada.
    |
    */

    'contact' => [
        'whatsapp' => env('JOVEXA_WHATSAPP'),
        'email' => env('JOVEXA_CONTACT_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Jumlah Jasa Unggulan
    |--------------------------------------------------------------------------
    |
    | Berapa banyak jasa yang ditampilkan di bagian "Jasa Populer" pada
    | halaman depan.
    |
    */

    'featured_services_limit' => 3,

];
