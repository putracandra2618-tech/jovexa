<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/css/main.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<main>
    @include('partials.flash')
    @yield('content')
</main>

<script src="{{ asset('vendor/spark/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')

</body>
</html>
