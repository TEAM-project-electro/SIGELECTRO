@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Encabezado de Página y Acciones Principales -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-800 border border-brand-200">
                    PORTAFOLIO ESTRATÉGICO
                </span>
                <span class="text-xs text-slate-400">&bull; PGD 2023–2026</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Cartera de Proyectos de Gobierno Digital
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Supervisión institucional de los 10 proyectos adjudicados a Electrocentro S. A.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('objetivos.index') }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span>Catálogo OGD</span>
            </a>

            @can('create', App\Models\Proyecto::class)
                <a href="{{ route('proyectos.create') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-xs transition">
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nuevo Proyecto</span>
                </a>
            @endcan
        </div>
    </div>

    <!-- Métricas Rápidas de Cartera -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Total Proyectos</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $metricas['total_proyectos'] }}</div>
            <span class="text-[11px] text-slate-500 mt-0.5 block font-mono">S/ {{ number_format($metricas['monto_total'], 2) }}</span>
        </div>
        <div class="bg-white p-4.5 rounded-xl border border-emerald-200/80 bg-emerald-50/20 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800 block">En Curso</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1 tabular-nums">{{ $metricas['en_curso'] }}</div>
            <span class="text-[11px] text-emerald-600 mt-0.5 block">En ejecución activa</span>
        </div>
        <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-600 block">Cerrados</span>
            <div class="text-2xl font-extrabold text-slate-800 mt-1 tabular-nums">{{ $metricas['cerrados'] }}</div>
            <span class="text-[11px] text-slate-500 mt-0.5 block">Hitos LB concluidos</span>
        </div>
        <div class="bg-white p-4.5 rounded-xl border border-amber-200/80 bg-amber-50/20 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800 block">Pendientes</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1 tabular-nums">{{ $metricas['pendientes'] }}</div>
            <span class="text-[11px] text-amber-600 mt-0.5 block">Por iniciar</span>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
        <form method="GET" action="{{ route('proyectos.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
            <!-- Buscar por texto -->
            <div class="lg:col-span-4">
                <label for="buscar" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Buscar por código o nombre
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                        placeholder="p. ej. PGD 001 o Ciberseguridad"
                        class="w-full text-xs sm:text-sm pl-9 pr-3 py-2 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <!-- Filtro Estado -->
            <div class="lg:col-span-3">
                <label for="estado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Estado
                </label>
                <select name="estado" id="estado"
                    class="w-full text-xs sm:text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Todos los estados</option>
                    <option value="En curso" {{ request('estado') === 'En curso' ? 'selected' : '' }}>En curso</option>
                    <option value="Cerrado" {{ request('estado') === 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                    <option value="Pendiente" {{ request('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>

            <!-- Filtro Objetivo -->
            <div class="lg:col-span-3">
                <label for="id_objetivo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Objetivo Estratégico
                </label>
                <select name="id_objetivo" id="id_objetivo"
                    class="w-full text-xs sm:text-sm border border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    <option value="">Todos los objetivos</option>
                    @foreach($objetivos as $obj)
                        <option value="{{ $obj->id_objetivo }}" {{ request('id_objetivo') == $obj->id_objetivo ? 'selected' : '' }}>
                            {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 30) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Botones -->
            <div class="lg:col-span-2 flex items-end gap-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    Filtrar
                </button>
                @if(request()->anyFilled(['buscar', 'estado', 'id_objetivo']))
                    <a href="{{ route('proyectos.index') }}"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-semibold transition" title="Limpiar filtros">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla Principal de Cartera -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-100/80 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3.5 text-left">Código PGD</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Proyecto</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Presupuesto (S/)</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Vigencia</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Objetivo OGD</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Responsable Titular</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Estado</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($proyectos as $proy)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Código PGD -->
                            <td class="px-4 py-3.5 font-mono font-bold text-brand-900 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-brand-50 border border-brand-200 text-brand-800">
                                    {{ $proy->codigo_pgd }}
                                </span>
                            </td>

                            <!-- Nombre del Proyecto -->
                            <td class="px-4 py-3.5 max-w-sm">
                                <a href="{{ route('proyectos.show', $proy) }}"
                                   class="font-semibold text-slate-900 hover:text-brand-700 hover:underline transition">
                                    {{ $proy->nombre }}
                                </a>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Fuente: {{ $proy->fuente ?? 'PGD Electrocentro' }}
                                </div>
                            </td>

                            <!-- Monto Presupuestal -->
                            <td class="px-4 py-3.5 text-right font-mono font-semibold text-slate-800 whitespace-nowrap tabular-nums">
                                {{ number_format($proy->monto, 2) }}
                            </td>

                            <!-- Vigencia Años -->
                            <td class="px-4 py-3.5 text-center text-slate-600 font-mono whitespace-nowrap">
                                {{ $proy->anio_inicio }}–{{ $proy->anio_fin }}
                            </td>

                            <!-- Objetivo Estratégico -->
                            <td class="px-4 py-3.5 text-slate-600">
                                <span class="font-bold text-slate-800 font-mono">{{ $proy->objetivo->codigo ?? 'N/A' }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-xs" title="{{ $proy->objetivo->descripcion ?? '' }}">
                                    {{ $proy->objetivo->descripcion ?? '' }}
                                </span>
                            </td>

                            <!-- Responsable -->
                            <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">
                                <div class="font-medium text-slate-800">{{ $proy->responsable->nombre ?? 'Sin asignar' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $proy->responsable->area ?? '' }}</div>
                            </td>

                            <!-- Estado Semántico con texto y punto -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($proy->estado === 'En curso')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        En curso
                                    </span>
                                @elseif($proy->estado === 'Cerrado')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500 mr-1.5"></span>
                                        Cerrado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Pendiente
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('proyectos.show', $proy) }}"
                                        class="inline-flex items-center px-2 py-1 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:bg-brand-50 rounded transition" title="Ver Detalle y Hitos">
                                        Ver
                                    </a>

                                    @can('update', $proy)
                                        <a href="{{ route('proyectos.edit', $proy) }}"
                                            class="inline-flex items-center px-2 py-1 text-xs font-semibold text-amber-700 hover:text-amber-900 hover:bg-amber-50 rounded transition" title="Editar Metadatos">
                                            Editar
                                        </a>
                                    @endcan

                                    @can('delete', $proy)
                                        <form method="POST" action="{{ route('proyectos.destroy', $proy) }}" class="inline"
                                            onsubmit="return confirm('¿Está seguro de eliminar el proyecto {{ $proy->codigo_pgd }}? Si tiene hitos vinculados la acción será bloqueada por integridad.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                <div class="max-w-xs mx-auto">
                                    <p class="text-sm font-semibold text-slate-800">No se encontraron proyectos</p>
                                    <p class="text-xs text-slate-400 mt-1">No hay iniciativas que coincidan con los criterios de búsqueda.</p>
                                    <a href="{{ route('proyectos.index') }}" class="mt-3 inline-block text-xs font-semibold text-brand-700 hover:underline">
                                        Restablecer filtros
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
