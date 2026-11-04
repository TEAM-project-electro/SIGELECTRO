<div class="p-3.5 bg-brand-900/60 border-t border-white/10">
    @auth
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center space-x-2.5 min-w-0">
                <!-- Avatar tipográfico con inicial -->
                <div class="w-8 h-8 rounded-full bg-brand-700 border border-amber-400/40 text-amber-300 flex items-center justify-center font-bold text-xs shrink-0">
                    {{ strtoupper(substr(Auth::user()->nombre, 0, 1)) }}
                </div>
                <div class="min-w-0 leading-tight">
                    <span class="text-xs font-bold text-white block truncate" title="{{ Auth::user()->nombre }}">
                        {{ Auth::user()->nombre }}
                    </span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider
                            @if(Auth::user()->esAdmin()) bg-purple-900/80 text-purple-200 border border-purple-500/40
                            @elseif(Auth::user()->esResponsable()) bg-emerald-900/80 text-emerald-200 border border-emerald-500/40
                            @else bg-amber-900/80 text-amber-200 border border-amber-500/40 @endif">
                            {{ Auth::user()->perfil }}
                        </span>
                        <span class="text-[10px] text-slate-400 truncate">&bull; {{ Auth::user()->area }}</span>
                    </div>
                </div>
            </div>

            <!-- Botón Salir -->
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" title="Cerrar sesión"
                    class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-white/5 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    @else
        <div class="text-center py-1">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-amber-400 hover:underline">
                Iniciar Sesión &rarr;
            </a>
        </div>
    @endauth
</div>
