<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - Parkirkan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 overflow-x-hidden">
    <div class="min-h-screen flex flex-col md:flex-row">

        <div class="md:hidden flex items-center justify-between bg-white border-b border-gray-200 p-4 sticky top-0 z-20 shadow-sm">
            <div class="text-xl font-bold text-blue-600">Parkirkan</div>
            <button id="mobile-menu-btn" class="text-gray-500 hover:text-gray-700 focus:outline-none p-2 rounded-md hover:bg-gray-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-30 hidden transition-opacity opacity-0"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 transform -translate-x-full md:translate-x-0 md:sticky md:top-0 md:h-screen transition-transform duration-300 ease-in-out flex flex-col">
            <div class="flex items-center justify-center h-16 border-b border-gray-200 hidden md:flex shrink-0">
                <div class="text-2xl font-bold text-blue-600">Parkirkan</div>
            </div>

            <div class="p-4 flex-1 overflow-y-auto">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4 px-2">Menu Utama</p>
                <nav class="space-y-1">
                    <x-admin.nav-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
                        Dashboard
                    </x-admin.nav-link>
                    <x-admin.nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
                        Pengguna
                    </x-admin.nav-link>
                    <x-admin.nav-link href="{{ route('admin.parking-history.index') }}" :active="request()->routeIs('admin.parking-history.*')">
                        Riwayat Parkir
                    </x-admin.nav-link>
                </nav>
            </div>

            <div class="p-4 border-t border-gray-200 shrink-0">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors border border-transparent hover:border-red-100">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 w-full flex flex-col relative z-0 overflow-y-auto">
            <div class="p-4 md:p-8">
                @if(session('success'))
                    <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('modals')

</body>
</html>
