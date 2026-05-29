@props(['type' => 'info'])

@php
$classes = [
    'success' => 'bg-emerald-50/80 text-emerald-700 border-emerald-200/70',
    'danger'  => 'bg-red-50/80 text-red-700 border-red-200/70',
    'warning' => 'bg-amber-50/80 text-amber-700 border-amber-200/70',
    'info'    => 'bg-blue-50/80 text-blue-700 border-blue-200/70',
    'gray'    => 'bg-slate-100/80 text-slate-700 border-slate-200/70',
][$type] ?? 'bg-slate-100/80 text-slate-700 border-slate-200/70';
@endphp

<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold border shadow-sm shadow-slate-900/5 {{ $classes }}">
    {{ $slot }}
</span>
