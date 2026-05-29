@extends('layouts.guest')

@section('title', 'Login Admin')

@section('content')
<div class="w-full max-w-md surface rounded-3xl shadow-xl p-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500 mb-2">Parkirkan</h1>
        <p class="text-slate-500 text-sm">Masuk ke panel administrator</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-red-50/70 border border-red-200/70 text-sm text-red-700 shadow-sm shadow-red-500/10">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-6">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Alamat Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="input py-3 bg-white/50"
                placeholder="admin@parkirkan.com">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Kata Sandi</label>
            <input type="password" name="password" id="password" required
                class="input py-3 bg-white/50"
                placeholder="••••••••">
        </div>

        <button type="submit" class="btn-primary w-full py-3 shadow-lg shadow-blue-500/30 active:scale-[0.98]">
            Masuk Sekarang
        </button>
    </form>
</div>
@endsection
