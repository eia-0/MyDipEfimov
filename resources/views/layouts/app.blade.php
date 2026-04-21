<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MoyCupon - @yield('title', 'Инвест портфель')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f5f7fb; }
        /* Анимации для мобильного меню */
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .overlay {
            transition: opacity 0.3s ease;
        }
        @media (min-width: 768px) {
            .main-content {
                margin-left: 256px;
            }
        }
    </style>
</head>
<body class="antialiased">
    <div class="hidden md:block fixed left-0 top-0 h-full w-64 bg-[#29292B] shadow-lg z-30">
        <div class="p-5 flex flex-col h-full">
            <div class="text-white text-xl font-bold mb-8">MoyCupon.ru</div>
            <nav class="flex-1 space-y-2">
                <a href="{{ route('dashboard') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white' : '' }}">
                    📊 Портфель
                </a>
                <a href="{{ route('market') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition {{ request()->routeIs('market') ? 'bg-gray-700 text-white' : '' }}">
                    ➕ Добавить
                </a>
                <a href="{{ route('analytics') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition {{ request()->routeIs('analytics') ? 'bg-gray-700 text-white' : '' }}">
                    📈 Аналитика
                </a>
                <a href="{{ route('favorites.index') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition {{ request()->routeIs('favorites.*') ? 'bg-gray-700 text-white' : '' }}">
                    ⭐ Избранное
                </a>
                <!--<a href="{{ route('profile.edit') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    👤 Профиль
                </a>-->
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button type="submit" class="w-full text-left py-2 px-4 text-red-400 hover:bg-red-900/20 rounded transition">
                    🚪 Выйти
                </button>
            </form>
        </div>
    </div>

    <!-- Мобильное меню (выезжает справа) -->
    <div id="mobile-sidebar" class="fixed top-0 right-0 h-full w-64 bg-[#29292B] z-50 transform transition-transform duration-300 ease-in-out translate-x-full md:hidden shadow-lg">
        <div class="p-5 flex flex-col h-full">
            <div class="flex justify-between items-center mb-8">
                <div class="text-white text-xl font-bold">MoyCupon.ru</div>
                <button id="close-sidebar" class="text-white text-2xl">&times;</button>
            </div>
            <nav class="flex-1 space-y-2">
                <a href="{{ route('dashboard') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    📊 Портфель
                </a>
                <a href="{{ route('market') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    ➕ Добавить
                </a>
                <a href="{{ route('analytics') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    📈 Аналитика
                </a>
                <a href="{{ route('favorites.index') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    ⭐ Избранное
                </a>
            <!--<a href="{{ route('profile.edit') }}" class="block py-2 px-4 rounded text-gray-300 hover:bg-gray-700 hover:text-white transition">
                    👤 Профиль
                </a>-->
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button type="submit" class="w-full text-left py-2 px-4 text-red-400 hover:bg-red-900/20 rounded transition">
                    🚪 Выйти
                </button>
            </form>
        </div>
    </div>

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

    <!-- данные -->
    <div class="main-content min-h-screen">
        <!-- Шапка с гамб менб -->
        <div class="md:hidden bg-white shadow-sm sticky top-0 z-20">
            <div class="px-4 py-3 flex justify-between items-center">
                <div class="text-xl font-bold text-gray-800">MoyCupon</div>
                <button id="open-sidebar" class="text-gray-600 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="p-4 md:p-6">
            @if(session('success'))
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('success') }}</div>
            @endif
            @yield('content')
        </div>
    </div>

    <script>
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const openBtn = document.getElementById('open-sidebar');
        const closeBtn = document.getElementById('close-sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            mobileSidebar.classList.remove('translate-x-full');
            mobileSidebar.classList.add('translate-x-0');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            mobileSidebar.classList.remove('translate-x-0');
            mobileSidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        }

        if (openBtn) openBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                closeSidebar();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>