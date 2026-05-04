<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MoyCupon - @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('iconka/apple-icon-57x57.png') }}">
<link rel="apple-touch-icon" sizes="60x60" href="{{ asset('iconka/apple-icon-60x60.png') }}">
<link rel="apple-touch-icon" sizes="72x72" href="{{ asset('iconka/apple-icon-72x72.png') }}">
<link rel="apple-touch-icon" sizes="76x76" href="{{ asset('iconka/apple-icon-76x76.png') }}">
<link rel="apple-touch-icon" sizes="114x114" href="{{ asset('iconka/apple-icon-114x114.png') }}">
<link rel="apple-touch-icon" sizes="120x120" href="{{ asset('iconka/apple-icon-120x120.png') }}">
<link rel="apple-touch-icon" sizes="144x144" href="{{ asset('iconka/apple-icon-144x144.png') }}">
<link rel="apple-touch-icon" sizes="152x152" href="{{ asset('iconka/apple-icon-152x152.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('iconka/apple-icon-180x180.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('iconka/android-icon-192x192.png') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('iconka/favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('iconka/favicon-96x96.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('iconka/favicon-16x16.png') }}">
<link rel="manifest" href="{{ asset('iconka/manifest.json') }}">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="{{ asset('iconka/ms-icon-144x144.png') }}">
<meta name="theme-color" content="#ffffff">
    <style>
        body { font-family: 'Montserrat', sans-serif;  }
        .auth-card { background: white;  padding: 32px; max-width: 480px; width: 100%; margin: 20px; }
        .auth-btn { background-color: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-align: center; display: inline-block; transition: 0.2s; width: 100%; border: none; cursor: pointer; }
        .auth-btn:hover { background-color: #1d4ed8; }
        .input-field { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; }
        .input-field:focus { outline: none; border-color: #2563eb; ring: 2px solid #2563eb; }
        .back-link { display: inline-block; margin-top: 10px; font-size: 14px; color: #6c757d; text-decoration: none; }
        .back-link:hover { color: #2563eb; }
        .app-icon { width: 60px; height: 60px; margin-bottom: 16px; }
    </style>
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col justify-center items-center">
        <div class="auth-card">
            <div class="text-center mb-6">
                <!-- Иконка -->
                <img src="{{ Vite::asset('resources/images/iconka.svg') }}" alt="MoyCupon" class="app-icon mx-auto">
                <div class="text-2xl font-bold text-gray-800">MoyCupon.ru</div>
                <div class="text-sm text-gray-500 mt-1">@yield('subtitle')</div>
            </div>
            @yield('content')
            <div class="text-center">
                <a href="{{ route('welcome') }}" class="back-link">← Вернуться на главную</a>
            </div>
        </div>
        <div class="text-center text-xs text-gray-400 mt-4">
            
        </div>
    </div>
</body>
</html>