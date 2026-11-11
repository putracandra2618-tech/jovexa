<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if (auth()->user()?->isStaff())
        <meta name="staff-turbo" content="enabled">
    @endif

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/css/main.css') }}">

    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
<body>

<div class="impersonation-loading-overlay" data-impersonation-loading-overlay hidden aria-live="polite" aria-busy="true">
    <div class="impersonation-loading-card" role="status">
        <span class="impersonation-loading-status" aria-hidden="true">
            <span class="impersonation-loading-spinner"></span>
            <svg class="impersonation-loading-check" viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="21"></circle>
                <path d="m14 25 6.5 6.5L34.5 17"></path>
            </svg>
        </span>
        <strong>Menyiapkan akses akun</strong>
        <span data-impersonation-loading-message>Mohon tunggu sebentar...</span>
    </div>
</div>

<div class="logout-loading-overlay" data-logout-loading-overlay hidden aria-live="polite" aria-busy="true">
    <div class="logout-loading-card" role="status">
        <span class="logout-loading-status" aria-hidden="true">
            <span class="logout-loading-spinner"></span>
            <svg class="logout-loading-check" viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="21"></circle>
                <path d="m14 25 6.5 6.5L34.5 17"></path>
            </svg>
        </span>
        <strong>Sampai jumpa</strong>
        <span data-logout-loading-message>Menutup sesi...</span>
        <small>Semoga harimu menyenangkan.</small>
    </div>
</div>

@include('partials.admin.sidebar')

<div class="main-wrapper">
    @include('partials.admin.topbar')

    @hasSection('page-title')
        <div class="page-header">
            <div>
                <h1 class="page-title">@yield('page-title')</h1>
                @hasSection('page-subtitle')
                    <p class="page-subtitle">@yield('page-subtitle')</p>
                @endif
            </div>
            @hasSection('page-actions')
                <div class="page-header-actions">
                    @yield('page-actions')
                </div>
            @endif
        </div>
    @endif

    <main>
        @include('partials.flash')
        @yield('content')
    </main>

    @include('partials.admin.footer')
</div>

    <script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}" data-turbo-eval="false"></script>
    <script>
        // Initialize all dropdowns to prevent unexpected closing for admin/worker layout
        document.addEventListener('DOMContentLoaded', function() {
            // Cari semua dropdown yang ada di halaman
            const allDropdowns = document.querySelectorAll('.dropdown');
            allDropdowns.forEach(function(dropdown) {
                const toggleBtn = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                
                if (toggleBtn && dropdownMenu) {
                    // Inisialisasi Bootstrap dropdown
                    new bootstrap.Dropdown(toggleBtn);
                    
                    // Hentikan propagasi hanya pada dropdown itu sendiri
                    toggleBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                    
                    dropdownMenu.addEventListener('click', function(e) {
                        // Jangan hentikan propagasi untuk tombol yang seharusnya menutup dropdown (seperti logout)
                        if (!e.target.closest('button[type="submit"]')) {
                            e.stopPropagation();
                        }
                    });
                }
            });
        });
    </script>
@stack('scripts')

</body>
</html>