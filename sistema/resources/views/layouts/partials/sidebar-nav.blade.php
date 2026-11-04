<nav class="space-y-6">
    <!-- Grupo 1: Supervisión y Operación -->
    <div>
        <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
            Supervisión y Control
        </span>
        <div class="space-y-1">
            <!-- Tablero Gerencial -->
            <a href="{{ route('tablero') }}"
               class="flex items-center gap-3 py-2.5 rounded-lg text-xs transition {{ request()->routeIs('tablero*') ? 'bg-brand-800 text-white font-semibold border-l-4 border-amber-400 pl-3 shadow-xs' : 'text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium' }}">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Tablero Gerencial</span>
            </a>

            <!-- Cartera de Proyectos -->
            <a href="{{ route('proyectos.index') }}"
               class="flex items-center gap-3 py-2.5 rounded-lg text-xs transition {{ request()->routeIs('proyectos*') ? 'bg-brand-800 text-white font-semibold border-l-4 border-amber-400 pl-3 shadow-xs' : 'text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium' }}">
                <svg class="w-4 h-4 text-brand-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <span>Cartera de Proyectos</span>
            </a>

            <!-- Catálogo de Objetivos PGD -->
            <a href="{{ route('objetivos.index') }}"
               class="flex items-center gap-3 py-2.5 rounded-lg text-xs transition {{ request()->routeIs('objetivos*') ? 'bg-brand-800 text-white font-semibold border-l-4 border-amber-400 pl-3 shadow-xs' : 'text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium' }}">
                <svg class="w-4 h-4 text-brand-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                <span>Catálogo de Objetivos (6)</span>
            </a>
        </div>
    </div>

    <!-- Grupo 2: Gobernanza y Administración (Solo Administrador) -->
    @if(Auth::check() && Auth::user()->esAdmin())
        <div>
            <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                Gobernanza y Control
            </span>
            <div class="space-y-1">
                <!-- Bitácora de Auditoría R4 -->
                <a href="{{ route('auditoria.index') }}"
                   class="flex items-center gap-3 py-2.5 rounded-lg text-xs transition {{ request()->routeIs('auditoria*') ? 'bg-brand-800 text-white font-semibold border-l-4 border-amber-400 pl-3 shadow-xs' : 'text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium' }}">
                    <svg class="w-4 h-4 text-purple-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <span>Bitácora de Auditoría</span>
                </a>

                <!-- Administración de Usuarios -->
                <a href="{{ route('usuarios.index') }}"
                   class="flex items-center gap-3 py-2.5 rounded-lg text-xs transition {{ request()->routeIs('usuarios*') ? 'bg-brand-800 text-white font-semibold border-l-4 border-amber-400 pl-3 shadow-xs' : 'text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium' }}">
                    <svg class="w-4 h-4 text-purple-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Usuarios y Perfiles</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Grupo 3: Reportes y Diagnóstico -->
    <div>
        <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
            Salidas y Diagnóstico
        </span>
        <div class="space-y-1">
            <!-- Vista de Impresión A4 -->
            <a href="{{ route('reportes.imprimir') }}" target="_blank"
               class="flex items-center gap-3 py-2.5 rounded-lg text-xs text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium transition">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Reporte Ejecutivo A4</span>
            </a>

            <!-- Diagnóstico de Motor -->
            <a href="{{ route('salud') }}" target="_blank"
               class="flex items-center gap-3 py-2.5 rounded-lg text-xs text-slate-300 hover:bg-brand-900/90 hover:text-white pl-4 font-medium transition">
                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Diagnóstico del Motor</span>
            </a>
        </div>
    </div>
</nav>
