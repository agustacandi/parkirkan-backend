<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - Parkirkan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-app antialiased text-slate-900 overflow-x-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50 relative before:content-[''] before:fixed before:inset-0 before:pointer-events-none before:-z-10 before:bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.14),transparent_55%),radial-gradient(circle_at_80%_0%,rgba(34,211,238,0.12),transparent_45%)]">
    <div class="min-h-screen flex flex-col md:flex-row">

        <div class="md:hidden flex items-center justify-between bg-white/70 backdrop-blur-xl border-b border-white/60 p-4 sticky top-0 z-20 shadow-sm shadow-slate-900/5">
            <div class="text-xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">Parkirkan</div>
            <button id="mobile-menu-btn" class="text-slate-500 hover:text-slate-700 focus:outline-none p-2 rounded-xl hover:bg-white/60 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-30 hidden transition-opacity opacity-0"></div>

        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white/70 backdrop-blur-xl border-r border-white/60 shadow-xl shadow-slate-900/5 ring-1 ring-slate-900/5 transform -translate-x-full md:translate-x-0 md:sticky md:top-0 md:my-4 md:ml-4 md:rounded-3xl md:h-[calc(100vh-2rem)] transition-transform duration-300 ease-in-out flex flex-col">
            <div class="flex items-center justify-center h-16 border-b border-slate-200/60 hidden md:flex shrink-0">
                <div class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">Parkirkan</div>
            </div>

            <div class="p-4 flex-1 overflow-y-auto">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 px-2">Menu Utama</p>
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

            <div class="p-4 border-t border-slate-200/60 shrink-0">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full btn-secondary text-red-600 hover:bg-red-50">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <main class="flex-1 w-full flex flex-col relative z-0 overflow-y-auto">
            <div class="p-4 md:p-10">
                @if(session('success'))
                    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-green-200/70 bg-green-50/70 p-4 text-sm text-green-800 shadow-sm shadow-green-500/10" role="alert">
                        <svg class="w-5 h-5 text-green-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="flex-1">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/70 bg-red-50/70 p-4 text-sm text-red-800 shadow-sm shadow-red-500/10" role="alert">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <div class="flex-1">{{ session('error') }}</div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @yield('modals')

</body>
</html>
