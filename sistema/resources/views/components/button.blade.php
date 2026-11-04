@props([
    'variant' => 'primary', // primary, secondary, danger, outline, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed shadow-xs';
    
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-xs sm:text-sm',
        'lg' => 'px-5 py-2.5 text-sm',
    ][$size] ?? 'px-4 py-2 text-xs sm:text-sm';

    $variantClasses = [
        'primary' => 'bg-brand-700 hover:bg-brand-800 text-white focus:ring-brand-500 border border-brand-800',
        'secondary' => 'bg-slate-800 hover:bg-slate-900 text-white focus:ring-slate-500 border border-slate-900',
        'danger' => 'bg-rose-700 hover:bg-rose-800 text-white focus:ring-rose-500 border border-rose-800',
        'outline' => 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 focus:ring-brand-500',
        'ghost' => 'bg-transparent hover:bg-slate-100 text-slate-700 border border-transparent shadow-none',
        'amber' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500 border border-amber-700',
    ][$variant] ?? 'bg-brand-700 hover:bg-brand-800 text-white';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}>
        {{ $slot }}
    </button>
@endif
