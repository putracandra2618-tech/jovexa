<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-layouts.head />
<body class="playful @yield('body_class')">

<div class="jx-login-loading-overlay" data-login-loading-overlay hidden aria-live="polite" aria-busy="true">
    <div class="jx-login-loading-card" role="status">
        <span class="jx-login-loading-status" aria-hidden="true">
            <span class="jx-login-loading-spinner"></span>
            <svg class="jx-login-loading-check" viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="21"></circle>
                <path d="m14 25 6.5 6.5L34.5 17"></path>
            </svg>
            <svg class="jx-login-loading-error" viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="21"></circle>
                <path d="m17 17 14 14M31 17 17 31"></path>
            </svg>
        </span>
        <strong data-login-loading-title>Menyiapkan dashboard</strong>
        <span data-login-loading-message>Memverifikasi akun...</span>
        <small data-login-loading-note>Silakan gunakan akun dengan baik.</small>
    </div>
</div>

<div class="jx-auth">
    <span class="jx-blob jx-blob-mint jx-auth-blob jx-auth-blob-a" aria-hidden="true"></span>
    <span class="jx-blob jx-blob-sky jx-auth-blob jx-auth-blob-b" aria-hidden="true"></span>

    <div class="jx-auth-card">
        <aside class="jx-auth-aside">
            <span class="jx-blob jx-blob-mint" aria-hidden="true"></span>
            <span class="jx-blob jx-blob-coral" aria-hidden="true"></span>

            <a href="{{ route('home') }}" class="jx-auth-brand">
                <span class="jx-brand-mark"><i class="bi bi-asterisk"></i></span>
                <span>{{ config('app.name') }}</span>
            </a>

            <h2 class="jx-auth-aside-title">
                Semua jasa digitalmu, satu akun saja.
            </h2>

            <p class="jx-auth-aside-text">
                Pesan tugas sekolah, coding, sampai desain, lalu pantau progresnya tanpa harus
                mengejar Admin lewat telepon atau chat.
            </p>

            <ul class="jx-auth-points">
                <li>
                    <i class="bi bi-lightning-charge-fill"></i>
                    Pesanan masuk antrean setelah pembayaran terkonfirmasi
                </li>
                <li>
                    <i class="bi bi-graph-up-arrow"></i>
                    Progres pengerjaan bisa dipantau kapan saja
                </li>
                <li>
                    <i class="bi bi-paperclip"></i>
                    File hasil dikumpulkan dalam satu halaman pesanan
                </li>
            </ul>
        </aside>

        <div class="jx-auth-form">
            <h1 class="jx-auth-title">@yield('title', config('app.name'))</h1>

            @hasSection('subtitle')
                <p class="jx-auth-subtitle">@yield('subtitle')</p>
            @endif

            @include('partials.flash')

            @yield('content')

            @hasSection('footer')
                <p class="jx-auth-footer">@yield('footer')</p>
            @endif
        </div>
    </div>
</div>

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
