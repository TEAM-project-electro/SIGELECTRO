@auth
@php
    $partes = preg_split('/\s+/', trim(Auth::user()->nombre));
    $iniciales = strtoupper(mb_substr($partes[0] ?? 'U', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
@endphp
<div class="p-3.5 border-t border-white/10 bg-brand-950/60 space-y-2.5">
    <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-brand-700/80 border border-white/15 flex items-center justify-center text-xs font-bold text-white shrink-0" aria-hidden="true">
            {{ $iniciales }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->nombre }}</p>
            <p class="text-[11px] text-slate-400 truncate">{{ Auth::user()->perfil }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-1.5">
        <a href="{{ route('perfil.clave.edit') }}"
           class="inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg border border-white/15 text-[11px] font-medium text-slate-200 hover:bg-white/10 hover:text-white transition-colors duration-150">
            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            <span>Mi clave</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg border border-white/15 text-[11px] font-medium text-slate-200 hover:bg-white/10 hover:text-white transition-colors duration-150">
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Salir</span>
            </button>
        </form>
    </div>
</div>
@endauth
