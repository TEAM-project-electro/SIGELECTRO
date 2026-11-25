@php
    $enlaces = [
        [
            'ruta' => 'tablero',
            'activo' => 'tablero*',
            'texto' => 'Tablero',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-18v6h8V3h-8z"/>',
        ],
        [
            'ruta' => 'proyectos.index',
            'activo' => ['proyectos*', 'hitos*', 'avances*', 'abastecimiento*'],
            'texto' => 'Proyectos',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>',
        ],
        [
            'ruta' => 'priorizacion.index',
            'activo' => 'priorizacion*',
            'texto' => 'Priorización',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>',
        ],
        [
            'ruta' => 'riesgos.index',
            'activo' => 'riesgos*',
            'texto' => 'Riesgos',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
        ],
        [
            'ruta' => 'alertas.index',
            'activo' => 'alertas*',
            'texto' => 'Alertas',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        ],
        [
            'ruta' => 'reportes.imprimir',
            'activo' => 'reportes*',
            'texto' => 'Reportes',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        ],
        [
            'ruta' => 'asistente.index',
            'activo' => 'asistente*',
            'texto' => 'Asistente',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5m-9 6l2.5-3H18a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v14z"/>',
        ],
    ];

    $admin = [
        [
            'ruta' => 'objetivos.index',
            'activo' => 'objetivos*',
            'texto' => 'Objetivos PGD',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        ],
        [
            'ruta' => 'usuarios.index',
            'activo' => 'usuarios*',
            'texto' => 'Usuarios',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        ],
        [
            'ruta' => 'auditoria.index',
            'activo' => 'auditoria*',
            'texto' => 'Registro de cambios',
            'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ],
    ];
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-[13px]" aria-label="Navegación principal">
    <div class="space-y-1">
        @foreach ($enlaces as $e)
            @php $esActivo = request()->routeIs($e['activo']); @endphp
            <a href="{{ route($e['ruta']) }}"
               class="relative flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors duration-150 {{ $esActivo ? 'bg-white/12 text-white font-semibold' : 'text-slate-300 hover:bg-white/6 hover:text-white' }}"
               @if($esActivo) aria-current="page" @endif>
                @if($esActivo)
                    <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-energy-500" aria-hidden="true"></span>
                @endif
                <svg class="w-[18px] h-[18px] shrink-0 {{ $esActivo ? 'text-brand-300' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    {!! $e['icono'] !!}
                </svg>
                <span class="truncate">{{ $e['texto'] }}</span>
            </a>
        @endforeach
    </div>

    @if (Auth::check() && Auth::user()->esAdmin())
        <div class="space-y-1 pt-2 border-t border-white/10">
            <p class="px-3 pb-1.5 text-[11px] font-medium text-slate-400">Administración</p>
            @foreach ($admin as $e)
                @php $esActivo = request()->routeIs($e['activo']); @endphp
                <a href="{{ route($e['ruta']) }}"
                   class="relative flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors duration-150 {{ $esActivo ? 'bg-white/12 text-white font-semibold' : 'text-slate-300 hover:bg-white/6 hover:text-white' }}"
                   @if($esActivo) aria-current="page" @endif>
                    @if($esActivo)
                        <span class="absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-energy-500" aria-hidden="true"></span>
                    @endif
                    <svg class="w-[18px] h-[18px] shrink-0 {{ $esActivo ? 'text-brand-300' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        {!! $e['icono'] !!}
                    </svg>
                    <span class="truncate">{{ $e['texto'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</nav>
