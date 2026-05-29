@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')

@section('content')

@if($errors->any())
<div class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200/70 bg-red-50/70 p-4 text-sm text-red-800 shadow-sm shadow-red-500/10">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Gagal menyimpan data pengguna:</h3>
            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Daftar Pengguna</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola data pengguna aplikasi.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
        <button onclick="openFormModal('create')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Pengguna
        </button>
        <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="btn-secondary">
            Import Excel
        </button>
        <a href="{{ route('admin.users.export') }}" class="btn-secondary">
            Export Data
        </a>
    </div>
</div>

<div class="surface overflow-hidden">
    <div class="surface-header">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, atau no HP..."
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
                    <th class="px-6 py-4">Nama Lengkap</th>
                    <th class="px-6 py-4">Kontak</th>
                    <th class="px-6 py-4">Role</th>
                    <th class="px-6 py-4">Terdaftar</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/40">
                @forelse($users as $user)
                <tr class="hover:bg-white/50 transition">
                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $user->name }}</td>
                    <td class="px-6 py-4">
                        <div class="text-slate-900">{{ $user->email }}</div>
                        <div class="text-xs text-slate-500">{{ $user->phone ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($user->role === 'admin')
                            <x-admin.badge type="info">Admin</x-admin.badge>
                        @elseif($user->role === 'security')
                            <x-admin.badge type="warning">Security</x-admin.badge>
                        @else
                            <x-admin.badge type="gray">User</x-admin.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-4 text-right">
                        <button data-user="{{ json_encode($user) }}" onclick="openFormModal('edit', JSON.parse(this.dataset.user))" class="text-blue-600 hover:text-blue-800 font-semibold text-sm transition mr-3">
                            Edit
                        </button>
                        
                        @if($user->id !== auth()->id())
                        <button onclick="openDeleteModal('{{ route('admin.users.destroy', $user->id) }}')" class="text-red-600 hover:text-red-800 font-semibold text-sm transition">
                            Hapus
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-slate-500">Tidak ada data pengguna yang ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t border-slate-200/60 bg-white/30">
        {{ $users->links('components.admin.pagination') }}
    </div>
</div>

<script>
    function openDeleteModal(url) {
        document.getElementById('deleteForm').action = url;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    // Mengontrol Modal Form untuk Create & Edit secara dinamis
    function openFormModal(type, user = null) {
        const modal = document.getElementById('formModal');
        const form = document.getElementById('userForm');
        const modalTitle = document.getElementById('modalTitle');
        const methodInput = document.getElementById('formMethod');
        const passwordHelp = document.getElementById('passwordHelp');
        const passwordInput = document.getElementById('inputPassword');

        if (type === 'create') {
            modalTitle.textContent = 'Tambah Pengguna Baru';
            form.action = '{{ route('admin.users.store') }}';
            methodInput.value = 'POST';
            passwordHelp.classList.add('hidden');
            passwordInput.required = true;
            form.reset();
        } else if (type === 'edit') {
            modalTitle.textContent = 'Edit Pengguna';
            form.action = `/admin/users/${user.id}`;
            methodInput.value = 'PUT'; // Menyamarkan form menjadi PUT request
            passwordHelp.classList.remove('hidden');
            passwordInput.required = false; // Boleh kosong saat diedit
            
            // Isi form dengan data lama
            document.getElementById('inputName').value = user.name;
            document.getElementById('inputEmail').value = user.email;
            document.getElementById('inputPhone').value = user.phone || '';
            document.getElementById('inputRole').value = user.role;
            document.getElementById('inputPassword').value = ''; // Selalu kosongkan password
        }
        
        modal.classList.remove('hidden');
    }
</script>
@endsection

@section('modals')
<div id="formModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="surface w-full max-w-lg overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-200/60 bg-gradient-to-r from-slate-50/60 to-blue-50/40 flex justify-between items-center">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900">Tambah Pengguna</h3>
            <button onclick="document.getElementById('formModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="userForm" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="inputName" required class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="inputEmail" required class="input">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No Handphone</label>
                    <input type="text" name="phone" id="inputPhone" class="input">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select name="role" id="inputRole" required class="select">
                        <option value="user">User</option>
                        <option value="security">Security</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="inputPassword" minlength="8" class="input">
                <p id="passwordHelp" class="text-xs text-gray-500 mt-1 hidden">Kosongkan jika tidak ingin mengubah password.</p>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('formModal').classList.add('hidden')" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="importModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="surface w-full max-w-md overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-slate-200/60 bg-gradient-to-r from-slate-50/60 to-blue-50/40 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900">Import Pengguna</h3>
            <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <div class="mb-3 flex flex-col md:flex-row md:justify-between md:items-end gap-2">
                <label class="block text-sm font-medium text-gray-700">Pilih File Excel/CSV</label>
                <a href="{{ route('admin.users.template') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 transition bg-blue-50 px-2 py-1 rounded-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh Template
                </a>
            </div>
            
            <div class="mb-4">
                <input type="file" name="file" required accept=".xlsx,.csv,.txt" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50/80 file:text-blue-700 hover:file:bg-blue-100/80 border border-slate-200/80 rounded-xl p-2 mt-1 bg-white/60">
                <p class="text-xs text-gray-500 mt-2">Pastikan kolom Anda sesuai dengan format template di atas.</p>
            </div>
            
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="btn-secondary">Batal</button>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="surface w-full max-w-sm p-6 text-center transform transition-all">
        <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4 text-red-500">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-2">Konfirmasi Penghapusan</h3>
        <p class="text-sm text-gray-500 mb-6">Apakah Anda yakin ingin menghapus pengguna ini? Data tidak dapat dikembalikan.</p>

        <form id="deleteForm" method="POST" class="flex justify-center gap-3">
            @csrf
            @method('DELETE')
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden')" class="btn-secondary flex-1">Batal</button>
            <button type="submit" class="btn-danger flex-1">Ya, Hapus</button>
        </form>
    </div>
</div>
@endsection
