@extends('layouts.app')

@section('title', 'Bitácora Técnica de Auditoría')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Bitácora Técnica de Auditoría</h1>
                <x-badge variant="neutral" size="sm" class="font-mono">R4 Inmutable</x-badge>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Trazabilidad inmutable de mutaciones en el esquema operativo conforme a la Regla R4 y Decisión 11.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Inmutabilidad BD Activa
            </span>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card
            title="Total Transacciones"
            :value="number_format($estadisticas['total_cambios'])"
            subtitle="Eventos de auditoría registrados"
            variant="neutral"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="En Proyectos"
            :value="number_format($estadisticas['cambios_proyecto'])"
            subtitle="Altas, cierres y ediciones"
            variant="primary"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-brand-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="En Hitos"
            :value="number_format($estadisticas['cambios_hito'])"
            subtitle="Entregables y líneas base"
            variant="success"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card
            title="En Avances"
            :value="number_format($estadisticas['cambios_avance'])"
            subtitle="Cortes financieros (R1/R2)"
            variant="purple"
        >
            <x-slot:icon>
                <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs">
        <form method="GET" action="{{ route('auditoria.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label for="tabla_afectada" class="block text-xs font-bold text-slate-700 mb-1">Tabla Afectada</label>
                <select name="tabla_afectada" id="tabla_afectada"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition">
                    <option value="">Todas las tablas</option>
                    <option value="proyecto" {{ request('tabla_afectada') === 'proyecto' ? 'selected' : '' }}>proyecto</option>
                    <option value="hito" {{ request('tabla_afectada') === 'hito' ? 'selected' : '' }}>hito</option>
                    <option value="avance_presupuestal" {{ request('tabla_afectada') === 'avance_presupuestal' ? 'selected' : '' }}>avance_presupuestal</option>
                    <option value="objetivo_gd" {{ request('tabla_afectada') === 'objetivo_gd' ? 'selected' : '' }}>objetivo_gd</option>
                    <option value="responsable" {{ request('tabla_afectada') === 'responsable' ? 'selected' : '' }}>responsable</option>
                </select>
            </div>

            <div>
                <label for="id_registro" class="block text-xs font-bold text-slate-700 mb-1">ID Registro</label>
                <input type="number" name="id_registro" id="id_registro" value="{{ request('id_registro') }}" placeholder="p. ej. 5"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition">
            </div>

            <div>
                <label for="id_responsable" class="block text-xs font-bold text-slate-700 mb-1">Actor Responsable</label>
                <select name="id_responsable" id="id_responsable"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition">
                    <option value="">Todos los actores</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id_responsable }}" {{ request('id_responsable') == $resp->id_responsable ? 'selected' : '' }}>
                            {{ $resp->nombre }} ({{ $resp->usuario }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="fecha_desde" class="block text-xs font-bold text-slate-700 mb-1">Desde Fecha</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition">
            </div>

            <div>
                <label for="fecha_hasta" class="block text-xs font-bold text-slate-700 mb-1">Hasta Fecha</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}"
                    class="w-full text-xs border border-slate-300 rounded-lg px-2.5 py-2 bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-600 transition">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-brand-900 hover:bg-brand-800 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filtrar
                </button>
                @if(request()->anyFilled(['tabla_afectada', 'id_registro', 'id_responsable', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ route('auditoria.index') }}"
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition" title="Limpiar filtros">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Bitácora -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">Registros Históricos de Cambio</h2>
            <span class="text-xs text-slate-500 font-medium">Mostrando {{ $cambios->count() }} de {{ $cambios->total() }} registros</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50/80 text-slate-600 uppercase font-bold tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3.5 text-center">#</th>
                        <th class="px-4 py-3.5">Fecha y Hora</th>
                        <th class="px-4 py-3.5">Actor (@id_responsable)</th>
                        <th class="px-3 py-3.5 text-center">Tabla</th>
                        <th class="px-3 py-3.5 text-center">ID Reg</th>
                        <th class="px-4 py-3.5">Campo</th>
                        <th class="px-4 py-3.5">Mutación (Anterior &rarr; Nuevo)</th>
                        <th class="px-4 py-3.5">Motivo / Justificación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($cambios as $c)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-4 py-3 text-center font-mono text-slate-400 tabular-nums">
                                #{{ $c->id_cambio }}
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-700 whitespace-nowrap tabular-nums">
                                {{ $c->fecha_hora->format('Y-m-d') }}
                                <span class="text-slate-400 text-[10px] block">{{ $c->fecha_hora->format('H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $c->responsable->nombre ?? 'ID ' . $c->id_responsable }}</span>
                                <span class="text-[11px] text-slate-500">
                                    {{ $c->responsable->usuario ? '@' . $c->responsable->usuario : '' }}
                                    @if($c->responsable && $c->responsable->area)
                                        • {{ $c->responsable->area }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @if($c->tabla_afectada === 'proyecto')
                                    <x-badge variant="primary" size="sm" class="font-mono">proyecto</x-badge>
                                @elseif($c->tabla_afectada === 'hito')
                                    <x-badge variant="success" size="sm" class="font-mono">hito</x-badge>
                                @elseif($c->tabla_afectada === 'avance_presupuestal')
                                    <x-badge variant="purple" size="sm" class="font-mono">avance_pres.</x-badge>
                                @elseif($c->tabla_afectada === 'objetivo_gd')
                                    <x-badge variant="info" size="sm" class="font-mono">objetivo_gd</x-badge>
                                @else
                                    <x-badge variant="neutral" size="sm" class="font-mono">{{ $c->tabla_afectada }}</x-badge>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold text-slate-700 tabular-nums">
                                {{ $c->id_registro }}
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold text-brand-900">
                                {{ $c->campo }}
                            </td>
                            <td class="px-4 py-3 font-mono">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-rose-50 text-rose-800 border border-rose-200 line-through text-[11px] max-w-xs truncate" title="{{ $c->valor_anterior }}">
                                        {{ $c->valor_anterior !== null && $c->valor_anterior !== '' ? $c->valor_anterior : '—' }}
                                    </span>
                                    <span class="text-slate-400">&rarr;</span>
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 font-bold text-[11px] max-w-xs truncate" title="{{ $c->valor_nuevo }}">
                                        {{ $c->valor_nuevo }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs max-w-xs">
                                @if($c->motivoExtension)
                                    <div class="inline-flex items-start gap-1 p-1.5 rounded-lg bg-amber-50/80 border border-amber-200/80 text-amber-900 text-[11px]"
                                         title="{{ $c->motivoExtension->motivo }}">
                                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <span class="font-medium line-clamp-2">
                                            {{ $c->motivoExtension->motivo }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Sin motivo especial</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8">
                                <x-empty-state
                                    title="No se encontraron registros de auditoría"
                                    description="No existen movimientos registrados para los filtros o rango de fechas seleccionados."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cambios->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $cambios->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
