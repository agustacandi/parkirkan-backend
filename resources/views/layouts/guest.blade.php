<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Login - Parkirkan')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-app antialiased text-slate-900 bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-100 min-h-screen flex items-center justify-center p-6 relative before:content-[''] before:fixed before:inset-0 before:pointer-events-none before:-z-10 before:bg-[radial-gradient(circle_at_20%_20%,rgba(59,130,246,0.18),transparent_55%),radial-gradient(circle_at_80%_0%,rgba(34,211,238,0.14),transparent_45%)]">

    @yield('content')

</body>
</html>
