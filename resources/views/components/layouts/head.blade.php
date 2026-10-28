<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <meta name="description" content="@yield('description', 'Jovexa — platform penyedia jasa digital untuk tugas sekolah, coding, dan desain.')">

    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/libs/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/spark/css/main.css') }}">

    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;800&display=swap">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
