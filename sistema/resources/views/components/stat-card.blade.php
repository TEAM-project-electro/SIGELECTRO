@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'badge' => null,
    'variant' => 'neutral', // neutral, primary, success, warning, danger
])

@php
    $variantBorder = [
        'neutral' => 'border-slate-200/90',
        'primary' => 'border-brand-200 bg-brand-50/20',
        'success' => 'border-emerald-200 bg-emerald-50/20',
        'warning' => 'border-amber-200 bg-amber-50/20',
        'danger' => 'border-rose-200 bg-rose-50/20',
    ][$variant] ?? 'border-slate-200/90';

    $valueColor = [
        'neutral' => 'text-slate-900',
        'primary' => 'text-brand-900',
        'success' => 'text-emerald-700',
        'warning' => 'text-amber-800',
        'danger' => 'text-rose-700',
    ][$variant] ?? 'text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => "bg-white p-5 rounded-xl border $variantBorder shadow-xs flex flex-col justify-between transition hover:shadow-sm"]) }}>
    <div class="flex items-start justify-between gap-2">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 leading-tight">
            {{ $title }}
        </span>
        @if($badge)
            <div>{{ $badge }}</div>
        @elseif($icon)
            <div class="p-1.5 rounded-lg bg-slate-100 text-slate-600">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="mt-3">
        <div class="text-2xl font-extrabold tracking-tight {{ $valueColor }} tabular-nums">
            {{ $value }}
        </div>
        @if($subtitle)
            <p class="text-xs text-slate-500 mt-1 font-medium">
                {{ $subtitle }}
            </p>
        @endif
    </div>

    @if(isset($extra))
        <div class="mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500">
            {{ $extra }}
        </div>
    @endif
</div>
