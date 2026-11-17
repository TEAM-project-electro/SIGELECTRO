@extends('layouts.app')

@section('title', 'Bitácora de Auditoría')

@section('content')
<div class="space-y-5">
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Bitácora Técnica de Auditoría</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Historial de modificaciones registradas en el sistema ({{ number_format($estadisticas['total_cambios']) }} eventos)
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 self-start sm:self-auto">
            Inmutabilidad BD Activa
        </span>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label for="tabla_afectada" class="block text-xs font-semibold text-slate-700 mb-1">Entidad</label>
                <select name="tabla_afectada" id="tabla_afectada"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white focus:border-brand-600 focus:outline-none">
                    <option value="">Todas</option>
                    <option value="proyecto" {{ request('tabla_afectada') === 'proyecto' ? 'selected' : '' }}>proyecto</option>
                    <option value="hito" {{ request('tabla_afectada') === 'hito' ? 'selected' : '' }}>hito</option>
                    <option value="avance_presupuestal" {{ request('tabla_afectada') === 'avance_presupuestal' ? 'selected' : '' }}>avance_presupuestal</option>
                    <option value="objetivo_gd" {{ request('tabla_afectada') === 'objetivo_gd' ? 'selected' : '' }}>objetivo_gd</option>
                    <option value="responsable" {{ request('tabla_afectada') === 'responsable' ? 'selected' : '' }}>responsable</option>
                </select>
            </div>

            <div>
                <label for="id_registro" class="block text-xs font-semibold text-slate-700 mb-1">ID Registro</label>
                <input type="number" name="id_registro" id="id_registro" value="{{ request('id_registro') }}" placeholder="Ej. 5"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 focus:border-brand-600 focus:outline-none">
            </div>

            <div>
                <label for="id_responsable" class="block text-xs font-semibold text-slate-700 mb-1">Usuario</label>
                <select name="id_responsable" id="id_responsable"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-white focus:border-brand-600 focus:outline-none">
                    <option value="">Todos</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id_responsable }}" {{ request('id_responsable') == $resp->id_responsable ? 'selected' : '' }}>
                            {{ $resp->nombre }} ({{ $resp->usuario }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha_desde" class="block text-xs font-semibold text-slate-700 mb-1">Desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 focus:border-brand-600 focus:outline-none">
            </div>

            <div>
                <label for="fecha_hasta" class="block text-xs font-semibold text-slate-700 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 focus:border-brand-600 focus:outline-none">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold transition">
                    Filtrar
                </button>
                @if(request()->anyFilled(['tabla_afectada', 'id_registro', 'id_responsable', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ route('auditoria.index') }}"
                        class="px-3 py-2 border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-xs font-semibold transition">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Cambios -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-[11px]">
                    <tr>
                        <th class="px-4 py-3">Fecha y Hora</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-3 py-3 text-center">Entidad</th>
                        <th class="px-3 py-3 text-center">ID</th>
                        <th class="px-4 py-3">Campo</th>
                        <th class="px-4 py-3">Cambio (Anterior &rarr; Nuevo)</th>
                        <th class="px-4 py-3">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($cambios as $c)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono text-slate-600 whitespace-nowrap">
                                {{ $c->fecha_hora->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $c->responsable->nombre ?? 'ID ' . $c->id_responsable }}</span>
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-700 whitespace-nowrap">
                                {{ $c->tabla_afectada }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-slate-600">
                                {{ $c->id_registro }}
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold text-brand-900">
                                {{ $c->campo }}
                            </td>
                            <td class="px-4 py-3 font-mono">
                                <span class="text-slate-500 line-through mr-1">{{ $c->valor_anterior !== null && $c->valor_anterior !== '' ? $c->valor_anterior : '—' }}</span>
                                <span class="text-slate-400">&rarr;</span>
                                <span class="font-semibold text-slate-900 ml-1">{{ $c->valor_nuevo }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $c->motivoExtension?->motivo ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No se encontraron registros de auditoría para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cambios->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50">
                {{ $cambios->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
