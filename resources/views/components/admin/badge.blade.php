@props(['type' => 'info'])

@php
$classes = [
    'success' => 'bg-green-100 text-green-800 border-green-200',
    'danger'  => 'bg-red-100 text-red-800 border-red-200',
    'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'info'    => 'bg-blue-100 text-blue-800 border-blue-200',
    'gray'    => 'bg-gray-100 text-gray-800 border-gray-200',
][$type] ?? 'bg-gray-100 text-gray-800 border-gray-200';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $classes }}">
    {{ $slot }}
</span>
