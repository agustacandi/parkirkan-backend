@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Utama</h1>
        <p class="text-gray-500 text-sm mt-1">Ringkasan data aplikasi Parkirkan hari ini.</p>
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

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h3 class="text-lg font-bold text-gray-800 mb-6">Tren Parkir (7 Hari Terakhir)</h3>

    @php
        $maxValue = max($chartValues) > 0 ? max($chartValues) : 1;
    @endphp

    <div class="flex items-end justify-between h-48 space-x-2 md:space-x-4">
        @foreach($chartLabels as $index => $label)
            @php
                $value = $chartValues[$index] ?? 0;
                $height = ($value / $maxValue) * 100;
            @endphp
            <div class="flex flex-col items-center flex-1 group">
                <div class="relative w-full flex justify-center items-end h-full bg-gray-50 rounded-t-lg">
                    <div class="absolute bottom-full mb-2 hidden group-hover:block bg-gray-800 text-white text-xs py-1 px-2 rounded whitespace-nowrap z-10">
                        {{ $value }} Parkir
                    </div>
                    <div class="w-full max-w-[40px] bg-gradient-to-t from-blue-600 to-cyan-400 rounded-t-sm transition-all duration-500 ease-in-out" style="height: {{ $height }}%"></div>
                </div>
                <div class="mt-3 text-xs text-gray-500 font-medium rotate-45 md:rotate-0 origin-left">{{ $label }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
