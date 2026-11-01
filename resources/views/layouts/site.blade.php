<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f8f4ed">
    <meta name="description" content="@yield('description', 'Buku tamu digital untuk acara Anda. Tamu cukup scan QR, tanpa install aplikasi.')">
    @hasSection('noindex')<meta name="robots" content="noindex, nofollow">@endif
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    {{-- Preview saat link dibagikan (WhatsApp, Facebook, dll.) --}}
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="@yield('title', config('app.name'))">
    <meta property="og:description" content="@yield('description', 'Buku tamu digital untuk acara Anda.')">
    <meta property="og:image" content="{{ asset('images/brand/og-image.jpg') }}">
    <meta property="og:type" content="website">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class', 'min-h-screen')">
    @yield('content')
</body>
</html>
