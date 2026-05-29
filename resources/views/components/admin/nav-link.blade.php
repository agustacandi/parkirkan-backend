@props(['active'])

@php
$classes = ($active ?? false)
            ? "relative flex items-center px-4 py-3 text-sm font-semibold rounded-2xl bg-white/70 text-slate-900 ring-1 ring-slate-900/5 shadow-sm shadow-slate-900/5 transition-all before:content-[''] before:absolute before:left-1 before:top-2 before:bottom-2 before:w-1 before:rounded-full before:bg-gradient-to-b before:from-blue-600 before:to-cyan-500 mb-1"
            : 'relative flex items-center px-4 py-3 text-sm font-semibold rounded-2xl text-slate-600 hover:text-slate-900 hover:bg-white/50 ring-1 ring-transparent hover:ring-slate-900/5 transition-all mb-1';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
