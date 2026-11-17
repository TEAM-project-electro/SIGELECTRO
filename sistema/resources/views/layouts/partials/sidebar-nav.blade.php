@php
    $enlaces = [
        ['ruta' => 'tablero', 'activo' => 'tablero*', 'texto' => 'Tablero'],
        ['ruta' => 'proyectos.index', 'activo' => ['proyectos*', 'hitos*', 'avances*'], 'texto' => 'Proyectos'],
        ['ruta' => 'reportes.imprimir', 'activo' => 'reportes*', 'texto' => 'Reportes'],
    ];
    $admin = [
        ['ruta' => 'objetivos.index', 'activo' => 'objetivos*', 'texto' => 'Objetivos PGD'],
        ['ruta' => 'usuarios.index', 'activo' => 'usuarios*', 'texto' => 'Usuarios'],
        ['ruta' => 'auditoria.index', 'activo' => 'auditoria*', 'texto' => 'Registro de cambios'],
    ];
    $clase = fn ($activo) => request()->routeIs($activo)
        ? 'bg-white/10 text-white font-semibold'
        : 'text-slate-300 hover:bg-white/5 hover:text-white';
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6 text-sm" aria-label="Navegación principal">
    <div class="space-y-1">
        @foreach ($enlaces as $e)
            <a href="{{ route($e['ruta']) }}" class="block px-3 py-2 rounded-lg {{ $clase($e['activo']) }}"
               @if(request()->routeIs($e['activo'])) aria-current="page" @endif>{{ $e['texto'] }}</a>
        @endforeach
    </div>

    @if (Auth::check() && Auth::user()->esAdmin())
        <div class="space-y-1">
            <p class="px-3 pb-1 text-[11px] uppercase tracking-wider text-slate-500">Administración</p>
            @foreach ($admin as $e)
                <a href="{{ route($e['ruta']) }}" class="block px-3 py-2 rounded-lg {{ $clase($e['activo']) }}"
                   @if(request()->routeIs($e['activo'])) aria-current="page" @endif>{{ $e['texto'] }}</a>
            @endforeach
        </div>
    @endif
</nav>
