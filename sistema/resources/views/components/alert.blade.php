@props([
    'type' => 'info', // success, error, warning, info
    'title' => null,
    'dismissible' => false,
])

@php
    $typeConfig = [
        'success' => [
            'bg' => 'bg-emerald-50/90 border-emerald-500/80 text-emerald-900',
            'iconBg' => 'text-emerald-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'error' => [
            'bg' => 'bg-rose-50/90 border-rose-500/80 text-rose-900',
            'iconBg' => 'text-rose-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
        'warning' => [
            'bg' => 'bg-amber-50/90 border-amber-500/80 text-amber-900',
            'iconBg' => 'text-amber-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
        ],
        'info' => [
            'bg' => 'bg-brand-50/90 border-brand-500/80 text-brand-900',
            'iconBg' => 'text-brand-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        ],
    ][$type] ?? [
        'bg' => 'bg-slate-50 border-slate-400 text-slate-800',
        'iconBg' => 'text-slate-500',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    ];
@endphp

<div x-data="{ open: true }" x-show="open"
     {{ $attributes->merge(['class' => 'p-4 rounded-xl border-l-4 shadow-xs text-xs flex items-start gap-3 ' . $typeConfig['bg']]) }}
     role="alert">
    <div class="shrink-0 mt-0.5 {{ $typeConfig['iconBg'] }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            {!! $typeConfig['icon'] !!}
        </svg>
    </div>
    <div class="flex-1 space-y-1">
        @if($title)
            <h5 class="font-bold tracking-tight">{{ $title }}</h5>
        @endif
        <div class="leading-relaxed">
            {{ $slot }}
        </div>
    </div>
    @if($dismissible)
        <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
