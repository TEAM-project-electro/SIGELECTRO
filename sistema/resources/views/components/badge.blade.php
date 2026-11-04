@props([
    'variant' => 'neutral', // primary, success, warning, danger, neutral, info, purple
    'size' => 'md', // sm, md, lg
])

@php
    $baseClasses = 'inline-flex items-center font-semibold rounded-full border transition-colors';
    
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ][$size] ?? 'px-2.5 py-0.5 text-xs';

    $variantClasses = [
        'primary' => 'bg-brand-50 text-brand-700 border-brand-200',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200',
        'danger' => 'bg-rose-50 text-rose-800 border-rose-200',
        'neutral' => 'bg-slate-100 text-slate-700 border-slate-200',
        'info' => 'bg-sky-50 text-sky-800 border-sky-200',
        'purple' => 'bg-purple-50 text-purple-800 border-purple-200',
    ][$variant] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}>
    {{ $slot }}
</span>
