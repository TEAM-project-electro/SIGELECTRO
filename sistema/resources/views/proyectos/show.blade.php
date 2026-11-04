@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Encabezado de Proyecto -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center space-x-3">
                    <span class="font-mono text-sm font-bold px-3 py-1 rounded bg-blue-900 text-white shadow-sm">
                        PGD {{ $proyecto->codigo_pgd }}
                    </span>
                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-semibold
                        @if($proyecto->estado === 'En curso') bg-emerald-100 text-emerald-800
                        @elseif($proyecto->estado === 'Cerrado') bg-blue-100 text-blue-800
                        @else bg-amber-100 text-amber-800 @endif">
                        {{ $proyecto->estado }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">
                    {{ $proyecto->nombre }}
                </h1>
                <p class="text-xs text-slate-500">
                    Última actualización registrada: {{ $proyecto->fecha_actualizacion }} &bull; Fuente: {{ $proyecto->fuente }}
                </p>
            </div>

            <div class="mt-4 sm:mt-0 flex flex-wrap items-center gap-2">
                <a href="{{ route('proyectos.index') }}"
                    class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 bg-white hover:bg-slate-50 transition">
                    &larr; Volver a la Cartera
                </a>

                @can('update', $proyecto)
                    <a href="{{ route('proyectos.edit', $proyecto) }}"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                        Editar Proyecto
                    </a>
                @endcan

                @if($proyecto->estado !== 'Cerrado')
                    @can('cerrar', $proyecto)
                        <form method="POST" action="{{ route('proyectos.cerrar', $proyecto) }}" class="inline"
                            onsubmit="return confirm('¿Confirmar el cierre del proyecto? La Regla R3 exige que el 100% de los hitos de línea base estén concluidos.');">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-lg text-xs font-medium transition shadow-sm">
                                Cerrar Proyecto
                            </button>
                        </form>
                    @endcan
                @else
                    @can('reabrir', $proyecto)
                        <button type="button" onclick="document.getElementById('modalReabrir').classList.remove('hidden')"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                            Reabrir Proyecto
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        <!-- Metadatos Básicos -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-slate-100 text-xs">
            <div>
                <span class="text-slate-400 font-semibold uppercase">Presupuesto PGD:</span>
                <p class="text-sm font-bold font-mono text-slate-800 mt-0.5">S/ {{ number_format($proyecto->monto, 2) }}</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold uppercase">Vigencia:</span>
                <p class="text-sm font-medium text-slate-800 mt-0.5">{{ $proyecto->anio_inicio }} – {{ $proyecto->anio_fin }}</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold uppercase">Objetivo Estratégico:</span>
                <p class="text-sm font-medium text-slate-800 mt-0.5">{{ $proyecto->objetivo->codigo ?? 'N/A' }} - {{ Str::limit($proyecto->objetivo->descripcion ?? '', 25) }}</p>
            </div>
            <div>
                <span class="text-slate-400 font-semibold uppercase">Responsable Titular:</span>
                <p class="text-sm font-medium text-slate-800 mt-0.5">{{ $proyecto->responsable->nombre ?? 'N/A' }} ({{ $proyecto->responsable->area ?? '' }})</p>
            </div>
        </div>
    </div>

    <!-- Indicadores Analíticos (Vista v_indicadores_proyecto) -->
    @if($indicador)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-semibold text-slate-500">Avance de Hitos (LB)</div>
                <div class="text-2xl font-bold text-slate-800 mt-1">
                    {{ $indicador->avance_hitos_pct !== null ? $indicador->avance_hitos_pct . '%' : 'Sin hitos' }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $indicador->hitos_lb_concluidos }} de {{ $indicador->hitos_lb_total }} hitos concluidos
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(100, (float)($indicador->avance_hitos_pct ?? 0)) }}%"></div>
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-semibold text-slate-500">Último Reporte Financiero</div>
                <div class="text-2xl font-bold font-mono text-slate-800 mt-1">
                    S/ {{ number_format($indicador->gasto_acumulado ?? 0, 2) }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Planificado: S/ {{ number_format($indicador->presupuesto_planificado ?? 0, 2) }}
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                    Corte: {{ $indicador->ultimo_reporte ?? 'Sin corte' }}
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-semibold text-slate-500">Desviación Presupuestal</div>
                <div class="text-2xl font-bold mt-1
                    @if(($indicador->desviacion_presupuesto_pct ?? 0) > 0) text-rose-700
                    @elseif(($indicador->desviacion_presupuesto_pct ?? 0) < 0) text-emerald-700
                    @else text-slate-800 @endif">
                    {{ $indicador->desviacion_presupuesto_pct !== null ? ($indicador->desviacion_presupuesto_pct > 0 ? '+' : '') . $indicador->desviacion_presupuesto_pct . '%' : '0.0%' }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Respecto a lo planificado
                </div>
            </div>

            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-semibold text-slate-500">Condición de Retraso</div>
                <div class="text-2xl font-bold mt-1 {{ $indicador->tiene_retraso ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $indicador->tiene_retraso ? 'RETRASADO' : 'AL DÍA' }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Hitos vencidos en LB: <span class="font-bold">{{ $indicador->hitos_lb_vencidos }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Bloque de Hitos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <div>
                <h2 class="text-base font-bold text-slate-900">Hitos del Proyecto</h2>
                <p class="text-xs text-slate-500">Línea base y seguimiento de entregables</p>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 rounded-md text-slate-700">
                    Total: {{ $proyecto->hitos->count() }}
                </span>
                @if($proyecto->estado !== 'Cerrado')
                    @can('create', [App\Models\Hito::class, $proyecto])
                        <a href="{{ route('proyectos.hitos.create', $proyecto) }}"
                            class="px-3 py-1.5 bg-blue-900 hover:bg-blue-800 text-white rounded-md text-xs font-medium transition shadow-sm">
                            + Nuevo Hito
                        </a>
                    @endcan
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Descripción del Hito</th>
                        <th class="px-4 py-3 text-center">Línea Base</th>
                        <th class="px-4 py-3 text-center">Fecha Prevista</th>
                        <th class="px-4 py-3 text-center">Fecha Cierre</th>
                        <th class="px-4 py-3 text-left">Evidencia / Entregable</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($proyecto->hitos as $hito)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-medium text-slate-900">
                                {{ $hito->descripcion }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($hito->en_linea_base)
                                    <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">LB</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-500">Fuera LB</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-slate-600">
                                {{ $hito->fecha_prevista }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono">
                                @if($hito->fecha_cierre)
                                    <span class="text-emerald-700 font-semibold">{{ $hito->fecha_cierre }}</span>
                                @else
                                    <span class="text-slate-400">Abierto</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $hito->evidencia ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($hito->fecha_cierre)
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Concluido</span>
                                @elseif($hito->fecha_prevista < now()->toDateString())
                                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold">Vencido</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-medium">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap space-x-2">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $hito)
                                        <a href="{{ route('hitos.edit', $hito) }}"
                                            class="text-xs font-semibold text-blue-900 hover:underline">
                                            Editar / Cerrar
                                        </a>
                                    @endcan
                                    @can('delete', $hito)
                                        <form method="POST" action="{{ route('hitos.destroy', $hito) }}" class="inline"
                                            onsubmit="return confirm('¿Está seguro de eliminar este hito?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-600 hover:underline">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Bloqueado por cierre</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-400">
                                No hay hitos registrados para este proyecto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bloque de Avances Presupuestales -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
            <div>
                <h2 class="text-base font-bold text-slate-900">Historial de Cortes Presupuestales</h2>
                <p class="text-xs text-slate-500">Ejecución cronológica verificada con reglas R1 y R2</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 rounded-md text-slate-700">
                Total Cortes: {{ $proyecto->avances->count() }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-center">Fecha Reporte</th>
                        <th class="px-4 py-3 text-right">Presupuesto Planificado (S/)</th>
                        <th class="px-4 py-3 text-right">Gasto Acumulado (S/)</th>
                        <th class="px-4 py-3 text-right">Desviación (S/)</th>
                        <th class="px-4 py-3 text-right">Desviación (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white font-mono">
                    @forelse ($proyecto->avances as $av)
                        @php
                            $desvMonto = $av->gasto_acumulado - $av->presupuesto_planificado;
                            $desvPct = $av->presupuesto_planificado > 0 ? ($desvMonto * 100 / $av->presupuesto_planificado) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-center font-bold text-slate-800">
                                {{ $av->fecha_reporte }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700">
                                {{ number_format($av->presupuesto_planificado, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-900 font-bold">
                                {{ number_format($av->gasto_acumulado, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right {{ $desvMonto > 0 ? 'text-rose-700' : ($desvMonto < 0 ? 'text-emerald-700' : 'text-slate-700') }}">
                                {{ ($desvMonto > 0 ? '+' : '') . number_format($desvMonto, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $desvPct > 0 ? 'text-rose-700' : ($desvPct < 0 ? 'text-emerald-700' : 'text-slate-700') }}">
                                {{ ($desvPct > 0 ? '+' : '') . number_format($desvPct, 1) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-400 font-sans">
                                No se registran cortes presupuestales para este proyecto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Reapertura de Proyecto (Requiere Motivo Obligatorio) -->
<div id="modalReabrir" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Reabrir Proyecto PGD {{ $proyecto->codigo_pgd }}</h3>
            <button type="button" onclick="document.getElementById('modalReabrir').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
            La reapertura cambia el estado de <strong>Cerrado</strong> a <strong>En curso</strong>. Conforme al contrato técnico y la <strong>Regla R3</strong>, es estrictamente obligatorio justificar el motivo para el registro auditable en la tabla <code class="bg-slate-100 px-1 py-0.5 rounded text-blue-900">bitacora_motivo</code>.
        </p>

        <form method="POST" action="{{ route('proyectos.reabrir', $proyecto) }}" class="space-y-4">
            @csrf
            <div>
                <label for="motivo" class="block text-xs font-semibold uppercase text-slate-700 mb-1">
                    Motivo Justificativo de la Reapertura *
                </label>
                <textarea name="motivo" id="motivo" rows="3" required minlength="5" maxlength="255"
                    placeholder="Especifique la resolución, acta de comité o requerimiento técnico que sustenta la reapertura..."
                    class="w-full text-xs border-slate-300 rounded-lg p-2.5 bg-slate-50 focus:bg-white focus:ring-blue-600 focus:border-blue-600"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalReabrir').classList.add('hidden')"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-medium transition shadow-sm">
                    Confirmar Reapertura
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
