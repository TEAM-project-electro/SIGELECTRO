@extends('layouts.app')

@section('title', 'Bitácora de Auditoría')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Bitácora Técnica de Auditoría</h1>
            <p class="text-sm text-slate-600 mt-1 tabular-nums">
                Historial de modificaciones registradas en el sistema ({{ number_format($estadisticas['total_cambios']) }} eventos)
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-energy-50 text-energy-700 border border-energy-100 self-start sm:self-auto">
            <span class="w-1.5 h-1.5 rounded-full bg-energy-500" aria-hidden="true"></span>
            Inmutabilidad BD Activa
        </span>
    </div>

    <div class="sig-panel p-4">
        <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label for="tabla_afectada" class="block text-xs font-semibold text-slate-700 mb-1">Entidad</label>
                <select name="tabla_afectada" id="tabla_afectada" class="sig-input text-xs py-2">
                    <option value="">Todas</option>
                    <option value="proyecto" @selected(request('tabla_afectada') === 'proyecto')>proyecto</option>
                    <option value="hito" @selected(request('tabla_afectada') === 'hito')>hito</option>
                    <option value="avance_presupuestal" @selected(request('tabla_afectada') === 'avance_presupuestal')>avance_presupuestal</option>
                    <option value="objetivo_gd" @selected(request('tabla_afectada') === 'objetivo_gd')>objetivo_gd</option>
                    <option value="responsable" @selected(request('tabla_afectada') === 'responsable')>responsable</option>
                </select>
            </div>

            <div>
                <label for="id_registro" class="block text-xs font-semibold text-slate-700 mb-1">ID Registro</label>
                <input type="number" inputmode="numeric" name="id_registro" id="id_registro" value="{{ request('id_registro') }}" placeholder="Ej. 5…" class="sig-input text-xs py-2">
            </div>

            <div>
                <label for="id_responsable" class="block text-xs font-semibold text-slate-700 mb-1">Usuario</label>
                <select name="id_responsable" id="id_responsable" class="sig-input text-xs py-2">
                    <option value="">Todos</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id_responsable }}" @selected(request('id_responsable') == $resp->id_responsable)>
                            {{ $resp->nombre }} ({{ $resp->usuario }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha_desde" class="block text-xs font-semibold text-slate-700 mb-1">Desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}" class="sig-input text-xs py-2 font-mono">
            </div>

            <div>
                <label for="fecha_hasta" class="block text-xs font-semibold text-slate-700 mb-1">Hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}" class="sig-input text-xs py-2 font-mono">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 sig-btn-primary py-2">Filtrar</button>
                @if(request()->anyFilled(['tabla_afectada', 'id_registro', 'id_responsable', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ route('auditoria.index') }}" class="sig-btn-secondary py-2 px-3">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <div class="sig-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 text-[13px] text-left">
                <thead class="bg-slate-50/90 text-slate-600 text-xs">
                    <tr>
                        <th class="px-5 py-3 font-semibold">Fecha y Hora</th>
                        <th class="px-4 py-3 font-semibold">Usuario</th>
                        <th class="px-3 py-3 text-center font-semibold">Entidad</th>
                        <th class="px-3 py-3 text-center font-semibold">ID</th>
                        <th class="px-4 py-3 font-semibold">Campo</th>
                        <th class="px-4 py-3 font-semibold">Cambio (Anterior &rarr; Nuevo)</th>
                        <th class="px-5 py-3 font-semibold">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($cambios as $c)
                        <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                            <td class="px-5 py-3 font-mono text-xs text-slate-600 whitespace-nowrap tabular-nums">
                                {{ $c->fecha_hora->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-semibold text-slate-900">
                                {{ $c->responsable->nombre ?? 'ID ' . $c->id_responsable }}
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span translate="no" class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700 border border-slate-200/80">{{ $c->tabla_afectada }}</span>
                            </td>
                            <td class="px-3 py-3 text-center font-mono text-xs text-slate-600 tabular-nums">
                                {{ $c->id_registro }}
                            </td>
                            <td translate="no" class="px-4 py-3 font-mono text-xs font-semibold text-brand-900">
                                {{ $c->campo }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                <span class="text-slate-500 line-through mr-1">{{ $c->valor_anterior !== null && $c->valor_anterior !== '' ? $c->valor_anterior : '—' }}</span>
                                <span class="text-slate-400" aria-hidden="true">&rarr;</span>
                                <span class="font-semibold text-slate-900 ml-1">{{ $c->valor_nuevo }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-600 max-w-xs break-words">
                                {{ $c->motivoExtension?->motivo ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">
                                No se encontraron registros de auditoría para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cambios->hasPages())
            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/70">
                {{ $cambios->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
