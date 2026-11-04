@props([
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'action' => null,
    'padding' => 'p-6',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden']) }}>
    @if($title || $subtitle || $badge || $action || isset($header))
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white">
            @if(isset($header))
                {{ $header }}
            @else
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2">
                        @if($title)
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $title }}</h3>
                        @endif
                        @if($badge)
                            {{ $badge }}
                        @endif
                    </div>
                    @if($subtitle)
                        <p class="text-xs text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
                @if($action)
                    <div class="flex items-center gap-2">
                        {{ $action }}
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 text-xs text-slate-600">
            {{ $footer }}
        </div>
    @endif
</div>
