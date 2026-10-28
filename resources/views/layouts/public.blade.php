<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-layouts.head />
<body class="playful @yield('body_class')">

@php
    $whatsapp = config('jovexa.contact.whatsapp');
    $contactEmail = config('jovexa.contact.email');
@endphp

<div class="jx-app">
    <header class="jx-header navbar-public">
        <div class="jx-container jx-header-inner">
            <a href="{{ route('home') }}" class="jx-brand">
                <span class="jx-brand-mark"><i class="bi bi-asterisk"></i></span>
                <span>{{ config('app.name') }}</span>
            </a>

            <button class="jx-nav-toggle" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#publicNavDrawer" aria-controls="publicNavDrawer"
                    aria-label="Buka menu navigasi">
                <i class="bi bi-list"></i>
            </button>

            <nav class="jx-nav">
                <x-public-nav-links />

                <div class="jx-nav-actions">
                    @auth
                        @unless (auth()->user()->isCustomer())
                            <a href="{{ route(auth()->user()->role->homeRoute()) }}" class="btn-custom btn-custom-primary">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        @endunless
                        <x-user-menu />
                    @else
                        <a href="{{ route('login') }}" class="btn-custom btn-custom-light">Masuk</a>

                        <a href="{{ route('register') }}" class="btn-custom btn-custom-primary">
                            <i class="bi bi-person-plus"></i> Daftar
                        </a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <div class="offcanvas offcanvas-start jx-drawer" tabindex="-1" id="publicNavDrawer"
         aria-labelledby="publicNavDrawerLabel">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title h6 mb-0" id="publicNavDrawerLabel">Menu</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
        </div>

        <div class="offcanvas-body">
            <x-public-nav-links variant="drawer" />

            <div class="jx-drawer-actions">
                @auth
                    @unless (auth()->user()->isCustomer())
                        <a href="{{ route(auth()->user()->role->homeRoute()) }}"
                           class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    @endunless
                    <x-user-menu variant="drawer" drawer-id="publicNavDrawer" />
                @else
                    <a href="{{ route('login') }}" class="btn-custom btn-custom-light w-100">
                        Masuk
                    </a>

                    <a href="{{ route('register') }}" class="btn-custom btn-custom-primary w-100">
                        <i class="bi bi-person-plus me-2"></i> Daftar
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <main class="public-main">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="jx-footer footer-public">
        <span class="jx-footer-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 90" preserveAspectRatio="none" focusable="false">
                <path d="M0,58 C240,20 480,84 720,64 C960,44 1200,8 1440,50 L1440,90 L0,90 Z"></path>
            </svg>
        </span>

        <div class="jx-container">
            <div class="jx-footer-grid">
                <div>
                    <a href="{{ route('home') }}" class="jx-brand">
                        <span class="jx-brand-mark"><i class="bi bi-asterisk"></i></span>
                        <span>{{ config('app.name') }}</span>
                    </a>

                    <p class="jx-footer-tagline mt-3">
                        Jasa digital untuk tugas sekolah, kuliah, coding, dan desain.
                        Pesanan diproses manusia, progresnya bisa kamu pantau sendiri.
                    </p>
                </div>

                <div>
                    <h2 class="jx-footer-title">Jelajahi</h2>
                    <ul class="jx-footer-list">
                        <li><a href="{{ route('services.index') }}">Katalog Jasa</a></li>
                        <li><a href="{{ route('home') }}#cara-pesan">Cara Pesan</a></li>
                        <li><a href="{{ route('home') }}#faq">Pertanyaan Umum</a></li>
                        @auth
                            <li><a href="{{ route(auth()->user()->role->homeRoute()) }}">Dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}">Masuk</a></li>
                            <li><a href="{{ route('register') }}">Daftar Akun</a></li>
                        @endauth
                    </ul>
                </div>

                <div>
                    <h2 class="jx-footer-title">Hubungi Kami</h2>
                    <ul class="jx-footer-list">
                        @if ($whatsapp)
                            <li>
                                <a href="https://wa.me/{{ ltrim($whatsapp, '+') }}" target="_blank" rel="noopener">
                                    <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                </a>
                            </li>
                        @endif

                        @if ($contactEmail)
                            <li><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></li>
                        @endif

                        <li>
                            <span class="text-muted">
                                <i class="bi bi-clock me-1"></i> Senin&ndash;Sabtu, 09.00&ndash;21.00 WIB
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="jx-footer-bottom">
                <span>&copy; {{ now()->year }} {{ config('app.name') }}. Seluruh hak dilindungi.</span>
                <span>Pembayaran aman lewat Tripay.</span>
            </div>
        </div>
    </footer>
</div>

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
