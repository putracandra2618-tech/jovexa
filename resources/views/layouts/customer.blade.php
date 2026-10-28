<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-layouts.head />
<body class="playful @yield('body_class')">

<div class="jx-app">
    <header class="jx-header jx-header-customer">
        <div class="jx-container jx-header-inner">
            <a href="{{ route('home') }}" class="jx-brand">
                <span class="jx-brand-mark"><i class="bi bi-asterisk"></i></span>
                <span>{{ config('app.name') }}</span>
            </a>

            <button class="jx-nav-toggle" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#customerNavDrawer" aria-controls="customerNavDrawer"
                    aria-label="Buka navigasi">
                <i class="bi bi-list"></i>
            </button>

            <nav class="jx-nav">
                <x-customer-nav-links />

                <div class="jx-nav-actions">
                    <x-user-menu />
                </div>
            </nav>
        </div>
    </header>

    <div class="offcanvas offcanvas-start jx-drawer" tabindex="-1" id="customerNavDrawer"
         aria-labelledby="customerNavDrawerLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h6 mb-0" id="customerNavDrawerLabel">Menu</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
        </div>

        <div class="offcanvas-body">
            <x-customer-nav-links variant="drawer" />

            <x-user-menu variant="drawer" drawer-id="customerNavDrawer" />
        </div>
    </div>

    @include('partials.impersonation-banner')

    <main class="jx-app-main">
        <div class="jx-container">
            @include('partials.flash')

            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div>
                    <h1 class="jx-section-title mb-1">@yield('page-title')</h1>
                    <p class="page-subtitle mb-0">@yield('page-subtitle')</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @yield('page-actions')
                </div>
            </div>

            @yield('content')
        </div>
    </main>

    <footer class="jx-footer jx-footer-customer">
        <span class="jx-footer-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 90" preserveAspectRatio="none" focusable="false">
                <path d="M0,58 C240,20 480,84 720,64 C960,44 1200,8 1440,50 L1440,90 L0,90 Z"></path>
            </svg>
        </span>

        <div class="jx-container">
            <span class="jx-footer-copy">
                &copy; {{ now()->year }} {{ config('app.name') }}. Semua hak dilindungi.
            </span>
        </div>
    </footer>
</div>

<div class="modal fade" id="jx-confirm-modal" tabindex="-1" aria-labelledby="jx-confirm-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="jx-confirm-modal-title">Konfirmasi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <div class="jx-confirm-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <p class="mb-0" data-confirm-message>Lanjutkan tindakan ini?</p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-custom btn-custom-light" data-bs-dismiss="modal">
                    Batal
                </button>
                <button type="button" class="btn-custom btn-custom-danger" data-confirm-accept>
                    Ya, lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
