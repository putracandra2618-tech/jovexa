<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') &middot; {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/css/main.css') }}">

    @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js'])
</head>
<body>

@include('partials.admin.sidebar')

<div class="main-wrapper">
    @include('partials.admin.topbar')
    @include('partials.impersonation-banner')

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

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
