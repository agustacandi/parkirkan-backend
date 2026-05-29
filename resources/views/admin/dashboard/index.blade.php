@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Dashboard Utama</h1>
        <p class="text-slate-500 text-sm mt-1">Ringkasan data aplikasi Parkirkan hari ini.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <x-admin.stat-card title="Total Pengguna" :value="$totalUsers">
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </x-slot>
    </x-admin.stat-card>

    <x-admin.stat-card title="Total Kendaraan" :value="$totalVehicles">
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
        </x-slot>
    </x-admin.stat-card>

    <x-admin.stat-card title="Total Parkir Terdata" :value="$totalParkings">
        <x-slot name="icon">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
        </x-slot>
    </x-admin.stat-card>
</div>

<div class="surface p-6">
    <h3 class="text-lg font-extrabold tracking-tight text-slate-800 mb-6">Tren Parkir (7 Hari Terakhir)</h3>

    <div id="parkingTrendsChart" class="w-full"
        data-labels='@json($chartLabels)'
        data-values='@json($chartValues)'></div>
</div>
@endsection
