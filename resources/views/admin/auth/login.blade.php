@extends('layouts.guest')

@section('title', 'Login Admin')

@section('content')
<div class="w-full max-w-md bg-white/80 backdrop-blur-lg rounded-3xl shadow-xl border border-white/40 p-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500 mb-2">Parkirkan</h1>
        <p class="text-gray-500 text-sm">Masuk ke panel administrator</p>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 text-sm text-red-600">
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
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white/50"
                placeholder="admin@parkirkan.com">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Kata Sandi</label>
            <input type="password" name="password" id="password" required
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white/50"
                placeholder="••••••••">
        </div>

        <button type="submit" class="w-full py-3 px-4 flex justify-center items-center rounded-xl text-white font-semibold bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 shadow-lg shadow-blue-500/30 transform transition-all active:scale-[0.98]">
            Masuk Sekarang
        </button>
    </form>
</div>
@endsection
