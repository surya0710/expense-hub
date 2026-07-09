@props(['label', 'value', 'icon' => null, 'trend' => null, 'color' => 'indigo'])

@php
    $gradients = [
        'indigo' => 'from-indigo-500 to-violet-600',
        'emerald' => 'from-emerald-500 to-teal-600',
        'amber' => 'from-amber-500 to-orange-500',
        'rose' => 'from-rose-500 to-pink-600',
        'sky' => 'from-sky-500 to-blue-600',
    ];
    $gradient = $gradients[$color] ?? $gradients['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl bg-gradient-to-br '.$gradient.' p-5 text-white shadow-lg']) }}>
    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-white/10"></div>
    <div class="absolute -bottom-6 -left-2 h-20 w-20 rounded-full bg-white/5"></div>
    <div class="relative">
        <p class="text-sm font-medium text-white/80">{{ $label }}</p>
        <p class="mt-1 text-2xl font-bold tracking-tight">{{ $value }}</p>
        @if($trend)
            <p class="mt-2 text-xs text-white/70">{{ $trend }}</p>
        @endif
    </div>
</div>
