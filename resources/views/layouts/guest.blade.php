<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MoyCupon - @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f5f7fb; }
        .auth-card { background: white; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 32px; max-width: 480px; width: 100%; margin: 20px; }
        .auth-btn { background-color: #2563eb; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-align: center; display: inline-block; transition: 0.2s; width: 100%; border: none; cursor: pointer; }
        .auth-btn:hover { background-color: #1d4ed8; }
        .input-field { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Montserrat', sans-serif; }
        .input-field:focus { outline: none; border-color: #2563eb; ring: 2px solid #2563eb; }
        .back-link { display: inline-block; margin-top: 16px; font-size: 14px; color: #6c757d; text-decoration: none; }
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
            <div class="text-center mt-4">
                <a href="{{ route('welcome') }}" class="back-link">← Вернуться на главную</a>
            </div>
        </div>
        <div class="text-center text-xs text-gray-400 mt-4">
            © 2026 MoyCupon — аналитический сервис для инвесторов
        </div>
    </div>
</body>
</html>