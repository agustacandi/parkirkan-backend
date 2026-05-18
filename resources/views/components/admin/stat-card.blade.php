@props(['title', 'value', 'icon' => null])

<div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm flex items-center justify-between hover:shadow-md transition-shadow">
    <div>
        <p class="text-sm font-medium text-gray-500 mb-1">{{ $title }}</p>
        <h3 class="text-3xl font-bold text-gray-900">{{ $value }}</h3>
    </div>
    @if($icon)
    <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
        {!! $icon !!}
    </div>
    @endif
</div>
