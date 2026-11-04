@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Encabezado y Acción -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Cartera de Proyectos de Gobierno Digital</h1>
            <p class="text-sm text-slate-500 mt-1">
                Seguimiento institucional del Portafolio PGD 2023–2026 de Electrocentro S. A.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('objetivos.index') }}"
                class="inline-flex items-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 transition">
                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Catálogo OGD
            </a>
            @can('create', App\Models\Proyecto::class)
                <a href="{{ route('proyectos.create') }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-900 hover:bg-blue-800 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Proyecto
                </a>
            @endcan
        </div>
    </div>

    <!-- Métricas Rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-slate-500">Total Proyectos</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $metricas['total_proyectos'] }}</div>
            <span class="text-xs text-slate-500">Monto: S/ {{ number_format($metricas['monto_total'], 2) }}</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-emerald-700">En Curso</span>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ $metricas['en_curso'] }}</div>
            <span class="text-xs text-slate-500">En ejecución activa</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-blue-700">Cerrados</span>
            <div class="text-2xl font-bold text-blue-700 mt-1">{{ $metricas['cerrados'] }}</div>
            <span class="text-xs text-slate-500">Hitos 100% concluidos</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-amber-700">Pendientes</span>
            <div class="text-2xl font-bold text-amber-700 mt-1">{{ $metricas['pendientes'] }}</div>
            <span class="text-xs text-slate-500">Por iniciar</span>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('proyectos.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Buscar por código o nombre</label>
                <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}"
                    placeholder="p. ej. PGD o Ciberseguridad"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div>
                <label for="estado" class="block text-xs font-medium text-slate-600 mb-1">Filtrar por Estado</label>
                <select name="estado" id="estado"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                    <option value="">Todos los estados</option>
                    <option value="En curso" {{ request('estado') === 'En curso' ? 'selected' : '' }}>En curso</option>
                    <option value="Cerrado" {{ request('estado') === 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                    <option value="Pendiente" {{ request('estado') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>

            <div>
                <label for="id_objetivo" class="block text-xs font-medium text-slate-600 mb-1">Objetivo Estratégico</label>
                <select name="id_objetivo" id="id_objetivo"
                    class="w-full text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                    <option value="">Todos los objetivos</option>
                    @foreach($objetivos as $obj)
                        <option value="{{ $obj->id_objetivo }}" {{ request('id_objetivo') == $obj->id_objetivo ? 'selected' : '' }}>
                            {{ $obj->codigo }} - {{ Str::limit($obj->descripcion, 35) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-sm font-medium transition">
                    Filtrar
                </button>
                @if(request()->anyFilled(['buscar', 'estado', 'id_objetivo', 'id_responsable']))
                    <a href="{{ route('proyectos.index') }}"
                        class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm transition" title="Limpiar filtros">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Proyectos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 text-xs uppercase font-semibold">
                    <tr>
                        <th scope="col" class="px-4 py-3.5 text-left">Código PGD</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Proyecto</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Monto (S/)</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Período</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Objetivo GD</th>
                        <th scope="col" class="px-4 py-3.5 text-left">Responsable</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Estado</th>
                        <th scope="col" class="px-4 py-3.5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($proyectos as $proy)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-blue-900 whitespace-nowrap">
                                {{ $proy->codigo_pgd }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-900 max-w-xs">
                                <a href="{{ route('proyectos.show', $proy) }}" class="hover:text-blue-900 hover:underline">
                                    {{ $proy->nombre }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-800 whitespace-nowrap">
                                {{ number_format($proy->monto, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600 whitespace-nowrap">
                                {{ $proy->anio_inicio }}–{{ $proy->anio_fin }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs">
                                <span class="font-semibold text-slate-800">{{ $proy->objetivo->codigo ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs whitespace-nowrap">
                                {{ $proy->responsable->nombre ?? 'Sin asignar' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($proy->estado === 'En curso') bg-emerald-100 text-emerald-800
                                    @elseif($proy->estado === 'Cerrado') bg-blue-100 text-blue-800
                                    @else bg-amber-100 text-amber-800 @endif">
                                    {{ $proy->estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap space-x-2">
                                <a href="{{ route('proyectos.show', $proy) }}"
                                    class="inline-flex items-center text-xs font-medium text-blue-900 hover:text-blue-700" title="Ver Detalle">
                                    Ver
                                </a>

                                @can('update', $proy)
                                    <a href="{{ route('proyectos.edit', $proy) }}"
                                        class="inline-flex items-center text-xs font-medium text-amber-700 hover:text-amber-900" title="Editar Proyecto">
                                        Editar
                                    </a>
                                @endcan

                                @can('delete', $proy)
                                    <form method="POST" action="{{ route('proyectos.destroy', $proy) }}" class="inline"
                                        onsubmit="return confirm('¿Está seguro de eliminar el proyecto {{ $proy->codigo_pgd }}? Si tiene hitos vinculados la acción será bloqueada por integridad.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">
                                            Eliminar
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                No se encontraron proyectos que coincidan con los criterios de búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
