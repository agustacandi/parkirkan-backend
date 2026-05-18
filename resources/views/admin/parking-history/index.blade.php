@extends('layouts.admin')

@section('title', 'Riwayat Parkir')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Riwayat Parkir</h1>
    <p class="text-sm text-gray-500 mt-1">Pantau kendaraan masuk dan keluar.</p>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
        <form method="GET" action="{{ route('admin.parking-history.index') }}" class="flex max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pengguna..."
                class="flex-1 border-gray-200 rounded-l-lg px-4 py-2 text-sm focus:ring-blue-500 focus:border-blue-500 outline-none">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-lg text-sm font-medium transition">
                Cari
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4">Pengguna</th>
                    <th class="px-6 py-4">Kendaraan</th>
                    <th class="px-6 py-4">Waktu Masuk</th>
                    <th class="px-6 py-4">Waktu Keluar</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($parkings as $parking)
                <tr class="hover:bg-gray-50/50 transition">
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $parking->user->name ?? 'User Terhapus' }}</td>
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
                        <a href="{{ route('admin.parking-history.show', $parking->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada riwayat parkir.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-gray-100">
        {{ $parkings->links('components.admin.pagination') }}
    </div>
</div>
@endsection
