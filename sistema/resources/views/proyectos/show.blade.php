@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- ============================================================ -->
    <!-- 1. ENCABEZADO DE FICHA DE PROYECTO                           -->
    <!-- ============================================================ -->
    <div class="bg-white p-6 rounded-xl border border-slate-200/90 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-bold px-2.5 py-1 rounded bg-brand-900 text-white shadow-xs">
                        PGD {{ $proyecto->codigo_pgd }}
                    </span>
                    @if($proyecto->estado === 'En curso')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                            En curso
                        </span>
                    @elseif($proyecto->estado === 'Cerrado')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500 mr-1.5"></span>
                            Cerrado
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                            Pendiente
                        </span>
                    @endif
                    <span class="text-xs text-slate-400 font-mono">
                        Vigencia: {{ $proyecto->anio_inicio }} – {{ $proyecto->anio_fin }}
                    </span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ $proyecto->nombre }}
                </h1>
                <p class="text-xs text-slate-500">
                    Última actualización registrada: <span class="font-mono font-medium text-slate-700">{{ $proyecto->fecha_actualizacion }}</span> &bull; Fuente: {{ $proyecto->fuente ?? 'PGD Electrocentro' }}
                </p>
            </div>

            <!-- Botones de Acción de Ficha -->
            <div class="flex flex-wrap items-center gap-2 self-start lg:self-center">
                <a href="{{ route('proyectos.index') }}"
                    class="px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 shadow-xs transition">
                    &larr; Volver a Cartera
                </a>

                @can('update', $proyecto)
                    <a href="{{ route('proyectos.edit', $proyecto) }}"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                        Editar Metadatos
                    </a>
                @endcan

                @if($proyecto->estado !== 'Cerrado')
                    @can('cerrar', $proyecto)
                        <form method="POST" action="{{ route('proyectos.cerrar', $proyecto) }}" class="inline"
                            onsubmit="return confirm('¿Confirmar el cierre formal del proyecto? Conforme a la Regla R3, el 100% de los hitos de línea base deben estar concluidos.');">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                                Cerrar Proyecto (R3)
                            </button>
                        </form>
                    @endcan
                @else
                    @can('reabrir', $proyecto)
                        <button type="button" onclick="document.getElementById('modalReabrir').classList.remove('hidden')"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                            Reabrir Proyecto
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        <!-- Ficha de Metadatos y Asignación Institucional -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-5 border-t border-slate-100 text-xs">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Presupuesto Total:</span>
                <p class="text-base font-extrabold font-mono text-slate-900 mt-0.5 tabular-nums">
                    S/ {{ number_format($proyecto->monto, 2) }}
                </p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Objetivo Estratégico:</span>
                <p class="text-xs font-semibold text-slate-800 mt-1 line-clamp-2" title="{{ $proyecto->objetivo->descripcion ?? '' }}">
                    <strong class="text-brand-800 font-mono">{{ $proyecto->objetivo->codigo ?? 'N/A' }}</strong> - {{ Str::limit($proyecto->objetivo->descripcion ?? '', 40) }}
                </p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Responsable Titular:</span>
                <p class="text-xs font-semibold text-slate-800 mt-1">
                    {{ $proyecto->responsable->nombre ?? 'Sin asignar' }}
                </p>
                <span class="text-[10px] text-slate-500 block">{{ $proyecto->responsable->area ?? '' }} ({{ $proyecto->responsable->perfil ?? '' }})</span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block text-[10px]">Estado de Auditoría:</span>
                <p class="text-xs font-mono font-semibold text-brand-900 mt-1">
                    R4 Inmutable Activa
                </p>
                <span class="text-[10px] text-slate-500 block">Triggers definer operativos</span>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. INDICADORES ANALÍTICOS (v_indicadores_proyecto)           -->
    <!-- ============================================================ -->
    @if($indicador)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Avance de Hitos LB -->
            <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
                <span class="text-[11px] uppercase font-bold tracking-wider text-slate-500 block">Avance de Hitos (LB)</span>
                <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">
                    {{ $indicador->avance_hitos_pct !== null ? $indicador->avance_hitos_pct . '%' : 'Sin hitos' }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $indicador->hitos_lb_concluidos }} de {{ $indicador->hitos_lb_total }} hitos concluidos
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                    <div class="bg-brand-600 h-1.5 rounded-full" style="width: {{ min(100, (float)($indicador->avance_hitos_pct ?? 0)) }}%"></div>
                </div>
            </div>

            <!-- Último Reporte Financiero -->
            <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
                <span class="text-[11px] uppercase font-bold tracking-wider text-slate-500 block">Gasto Devengado</span>
                <div class="text-2xl font-extrabold font-mono text-slate-900 mt-1 tabular-nums">
                    S/ {{ number_format($indicador->gasto_acumulado ?? 0, 2) }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Plan: <span class="font-mono text-slate-700">S/ {{ number_format($indicador->presupuesto_planificado ?? 0, 2) }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 font-mono">
                    Corte: {{ $indicador->ultimo_reporte ?? 'Sin corte' }}
                </div>
            </div>

            <!-- Desviación Presupuestal -->
            <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
                <span class="text-[11px] uppercase font-bold tracking-wider text-slate-500 block">Desviación Presupuestal</span>
                <div class="text-2xl font-extrabold mt-1 tabular-nums
                    @if(($indicador->desviacion_presupuesto_pct ?? 0) > 0) text-rose-700
                    @elseif(($indicador->desviacion_presupuesto_pct ?? 0) < 0) text-brand-900
                    @else text-slate-900 @endif">
                    {{ $indicador->desviacion_presupuesto_pct !== null ? ($indicador->desviacion_presupuesto_pct > 0 ? '+' : '') . $indicador->desviacion_presupuesto_pct . '%' : '0.0%' }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ ($indicador->desviacion_presupuesto_pct ?? 0) < 0 ? 'Desfase en el devengado' : (($indicador->desviacion_presupuesto_pct ?? 0) > 0 ? 'Sobregasto respecto al plan' : 'Alineado al plan') }}
                </div>
            </div>

            <!-- Condición de Retraso -->
            <div class="bg-white p-4.5 rounded-xl border border-slate-200/90 shadow-xs">
                <span class="text-[11px] uppercase font-bold tracking-wider text-slate-500 block">Condición de Cronograma</span>
                <div class="text-2xl font-extrabold mt-1 flex items-center gap-2 {{ $indicador->tiene_retraso ? 'text-rose-600' : 'text-emerald-700' }}">
                    <span class="w-2.5 h-2.5 rounded-full {{ $indicador->tiene_retraso ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500' }}"></span>
                    <span>{{ $indicador->tiene_retraso ? 'RETRASADO' : 'AL DÍA' }}</span>
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    Hitos vencidos en LB: <strong class="text-slate-800">{{ $indicador->hitos_lb_vencidos }}</strong>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================ -->
    <!-- 3. BLOQUE DE HITOS Y ENTREGABLES                             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Hitos del Proyecto</h2>
                <p class="text-xs text-slate-500">Línea base y seguimiento de entregables conforme a la Regla R3</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700">
                    Total: {{ $proyecto->hitos->count() }} hitos
                </span>
                @if($proyecto->estado !== 'Cerrado')
                    @can('create', [App\Models\Hito::class, $proyecto])
                        <a href="{{ route('proyectos.hitos.create', $proyecto) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                            <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Nuevo Hito</span>
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-100/80 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
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
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @forelse ($proyecto->hitos as $hito)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 font-semibold text-slate-900 max-w-xs">
                                {{ $hito->descripcion }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($hito->en_linea_base)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-brand-50 text-brand-800 border border-brand-200">
                                        Línea Base
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        Fuera LB
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-slate-600 whitespace-nowrap">
                                {{ $hito->fecha_prevista }}
                            </td>
                            <td class="px-4 py-3 text-center font-mono whitespace-nowrap">
                                @if($hito->fecha_cierre)
                                    <span class="text-emerald-700 font-bold">{{ $hito->fecha_cierre }}</span>
                                @else
                                    <span class="text-slate-400 italic">Abierto</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate" title="{{ $hito->evidencia }}">
                                {{ $hito->evidencia ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($hito->fecha_cierre)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Concluido
                                    </span>
                                @elseif($hito->fecha_prevista < now()->toDateString())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                        Vencido
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        Pendiente
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap space-x-2">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $hito)
                                        <a href="{{ route('hitos.edit', $hito) }}"
                                            class="inline-flex items-center px-2 py-1 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:bg-brand-50 rounded transition">
                                            Editar / Cerrar
                                        </a>
                                    @endcan
                                    @can('delete', $hito)
                                        <form method="POST" action="{{ route('hitos.destroy', $hito) }}" class="inline"
                                            onsubmit="return confirm('¿Está seguro de eliminar este hito?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition">
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
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No hay hitos registrados para este proyecto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 4. BLOQUE DE AVANCES PRESUPUESTALES (CORTES CRONOLÓGICOS)     -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50/60">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Historial de Cortes Presupuestales</h2>
                <p class="text-xs text-slate-500">Ejecución cronológica devengada validada contra Reglas R1 y R2</p>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-slate-700">
                    Total Cortes: {{ $proyecto->avances->count() }}
                </span>
                @if($proyecto->estado !== 'Cerrado')
                    @can('create', [App\Models\AvancePresupuestal::class, $proyecto])
                        <a href="{{ route('proyectos.avances.create', $proyecto) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                            <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Nuevo Corte Presupuestal</span>
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-100/80 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-center">Fecha Reporte</th>
                        <th class="px-4 py-3 text-right">Presupuesto Planificado (S/)</th>
                        <th class="px-4 py-3 text-right">Gasto Acumulado (S/)</th>
                        <th class="px-4 py-3 text-right">Desviación (S/)</th>
                        <th class="px-4 py-3 text-right">Desviación (%)</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white font-mono text-slate-700">
                    @forelse ($proyecto->avances as $av)
                        @php
                            $desvMonto = $av->gasto_acumulado - $av->presupuesto_planificado;
                            $desvPct = $av->presupuesto_planificado > 0 ? ($desvMonto * 100 / $av->presupuesto_planificado) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 text-center font-bold text-slate-900 whitespace-nowrap">
                                {{ $av->fecha_reporte }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap tabular-nums">
                                {{ number_format($av->presupuesto_planificado, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-900 font-extrabold whitespace-nowrap tabular-nums">
                                {{ number_format($av->gasto_acumulado, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums font-semibold {{ $desvMonto > 0 ? 'text-rose-700' : ($desvMonto < 0 ? 'text-brand-900' : 'text-slate-700') }}">
                                {{ ($desvMonto > 0 ? '+' : '') . number_format($desvMonto, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap tabular-nums font-bold {{ $desvPct > 0 ? 'text-rose-700' : ($desvPct < 0 ? 'text-brand-900' : 'text-slate-700') }}">
                                {{ ($desvPct > 0 ? '+' : '') . number_format($desvPct, 1) }}%
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap space-x-2 font-sans">
                                @if($proyecto->estado !== 'Cerrado')
                                    @can('update', $av)
                                        <a href="{{ route('avances.edit', $av) }}"
                                            class="inline-flex items-center px-2 py-1 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:bg-brand-50 rounded transition">
                                            Editar
                                        </a>
                                    @endcan
                                    @can('delete', $av)
                                        <form method="POST" action="{{ route('avances.destroy', $av) }}" class="inline"
                                            onsubmit="return confirm('¿Está seguro de eliminar este corte presupuestal?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-semibold text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition">
                                                Eliminar
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Bloqueado</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400 font-sans">
                                No se registran cortes presupuestales para este proyecto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- 5. MODAL DE REAPERTURA DE PROYECTO (Regla R3 & Motivo)        -->
<!-- ============================================================ -->
<div id="modalReabrir" class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200/90 space-y-4">
        <div class="flex justify-between items-center border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <h3 class="text-base font-extrabold text-slate-900">Reabrir Proyecto PGD {{ $proyecto->codigo_pgd }}</h3>
            </div>
            <button type="button" onclick="document.getElementById('modalReabrir').classList.add('hidden')"
                class="text-slate-400 hover:text-slate-700 p-1 text-lg leading-none">&times;</button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed">
            La reapertura cambia el estado de <strong>Cerrado</strong> a <strong>En curso</strong>. Conforme al contrato técnico y la <strong>Regla R3</strong>, es estrictamente obligatorio justificar el motivo para el registro auditable en la bitácora técnica de motivos.
        </p>

        <form method="POST" action="{{ route('proyectos.reabrir', $proyecto) }}" class="space-y-4">
            @csrf
            <div>
                <label for="motivo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Motivo Justificativo de la Reapertura (5 a 200 caracteres) *
                </label>
                <textarea name="motivo" id="motivo" rows="3" required minlength="5" maxlength="200"
                    placeholder="Especifique la resolución, acta de comité o requerimiento técnico que sustenta la reapertura..."
                    class="w-full text-xs border-slate-300 rounded-lg p-3 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 leading-relaxed"></textarea>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalReabrir').classList.add('hidden')"
                    class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                    Confirmar Reapertura
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
