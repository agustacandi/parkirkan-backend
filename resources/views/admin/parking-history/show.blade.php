@extends('layouts.admin')

@section('title', 'Detail Parkir')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Detail Transaksi Parkir</h1>
        <p class="text-sm text-gray-500 mt-1">Informasi lengkap kendaraan masuk/keluar.</p>
    </div>
    <a href="{{ route('admin.parking-history.index') }}" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-2">
        <div class="p-6 md:p-8 border-b md:border-b-0 md:border-r border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Informasi Utama
            </h3>

            <div class="space-y-5">
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Status</p>
                    @if($parking->status == 'completed' || $parking->check_out_time)
                        <x-admin.badge type="success">Selesai</x-admin.badge>
                    @else
                        <x-admin.badge type="warning">Sedang Parkir</x-admin.badge>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Waktu Masuk</p>
                        <p class="text-gray-900 font-medium">{{ \Carbon\Carbon::parse($parking->check_in_time)->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Waktu Keluar</p>
                        <p class="text-gray-900 font-medium">
                            {{ $parking->check_out_time ? \Carbon\Carbon::parse($parking->check_out_time)->format('d M Y, H:i') : '-' }}
                        </p>
                    </div>
                </div>

                <hr class="border-gray-100">

                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Pengguna</p>
                    <p class="text-gray-900 font-medium">{{ $parking->user->name ?? 'Data Terhapus' }}</p>
                    <p class="text-sm text-gray-500">{{ $parking->user->email ?? '-' }}</p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Kendaraan</p>
                    <div class="inline-block border-2 border-gray-800 rounded px-3 py-1 bg-gray-50 font-mono font-bold text-lg text-gray-800 mt-1">
                        {{ $parking->vehicle->license_plate ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8 bg-gray-50/50 flex flex-col justify-center items-center">
            <h3 class="text-lg font-bold text-gray-900 mb-6 w-full text-left">Foto Kendaraan</h3>

            @if($parking->vehicle_image_url)
                <div class="w-full max-w-sm aspect-video bg-gray-200 rounded-lg overflow-hidden shadow-inner relative border border-gray-300">
                    <img src="{{ Storage::url($parking->vehicle_image_url) }}" alt="Foto Plat Nomor" class="w-full h-full object-cover">
                </div>
                <p class="text-xs text-gray-500 mt-3 text-center">Ditangkap saat check-in</p>
            @else
                <div class="w-full max-w-sm aspect-video bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-400">
                    <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="text-sm font-medium">Tidak ada foto tersedia</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
