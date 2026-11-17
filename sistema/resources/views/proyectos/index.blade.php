@extends('layouts.app')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Cartera de Proyectos de Gobierno Digital</h1>
        <p class="text-sm text-slate-600 mt-1">
            {{ $metricas['total_proyectos'] }} proyectos · S/ {{ number_format($metricas['monto_total'], 2) }}
        </p>
    </div>
    @can('create', App\Models\Proyecto::class)
        <a href="{{ route('proyectos.create') }}" class="px-3 py-2 rounded-lg text-xs font-semibold text-white bg-brand-800 hover:bg-brand-900 self-start sm:self-auto">Nuevo proyecto</a>
    @endcan
</div>

<form method="GET" action="{{ route('proyectos.index') }}" class="bg-white rounded-xl border border-slate-200 p-4 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end text-sm">
    <div>
        <label for="buscar" class="block text-xs font-medium text-slate-600 mb-1">Código o nombre</label>
        <input type="search" name="buscar" id="buscar" value="{{ request('buscar') }}" class="w-full rounded-lg border-slate-300 text-sm">
    </div>
    <div>
        <label for="estado" class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
        <select name="estado" id="estado" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">Todos</option>
            @foreach(['Pendiente', 'En curso', 'Cerrado'] as $e)
                <option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="id_objetivo" class="block text-xs font-medium text-slate-600 mb-1">Objetivo</label>
        <select name="id_objetivo" id="id_objetivo" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">Todos</option>
            @foreach($objetivos as $obj)
                <option value="{{ $obj->id_objetivo }}" @selected(request('id_objetivo') == $obj->id_objetivo)>{{ $obj->codigo }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 px-3 py-2 rounded-lg bg-brand-800 text-white text-xs font-semibold hover:bg-brand-900">Filtrar</button>
        @if(request()->anyFilled(['buscar', 'estado', 'id_objetivo']))
            <a href="{{ route('proyectos.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">Limpiar</a>
        @endif
    </div>
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Proyecto</th>
                    <th class="px-4 py-2.5 font-medium">Objetivo</th>
                    <th class="px-4 py-2.5 font-medium">Responsable</th>
                    <th class="px-4 py-2.5 font-medium text-center">Años</th>
                    <th class="px-4 py-2.5 font-medium text-right">Monto (S/)</th>
                    <th class="px-4 py-2.5 font-medium">Estado</th>
                    <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($proyectos as $proy)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2.5">
                            <a href="{{ route('proyectos.show', $proy) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">
                                <span class="font-mono text-brand-800">{{ $proy->codigo_pgd }}</span> · {{ $proy->nombre }}
                            </a>
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $proy->objetivo->codigo ?? '—' }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">{{ $proy->responsable->nombre ?? 'Sin asignar' }}</td>
                        <td class="px-4 py-2.5 text-center whitespace-nowrap tabular-nums">{{ $proy->anio_inicio }}–{{ $proy->anio_fin }}</td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap tabular-nums">{{ number_format($proy->monto, 2) }}</td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$proy->estado] ?? 'neutral'">{{ $proy->estado }}</x-badge>
                        </td>
                        <td class="px-4 py-2.5 text-right whitespace-nowrap space-x-2">
                            @can('update', $proy)
                                <a href="{{ route('proyectos.edit', $proy) }}" class="text-brand-700 hover:underline">Editar</a>
                            @endcan
                            @can('delete', $proy)
                                <form method="POST" action="{{ route('proyectos.destroy', $proy) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar el proyecto {{ $proy->codigo_pgd }}? Si tiene hitos registrados, la eliminación será bloqueada.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:underline">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No se encontraron proyectos con los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
