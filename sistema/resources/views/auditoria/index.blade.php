@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Bitácora Técnica de Auditoría</h1>
            <p class="text-sm text-slate-500 mt-1">
                Trazabilidad inmutable de mutaciones en el esquema operativo conforme a la Regla R4 y Decisión 11.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Inmutabilidad BD Activa
            </span>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-slate-500">Total Transacciones</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($estadisticas['total_cambios']) }}</div>
            <span class="text-xs text-slate-400">Eventos de auditoría</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-blue-700">En Proyectos</span>
            <div class="text-2xl font-bold text-blue-700 mt-1">{{ number_format($estadisticas['cambios_proyecto']) }}</div>
            <span class="text-xs text-slate-400">Altas, cierres y ediciones</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-emerald-700">En Hitos</span>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($estadisticas['cambios_hito']) }}</div>
            <span class="text-xs text-slate-400">Entregables y cierres</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold uppercase text-purple-700">En Avances</span>
            <div class="text-2xl font-bold text-purple-700 mt-1">{{ number_format($estadisticas['cambios_avance']) }}</div>
            <span class="text-xs text-slate-400">Cortes financieros</span>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label for="tabla_afectada" class="block text-xs font-medium text-slate-600 mb-1">Tabla Afectada</label>
                <select name="tabla_afectada" id="tabla_afectada"
                    class="w-full text-xs border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                    <option value="">Todas las tablas</option>
                    <option value="proyecto" {{ request('tabla_afectada') === 'proyecto' ? 'selected' : '' }}>proyecto</option>
                    <option value="hito" {{ request('tabla_afectada') === 'hito' ? 'selected' : '' }}>hito</option>
                    <option value="avance_presupuestal" {{ request('tabla_afectada') === 'avance_presupuestal' ? 'selected' : '' }}>avance_presupuestal</option>
                    <option value="objetivo_gd" {{ request('tabla_afectada') === 'objetivo_gd' ? 'selected' : '' }}>objetivo_gd</option>
                    <option value="responsable" {{ request('tabla_afectada') === 'responsable' ? 'selected' : '' }}>responsable</option>
                </select>
            </div>

            <div>
                <label for="id_registro" class="block text-xs font-medium text-slate-600 mb-1">ID Registro</label>
                <input type="number" name="id_registro" id="id_registro" value="{{ request('id_registro') }}" placeholder="p. ej. 5"
                    class="w-full text-xs border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div>
                <label for="id_responsable" class="block text-xs font-medium text-slate-600 mb-1">Actor Responsable</label>
                <select name="id_responsable" id="id_responsable"
                    class="w-full text-xs border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
                    <option value="">Todos los actores</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id_responsable }}" {{ request('id_responsable') == $resp->id_responsable ? 'selected' : '' }}>
                            {{ $resp->nombre }} ({{ $resp->usuario }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha_desde" class="block text-xs font-medium text-slate-600 mb-1">Desde Fecha</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                    class="w-full text-xs border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-xs font-medium transition">
                    Filtrar
                </button>
                @if(request()->anyFilled(['tabla_afectada', 'id_registro', 'id_responsable', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ route('auditoria.index') }}"
                        class="px-3 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs transition" title="Limpiar filtros">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Bitácora -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th class="px-3 py-3 text-center"># Cambio</th>
                        <th class="px-4 py-3 text-left">Fecha y Hora</th>
                        <th class="px-4 py-3 text-left">Actor (@id_responsable)</th>
                        <th class="px-3 py-3 text-center">Tabla</th>
                        <th class="px-3 py-3 text-center">ID Reg</th>
                        <th class="px-3 py-3 text-left">Campo</th>
                        <th class="px-4 py-3 text-left">Valor Anterior</th>
                        <th class="px-4 py-3 text-left">Valor Nuevo</th>
                        <th class="px-4 py-3 text-left">Motivo / Justificación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($cambios as $c)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-3 py-3 text-center font-mono text-slate-400">
                                {{ $c->id_cambio }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap">
                                {{ $c->fecha_hora->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $c->responsable->nombre ?? 'ID ' . $c->id_responsable }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $c->responsable->area ?? '' }}</span>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-mono font-bold
                                    @if($c->tabla_afectada === 'proyecto') bg-blue-100 text-blue-800
                                    @elseif($c->tabla_afectada === 'hito') bg-emerald-100 text-emerald-800
                                    @elseif($c->tabla_afectada === 'avance_presupuestal') bg-purple-100 text-purple-800
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ $c->tabla_afectada }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold text-slate-700">
                                {{ $c->id_registro }}
                            </td>
                            <td class="px-3 py-3 font-mono font-semibold text-blue-900">
                                {{ $c->campo }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-500 max-w-xs truncate" title="{{ $c->valor_anterior }}">
                                {{ $c->valor_anterior ?: '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 max-w-xs truncate" title="{{ $c->valor_nuevo }}">
                                {{ $c->valor_nuevo }}
                            </td>
                            <td class="px-4 py-3 text-xs max-w-xs">
                                @if($c->motivoExtension)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200 text-[11px] font-medium"
                                        title="{{ $c->motivoExtension->motivo }}">
                                        <svg class="w-3 h-3 mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        {{ Str::limit($c->motivoExtension->motivo, 30) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                                No se encontraron registros de auditoría para los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cambios->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $cambios->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
