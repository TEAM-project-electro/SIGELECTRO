@extends('layouts.app')

@section('title', 'Tablero Gerencial')

@section('content')
@php
    $resumen = $datos['resumen'];
    $consulta = array_merge(request()->query(), ['revision_esperada' => $datos['contexto']['revision_datos']]);
    $desv = $resumen['desviacion_presupuestal']['valor'];
    $avanceNum = is_numeric($resumen['avance_hitos']['valor'] ?? null) ? min(100, max(0, (float) $resumen['avance_hitos']['valor'])) : 0;
    $totProy = max(1, (int) $resumen['total_proyectos']);
    $pctCurso = round(($resumen['conteo_estados']['En curso'] / $totProy) * 100, 1);
    $pctPend = round(($resumen['conteo_estados']['Pendiente'] / $totProy) * 100, 1);
    $pctCerr = round(($resumen['conteo_estados']['Cerrado'] / $totProy) * 100, 1);
@endphp

{{-- Encabezado ejecutivo --}}
<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                DATOS DEMOSTRATIVOS
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                <span class="w-1.5 h-1.5 rounded-full {{ $origen === 'DW' ? 'bg-brand-600' : 'bg-energy-500' }}" aria-hidden="true"></span>
                <span>{{ $origen === 'DW' ? 'Data Mart Analítico' : 'Base Operativa en Vivo' }}</span>
            </span>
            <span class="text-xs font-mono text-slate-500">v{{ $datos['contexto']['revision_datos'] }}</span>
            @if($estadoDw['desactualizado'])
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-amber-50 border border-amber-200 text-[11px] font-medium text-amber-800">
                    Data Mart desactualizado (v{{ $estadoDw['revision_dw'] }})
                    @if(Auth::user()->esAdmin())
                        <button type="button" onclick="ejecutarRecargaEtl()" class="underline font-semibold text-brand-800 hover:text-brand-950">Actualizar</button>
                    @endif
                </span>
            @endif
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Tablero Gerencial de Seguimiento PGD</h1>
        <p class="text-sm text-slate-600 mt-1">{{ $datos['contexto']['rango_fechas_texto'] }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <a href="{{ route('reportes.csv', $consulta) }}" class="sig-btn-secondary">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Descargar CSV</span>
        </a>
        <a href="{{ route('reportes.imprimir', $consulta) }}" target="_blank" rel="noopener" class="sig-btn-primary">
            <svg class="w-4 h-4 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Ver reporte</span>
        </a>
    </div>
</div>

{{-- Barra integrada de filtros (RF05) --}}
<form method="GET" action="{{ route('tablero') }}"
      class="sig-panel p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
    <div>
        <label for="modo" class="block text-xs font-semibold text-slate-700 mb-1">Modalidad</label>
        <select id="modo" name="modo" class="sig-input text-xs py-2">
            <option value="CORTE_COMUN" @selected($filtros['modo'] === 'CORTE_COMUN')>Fecha común</option>
            <option value="ULTIMO_REPORTE_POR_PROYECTO" @selected($filtros['modo'] === 'ULTIMO_REPORTE_POR_PROYECTO')>Último reporte</option>
        </select>
    </div>
    <div>
        <label for="fecha_corte" class="block text-xs font-semibold text-slate-700 mb-1">Fecha de corte</label>
        <select id="fecha_corte" name="fecha_corte" class="sig-input text-xs py-2 font-mono">
            @foreach($fechasCorteDisponibles as $fc)
                <option value="{{ $fc->fecha_reporte }}" @selected($fechaCorteSeleccionada === $fc->fecha_reporte)>{{ $fc->fecha_reporte }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="estado" class="block text-xs font-semibold text-slate-700 mb-1">Estado</label>
        <select id="estado" name="estado" class="sig-input text-xs py-2">
            <option value="">Todos</option>
            @foreach(['Pendiente', 'En curso', 'Cerrado'] as $e)
                <option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>{{ $e }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="id_responsable" class="block text-xs font-semibold text-slate-700 mb-1">Responsable</label>
        <select id="id_responsable" name="id_responsable" class="sig-input text-xs py-2">
            <option value="">Todos</option>
            @foreach($responsables as $r)
                <option value="{{ $r->id_responsable }}" @selected(($filtros['id_responsable'] ?? '') == $r->id_responsable)>{{ $r->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="origen" class="block text-xs font-semibold text-slate-700 mb-1">Origen de datos</label>
        <select id="origen" name="origen" class="sig-input text-xs py-2">
            <option value="OPERATIVO" @selected($origen === 'OPERATIVO')>Operativo</option>
            <option value="DW" @selected($origen === 'DW')>Data Mart</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="flex-1 sig-btn-primary py-2">Aplicar</button>
        <a href="{{ route('tablero') }}" class="sig-btn-secondary py-2 px-3">Limpiar</a>
    </div>
</form>

{{-- Franja ejecutiva unificada de los cuatro indicadores (RF04) --}}
<section aria-label="Indicadores principales de cartera">
    <div class="sig-panel overflow-hidden">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/80">
            {{-- KPI 1: Avance de Hitos (LB) --}}
            <div class="p-5 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-600">Avance de Hitos (LB)</p>
                    <p class="text-[30px] leading-tight font-bold text-slate-900 mt-1.5 tabular-nums">
                        {{ $resumen['avance_hitos']['valor_formateado'] }}
                    </p>
                </div>
                <div class="mt-3.5">
                    <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden" aria-hidden="true">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $avanceNum }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 tabular-nums">
                        {{ $resumen['avance_hitos']['numerador'] }} de {{ $resumen['avance_hitos']['denominador'] }} hitos concluidos
                    </p>
                </div>
            </div>

            {{-- KPI 2: Proyectos con Retraso --}}
            <div class="p-5 flex flex-col justify-between {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'bg-rose-50/35' : '' }}">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-slate-600">Proyectos con Retraso</p>
                        <span class="w-2 h-2 rounded-full {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'bg-rose-600' : 'bg-energy-500' }}" aria-hidden="true"></span>
                    </div>
                    <p class="text-[30px] leading-tight font-bold mt-1.5 tabular-nums {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-rose-700' : 'text-energy-700' }}">
                        {{ $resumen['proyectos_retraso']['valor_formateado'] }}
                    </p>
                </div>
                <p class="text-xs text-slate-500 mt-3.5 tabular-nums">
                    {{ $resumen['proyectos_retraso']['numerador'] }} de {{ $resumen['proyectos_retraso']['denominador'] }} proyectos activos
                </p>
            </div>

            {{-- KPI 3: Desviación Presupuestal --}}
            <div class="p-5 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-600">Desviación Presupuestal</p>
                    <p class="text-[30px] leading-tight font-bold mt-1.5 tabular-nums {{ ($desv ?? 0) > 0 ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ $resumen['desviacion_presupuestal']['valor_formateado'] }}
                    </p>
                </div>
                <div class="mt-3.5 space-y-0.5 text-xs text-slate-600 tabular-nums">
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Gasto</span>
                        <span class="font-medium text-slate-900">S/&nbsp;{{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-slate-500">Planificado</span>
                        <span>S/&nbsp;{{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- KPI 4: Proyectos por Estado --}}
            <div class="p-5 flex flex-col justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-600">Proyectos por Estado</p>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <p class="text-[30px] leading-tight font-bold text-slate-900 tabular-nums">{{ $resumen['total_proyectos'] }}</p>
                        <span class="text-xs text-slate-500 font-medium">en cartera</span>
                    </div>
                </div>
                <div class="mt-3.5">
                    <div class="h-1.5 w-full rounded-full bg-slate-100 overflow-hidden flex" aria-hidden="true">
                        @if($pctCurso > 0)<div class="h-full bg-energy-500" style="width: {{ $pctCurso }}%"></div>@endif
                        @if($pctPend > 0)<div class="h-full bg-amber-500" style="width: {{ $pctPend }}%"></div>@endif
                        @if($pctCerr > 0)<div class="h-full bg-slate-400" style="width: {{ $pctCerr }}%"></div>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600 mt-2 tabular-nums">
                        <span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-energy-500" aria-hidden="true"></span>En curso {{ $resumen['conteo_estados']['En curso'] }}</span>
                        <span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>Pendiente {{ $resumen['conteo_estados']['Pendiente'] }}</span>
                        <span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>Cerrado {{ $resumen['conteo_estados']['Cerrado'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p class="text-[11px] text-slate-500 mt-2">
        Una desviación presupuestal negativa (gasto menor al plan) indica desfase en el devengado, no ahorro; una desviación positiva indica gasto superior al plan acumulado al mismo corte. “Sin datos” o “No aplica” señalan ausencia de registros evaluables o denominador cero.
    </p>
</section>

{{-- Evolución financiera y Alertas (RF08) --}}
@if(count($evolucionCortes) > 0 || $alertas->count() > 0)
    <div class="grid grid-cols-1 {{ (count($evolucionCortes) > 0 && $alertas->count() > 0) ? 'xl:grid-cols-12' : '' }} gap-6">
        @if(count($evolucionCortes) > 0)
            <div class="sig-panel p-5 {{ $alertas->count() > 0 ? 'xl:col-span-7' : '' }}">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Planificado y gasto acumulado por corte</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Importes en soles (PEN); cada barra suma únicamente los proyectos con reporte en esa fecha.</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-slate-600">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-slate-300" aria-hidden="true"></span>Planificado</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-brand-600" aria-hidden="true"></span>Gasto</span>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="graficoEvolucion" aria-label="Gráfico de planificado y gasto por fecha de corte"></canvas>
                </div>
            </div>
        @endif

        @if($alertas->count() > 0)
            <div class="sig-panel overflow-hidden flex flex-col {{ count($evolucionCortes) > 0 ? 'xl:col-span-5' : '' }}">
                <div class="px-5 py-3.5 border-b border-slate-200/80 flex items-center justify-between bg-amber-50/40">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                        <h2 class="text-sm font-bold text-slate-900">Alertas activas</h2>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-900 tabular-nums">{{ $alertas->count() }}</span>
                </div>
                <ul class="divide-y divide-slate-100 overflow-y-auto max-h-72 text-xs">
                    @foreach($alertas as $alerta)
                        <li class="px-5 py-3 hover:bg-slate-50/80 transition-colors duration-150">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span translate="no" class="font-mono font-bold text-brand-800">PGD {{ $alerta->codigo_pgd }}</span>
                                    <p class="text-slate-700 mt-0.5 leading-snug">{{ $alerta->detalle }}</p>
                                </div>
                                <span class="text-[11px] text-slate-500 whitespace-nowrap shrink-0">{{ $alerta->responsable }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

{{-- Detalle por proyecto --}}
<section class="sig-panel overflow-hidden" aria-labelledby="titulo-detalle-proyectos">
    <div class="px-5 py-4 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 id="titulo-detalle-proyectos" class="text-sm font-bold text-slate-900">Detalle de Proyectos en el Corte Evaluado</h2>
            <p class="text-xs text-slate-500 mt-0.5">{{ count($datos['proyectos']) }} proyectos incluidos en la vista actual</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-[13px]">
            <thead class="bg-slate-50/90 text-slate-600 text-left text-xs border-b border-slate-200/80">
                <tr>
                    <th class="py-3 px-5 font-semibold">Proyecto</th>
                    <th class="py-3 px-4 font-semibold">Responsable</th>
                    <th class="py-3 px-4 font-semibold">Estado</th>
                    <th class="py-3 px-4 font-semibold">Hitos LB</th>
                    <th class="py-3 px-4 font-semibold text-right">Planificado (S/)</th>
                    <th class="py-3 px-4 font-semibold text-right">Gasto (S/)</th>
                    <th class="py-3 px-5 font-semibold text-right">Desviación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($datos['proyectos'] as $p)
                    @php
                        $pctHitos = ($p['hitos']['evaluable'] && $p['hitos']['total_lb'] > 0)
                            ? round(($p['hitos']['concluidos_lb'] / $p['hitos']['total_lb']) * 100)
                            : null;
                    @endphp
                    <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                        <td class="py-3.5 px-5 min-w-[240px]">
                            <a href="{{ route('proyectos.show', $p['id_proyecto']) }}"
                               class="group inline-flex items-baseline gap-2 font-semibold text-slate-900 hover:text-brand-700">
                                <span translate="no" class="font-mono text-xs font-bold px-1.5 py-0.5 rounded bg-brand-50 text-brand-800 border border-brand-200/70 group-hover:bg-brand-100">{{ $p['codigo_pgd'] }}</span>
                                <span class="group-hover:underline">{{ $p['nombre'] }}</span>
                            </a>
                            <span class="block text-[11px] text-slate-500 mt-0.5">{{ $p['objetivo']['codigo'] ?? '' }}</span>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap text-slate-700">{{ $p['responsable']['nombre'] }}</td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$p['estado']] ?? 'neutral'">{{ $p['estado'] }}</x-badge>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap tabular-nums">
                            @if($p['hitos']['evaluable'])
                                <div class="flex items-center gap-2.5">
                                    <div class="w-16 h-1.5 rounded-full bg-slate-100 overflow-hidden shrink-0" aria-hidden="true">
                                        <div class="h-full rounded-full {{ $p['hitos']['con_retraso'] ? 'bg-rose-500' : 'bg-brand-600' }}" style="width: {{ $pctHitos }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-800">{{ $p['hitos']['concluidos_lb'] }}/{{ $p['hitos']['total_lb'] }}</span>
                                    @if($p['hitos']['con_retraso'])
                                        <span class="text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-1.5 py-0.5 rounded">{{ $p['hitos']['vencidos_lb'] }} vencido(s)</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Sin línea base</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right tabular-nums whitespace-nowrap text-slate-600">
                            {{ $p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null ? number_format($p['financiero']['presupuesto_planificado'], 2) : '—' }}
                        </td>
                        <td class="py-3.5 px-4 text-right tabular-nums whitespace-nowrap font-medium text-slate-900">
                            {{ $p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null ? number_format($p['financiero']['gasto_acumulado'], 2) : '—' }}
                        </td>
                        <td class="py-3.5 px-5 text-right tabular-nums whitespace-nowrap font-semibold {{ ($p['financiero']['desviacion_pct'] ?? 0) > 0 ? 'text-amber-700' : 'text-slate-700' }}">
                            {{ $p['financiero']['tiene_corte'] ? $p['financiero']['desviacion_formateada'] : 'Sin corte' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 px-5 text-center text-sm text-slate-500">
                            No se encontraron proyectos para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lienzo = document.getElementById('graficoEvolucion');
        if (!lienzo || typeof window.Chart === 'undefined') return;
        const cortes = @json($evolucionCortes);
        const soles = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const solesCompacto = new Intl.NumberFormat('es-PE', { notation: 'compact', maximumFractionDigits: 1 });
        new window.Chart(lienzo, {
            type: 'bar',
            data: {
                labels: cortes.map(c => c.fecha_reporte + ' (' + c.proyectos_reportados + ' proy.)'),
                datasets: [
                    {
                        label: 'Planificado',
                        data: cortes.map(c => +c.total_planificado),
                        backgroundColor: '#cbd5e1',
                        hoverBackgroundColor: '#94a3b8',
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Gasto',
                        data: cortes.map(c => +c.total_gasto),
                        backgroundColor: '#0057a8',
                        hoverBackgroundColor: '#003266',
                        borderRadius: 5,
                        borderSkipped: false,
                        maxBarThickness: 28,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#001836',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            afterTitle: items => {
                                const c = cortes[items[0]?.dataIndex];
                                return c ? 'Proyectos reportados en este corte: ' + c.proyectos_reportados : '';
                            },
                            label: c => ' ' + c.dataset.label + ': S/ ' + soles.format(c.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: '#e2e8f0' },
                        ticks: {
                            color: '#64748b',
                            font: { size: 11 },
                            callback: v => 'S/ ' + solesCompacto.format(v)
                        }
                    },
                    x: {
                        border: { display: false },
                        grid: { display: false },
                        ticks: { color: '#475569', font: { size: 11 } }
                    }
                }
            }
        });
    });

    function ejecutarRecargaEtl() {
        if (!confirm('¿Actualizar el Data Mart con los datos operativos actuales?')) return;
        fetch('{{ route('etl.ejecutar') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(d => { alert(d.exito ? 'Data Mart actualizado.' : 'No se pudo actualizar: ' + (d.mensaje || 'error desconocido')); if (d.exito) location.reload(); })
        .catch(() => alert('Error de conexión con el servidor.'));
    }
</script>
@endsection
