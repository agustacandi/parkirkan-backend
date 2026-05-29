@extends('layouts.admin')

@section('title', 'Riwayat Parkir')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Riwayat Parkir</h1>
    <p class="text-sm text-slate-500 mt-1">Pantau kendaraan masuk dan keluar.</p>
</div>

<div class="surface overflow-hidden">
    <div class="surface-header">
        <form method="GET" action="{{ route('admin.parking-history.index') }}" class="flex max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pengguna..."
                class="input rounded-r-none">
            <button type="submit" class="btn-primary rounded-l-none px-5">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-600 whitespace-nowrap">
            <thead class="bg-slate-50/60 text-slate-700 text-xs uppercase font-semibold border-b border-slate-200/60">
                <tr>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4">Kendaraan</th>
                    <th class="px-6 py-4">Waktu Masuk</th>
                    <th class="px-6 py-4">Waktu Keluar</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/40">
                @forelse($parkings as $parking)
                <tr class="hover:bg-white/50 transition">
                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $parking->user->name ?? 'User Terhapus' }}</td>
                    <td class="px-6 py-4">{{ $parking->vehicle->license_plate ?? 'Tidak Diketahui' }}</td>
                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($parking->check_in_time)->format('d M Y, H:i') }}</td>
                    <td class="px-6 py-4">
                        {{ $parking->check_out_time ? \Carbon\Carbon::parse($parking->check_out_time)->format('d M Y, H:i') : '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($parking->status == 'completed' || $parking->check_out_time)
                            <x-admin.badge type="success">Selesai</x-admin.badge>
                        @else
                            <x-admin.badge type="warning">Sedang Parkir</x-admin.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('admin.parking-history.show', $parking->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm transition">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-500">Belum ada riwayat parkir.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-200/60 bg-white/30">
        {{ $parkings->links('components.admin.pagination') }}
    </div>
</div>
@endsection
