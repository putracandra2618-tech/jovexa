<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-layouts.head />
<body class="playful @yield('body_class')">

<div class="jx-app">
    <header class="jx-header">
        <div class="jx-container jx-header-inner">
            <a href="{{ route('home') }}" class="jx-brand">
                <span class="jx-brand-mark"><i class="bi bi-asterisk"></i></span>
                <span>{{ config('app.name') }}</span>
            </a>

            <a href="{{ route('home') }}" class="btn-custom btn-custom-light btn-custom-sm">
                <i class="bi bi-house"></i> Beranda
            </a>
        </div>
    </header>

    <main class="jx-app-main">
        <div class="jx-container">
            @yield('content')
        </div>
    </main>
</div>

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
