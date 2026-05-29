@props(['title', 'value', 'icon' => null])

<div class="surface p-6 flex items-center justify-between hover:shadow-md hover:shadow-blue-500/10 hover:-translate-y-0.5 transition-all">
    <div>
        <p class="text-sm font-semibold text-slate-500 mb-1">{{ $title }}</p>
        <h3 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $value }}</h3>
    </div>
    @if($icon)
    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600/10 to-cyan-500/10 ring-1 ring-blue-600/15 flex items-center justify-center text-blue-700">
        {!! $icon !!}
    </div>
    @endif
</div>
