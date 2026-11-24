@extends('layouts.app')

@section('title', 'Cartera de Proyectos')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Cartera de Proyectos de Gobierno Digital</h1>
        <div class="flex flex-wrap items-center gap-3 mt-1.5 text-sm text-slate-600 tabular-nums">
            <span class="font-medium text-slate-800">{{ $metricas['total_proyectos'] }} proyectos</span>
            <span class="text-slate-300" aria-hidden="true">|</span>
            <span>Monto total: <strong class="font-semibold text-brand-900">S/ {{ number_format($metricas['monto_total'], 2) }}</strong></span>
        </div>
    </div>

    @can('create', App\Models\Proyecto::class)
        <a href="{{ route('proyectos.create') }}" class="sig-btn-primary self-start sm:self-auto">
            <svg class="w-4 h-4 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Nuevo proyecto</span>
        </a>
    @endcan
</div>

<form method="GET" action="{{ route('proyectos.index') }}"
      class="sig-panel p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
    <div>
        <label for="buscar" class="block text-xs font-semibold text-slate-700 mb-1">Código o nombre</label>
        <input type="search" name="buscar" id="buscar" value="{{ request('buscar') }}"
               placeholder="Buscar por código o nombre…" autocomplete="off"
               class="sig-input text-xs py-2">
    </div>
    <div>
        <label for="estado" class="block text-xs font-semibold text-slate-700 mb-1">Estado</label>
        <select name="estado" id="estado" class="sig-input text-xs py-2">
            <option value="">Todos</option>
            @foreach(['Pendiente', 'En curso', 'Cerrado'] as $e)
                <option value="{{ $e }}" @selected(request('estado') === $e)>{{ $e }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="id_objetivo" class="block text-xs font-semibold text-slate-700 mb-1">Objetivo PGD</label>
        <select name="id_objetivo" id="id_objetivo" class="sig-input text-xs py-2">
            <option value="">Todos</option>
            @foreach($objetivos as $obj)
                <option value="{{ $obj->id_objetivo }}" @selected(request('id_objetivo') == $obj->id_objetivo)>{{ $obj->codigo }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 sig-btn-primary py-2">Filtrar</button>
        @if(request()->anyFilled(['buscar', 'estado', 'id_objetivo']))
            <a href="{{ route('proyectos.index') }}" class="sig-btn-secondary py-2 px-3">Limpiar</a>
        @endif
    </div>
</form>

<div class="sig-panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-[13px]">
            <thead class="bg-slate-50/90 text-slate-600 text-left text-xs border-b border-slate-200/80">
                <tr>
                    <th class="px-5 py-3 font-semibold">Proyecto</th>
                    <th class="px-4 py-3 font-semibold">Objetivo</th>
                    <th class="px-4 py-3 font-semibold">Responsable</th>
                    <th class="px-4 py-3 font-semibold text-center">Periodo</th>
                    <th class="px-4 py-3 font-semibold text-right">Monto (S/)</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($proyectos as $proy)
                    <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                        <td class="px-5 py-3.5 min-w-[260px]">
                            <a href="{{ route('proyectos.show', $proy) }}"
                               class="group inline-flex items-baseline gap-2 font-semibold text-slate-900 hover:text-brand-700">
                                <span translate="no" class="font-mono text-xs font-bold px-1.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200/70 group-hover:bg-brand-100">{{ $proy->codigo_pgd }}</span>
                                <span class="group-hover:underline">{{ $proy->nombre }}</span>
                            </a>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-mono text-xs text-slate-600">{{ $proy->objetivo->codigo ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">{{ $proy->responsable->nombre ?? 'Sin asignar' }}</td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap tabular-nums text-slate-600">{{ $proy->anio_inicio }}–{{ $proy->anio_fin }}</td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap tabular-nums font-semibold text-slate-900">{{ number_format($proy->monto, 2) }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$proy->estado] ?? 'neutral'">{{ $proy->estado }}</x-badge>
                        </td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-3 text-xs">
                            <a href="{{ route('proyectos.show', $proy) }}" class="font-medium text-slate-600 hover:text-brand-800">Ver</a>
                            @can('update', $proy)
                                <a href="{{ route('proyectos.edit', $proy) }}" class="font-semibold text-brand-700 hover:text-brand-900 hover:underline">Editar</a>
                            @endcan
                            @can('delete', $proy)
                                <form method="POST" action="{{ route('proyectos.destroy', $proy) }}" class="inline"
                                      onsubmit="return confirm('¿Eliminar el proyecto {{ $proy->codigo_pgd }}? Si tiene hitos registrados, la eliminación será bloqueada.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-rose-700 hover:text-rose-900 hover:underline">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                            No se encontraron proyectos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
