@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ modalReabrir: false }">

    {{-- Encabezado e información general del proyecto --}}
    <div class="bg-white p-5 rounded-xl border border-slate-200">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-900 text-white">PGD {{ $proyecto->codigo_pgd }}</span>
                    <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$proyecto->estado] ?? 'neutral'">{{ $proyecto->estado }}</x-badge>
                    <span class="text-xs text-slate-500">{{ $proyecto->anio_inicio }}–{{ $proyecto->anio_fin }}</span>
                </div>
                <h1 class="text-xl font-bold text-slate-900 mt-1.5">{{ $proyecto->nombre }}</h1>
                <p class="text-xs text-slate-500 mt-1">
                    Fuente: {{ $proyecto->fuente ?? 'PGD Electrocentro' }} · Última actualización: <span class="font-mono">{{ $proyecto->fecha_actualizacion }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('proyectos.index') }}" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50">Volver</a>

                @can('update', $proyecto)
                    <a href="{{ route('proyectos.edit', $proyecto) }}" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold">Editar proyecto</a>
                @endcan

                @if($proyecto->estado !== 'Cerrado')
                    @can('cerrar', $proyecto)
                        <form method="POST" action="{{ route('proyectos.cerrar', $proyecto) }}" class="inline"
                              onsubmit="return confirm('¿Cerrar formalmente el proyecto? Todos los hitos de línea base deben estar concluidos.');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">Cerrar proyecto</button>
                        </form>
                    @endcan
                @else
                    @can('reabrir', $proyecto)
                        <button type="button" @click="modalReabrir = true" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold">Reabrir proyecto</button>
                    @endcan
                @endif
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100 text-xs">
            <div>
                <dt class="text-slate-500">Objetivo de Gobierno Digital</dt>
                <dd class="font-medium text-slate-900 mt-0.5">{{ $proyecto->objetivo->codigo ?? '—' }} · {{ $proyecto->objetivo->descripcion ?? '' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Responsable</dt>
                <dd class="font-medium text-slate-900 mt-0.5">{{ $proyecto->responsable->nombre ?? 'Sin asignar' }} <span class="text-slate-500">({{ $proyecto->responsable->area ?? '' }})</span></dd>
            </div>
            <div>
                <dt class="text-slate-500">Monto estimado</dt>
                <dd class="font-semibold text-slate-900 font-mono tabular-nums mt-0.5">S/ {{ number_format($proyecto->monto, 2) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Resumen de indicadores del proyecto --}}
    @if($indicador)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500">Avance de Hitos (LB)</p>
                <p class="text-xl font-bold text-slate-900 mt-1 tabular-nums">{{ $indicador->avance_hitos_pct !== null ? $indicador->avance_hitos_pct . '%' : 'Sin hitos' }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $indicador->hitos_lb_concluidos }} de {{ $indicador->hitos_lb_total }} concluidos</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500">Cronograma</p>
                <p class="text-xl font-bold mt-1 {{ $indicador->tiene_retraso ? 'text-rose-700' : 'text-emerald-700' }}">{{ $indicador->tiene_retraso ? 'Con retraso' : 'Al día' }}</p>
                <p class="text-xs text-slate-500 mt-1">Hitos vencidos: {{ $indicador->hitos_lb_vencidos }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500">Gasto acumulado</p>
                <p class="text-xl font-bold text-slate-900 mt-1 tabular-nums">S/ {{ number_format($indicador->gasto_acumulado ?? 0, 2) }}</p>
                <p class="text-xs text-slate-500 mt-1">Planificado: S/ {{ number_format($indicador->presupuesto_planificado ?? 0, 2) }}</p>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200">
                <p class="text-xs text-slate-500">Desviación Presupuestal</p>
                <p class="text-xl font-bold mt-1 tabular-nums {{ ($indicador->desviacion_presupuesto_pct ?? 0) > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                    {{ $indicador->desviacion_presupuesto_pct !== null ? ($indicador->desviacion_presupuesto_pct > 0 ? '+' : '') . $indicador->desviacion_presupuesto_pct . '%' : '0.0%' }}
                </p>
                <p class="text-xs text-slate-500 mt-1">Corte: {{ $indicador->ultimo_reporte ?? 'Sin corte' }}</p>
            </div>
        </div>
    @endif

    {{-- Hitos del proyecto --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Hitos del Proyecto ({{ $proyecto->hitos->count() }})</h2>
            @if($proyecto->estado !== 'Cerrado')
                @can('create', [App\Models\Hito::class, $proyecto])
                    <a href="{{ route('proyectos.hitos.create', $proyecto) }}" class="px-3 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">Registrar hito</a>
                @endcan
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-slate-600 text-left">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Descripción</th>
                        <th class="px-4 py-2.5 font-medium text-center">Línea base</th>
                        <th class="px-4 py-2.5 font-medium text-center">Fecha prevista</th>
                        <th class="px-4 py-2.5 font-medium text-center">Fecha cierre</th>
                        <th class="px-4 py-2.5 font-medium">Evidencia</th>
                        <th class="px-4 py-2.5 font-medium">Estado</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proyecto->hitos as $hito)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 font-medium text-slate-900">{{ $hito->descripcion }}</td>
                            <td class="px-4 py-2.5 text-center">{{ $hito->en_linea_base ? 'Sí' : 'No' }}</td>
                            <td class="px-4 py-2.5 text-center font-mono">{{ $hito->fecha_prevista }}</td>
                            <td class="px-4 py-2.5 text-center font-mono">{{ $hito->fecha_cierre ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-600">{{ $hito->evidencia ?: '—' }}</td>
                            <td class="px-4 py-2.5">
                                @if($hito->fecha_cierre)
                                    <x-badge size="sm" variant="success">Concluido</x-badge>
                                @elseif($hito->fecha_prevista < now()->toDateString())
                                    <x-badge size="sm" variant="danger">Vencido</x-badge>
                                @else
                                    <x-badge size="sm" variant="neutral">Pendiente</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap space-x-2">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $hito)
                                        <a href="{{ route('hitos.edit', $hito) }}" class="text-brand-700 hover:underline">Editar</a>
                                    @endcan
                                    @can('delete', $hito)
                                        <form method="POST" action="{{ route('hitos.destroy', $hito) }}" class="inline" onsubmit="return confirm('¿Eliminar este hito?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay hitos registrados para este proyecto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Cortes presupuestales --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Historial de Cortes Presupuestales ({{ $proyecto->avances->count() }})</h2>
            @if($proyecto->estado !== 'Cerrado')
                @can('create', [App\Models\AvancePresupuestal::class, $proyecto])
                    <a href="{{ route('proyectos.avances.create', $proyecto) }}" class="px-3 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">Registrar corte</a>
                @endcan
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-slate-600 text-left">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Fecha</th>
                        <th class="px-4 py-2.5 font-medium text-right">Planificado (S/)</th>
                        <th class="px-4 py-2.5 font-medium text-right">Gasto acumulado (S/)</th>
                        <th class="px-4 py-2.5 font-medium text-right">Diferencia (S/)</th>
                        <th class="px-4 py-2.5 font-medium text-right">Desviación</th>
                        <th class="px-4 py-2.5"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($proyecto->avances as $av)
                        @php
                            $dif = $av->gasto_acumulado - $av->presupuesto_planificado;
                            $pct = $av->presupuesto_planificado > 0 ? ($dif * 100 / $av->presupuesto_planificado) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 font-mono text-slate-900">{{ $av->fecha_reporte }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format($av->presupuesto_planificado, 2) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums font-medium text-slate-900">{{ number_format($av->gasto_acumulado, 2) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ ($dif > 0 ? '+' : '') . number_format($dif, 2) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ ($pct > 0 ? '+' : '') . number_format($pct, 1) }}%</td>
                            <td class="px-4 py-2.5 text-right whitespace-nowrap space-x-2">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $av)
                                        <a href="{{ route('avances.edit', $av) }}" class="text-brand-700 hover:underline">Editar</a>
                                    @endcan
                                    @can('delete', $av)
                                        <form method="POST" action="{{ route('avances.destroy', $av) }}" class="inline" onsubmit="return confirm('¿Eliminar este corte presupuestal?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-700 hover:underline">Eliminar</button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No se registran cortes presupuestales para este proyecto.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal de reapertura --}}
    <div x-show="modalReabrir" x-cloak class="fixed inset-0 bg-slate-900/50 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div @click.outside="modalReabrir = false" class="bg-white rounded-xl max-w-md w-full p-5 border border-slate-200 space-y-4">
            <h3 class="text-base font-semibold text-slate-900">Reabrir proyecto PGD {{ $proyecto->codigo_pgd }}</h3>
            <p class="text-xs text-slate-600">El proyecto pasará al estado En curso. Indique el motivo del cambio (entre 5 y 200 caracteres).</p>
            <form method="POST" action="{{ route('proyectos.reabrir', $proyecto) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="motivo" class="block text-xs font-medium text-slate-700 mb-1">Motivo de reapertura</label>
                    <textarea name="motivo" id="motivo" rows="3" required minlength="5" maxlength="200"
                              class="w-full text-sm border-slate-300 rounded-lg"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="modalReabrir = false" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs text-slate-700">Cancelar</button>
                    <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
