@extends('layouts.app')

@section('content')
@php
    $resumen = $datos['resumen'];
    $consulta = array_merge(request()->query(), ['revision_esperada' => $datos['contexto']['revision_datos']]);
    $desv = $resumen['desviacion_presupuestal']['valor'];
@endphp

{{-- Encabezado y contexto del corte --}}
<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Tablero Gerencial de Seguimiento PGD</h1>
        <p class="text-sm text-slate-600 mt-1">{{ $datos['contexto']['rango_fechas_texto'] }}</p>
        <p class="text-xs text-slate-500 mt-1">
            <x-badge variant="warning" size="sm">DATOS DEMOSTRATIVOS</x-badge>
            Origen: {{ $origen === 'DW' ? 'Data Mart Analítico' : 'Base Operativa en Vivo' }} · Revisión v{{ $datos['contexto']['revision_datos'] }}
            @if($estadoDw['desactualizado'])
                · <span class="text-amber-700 font-medium">Data Mart desactualizado (v{{ $estadoDw['revision_dw'] }})</span>
                @if(Auth::user()->esAdmin())
                    <button type="button" onclick="ejecutarRecargaEtl()" class="ml-1 underline text-brand-800 hover:text-brand-950">Actualizar</button>
                @endif
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('reportes.imprimir', $consulta) }}" target="_blank"
           class="px-3 py-2 text-xs font-semibold rounded-lg bg-brand-800 text-white hover:bg-brand-900">Ver reporte</a>
        <a href="{{ route('reportes.csv', $consulta) }}"
           class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Descargar CSV</a>
    </div>
</div>

{{-- Filtros (RF05): estado, responsable y corte --}}
<form method="GET" action="{{ route('tablero') }}" class="bg-white rounded-xl border border-slate-200 p-4 grid grid-cols-2 lg:grid-cols-6 gap-3 items-end text-sm">
    <div class="col-span-2 lg:col-span-1">
        <label for="modo" class="block text-xs font-medium text-slate-600 mb-1">Modalidad</label>
        <select id="modo" name="modo" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="CORTE_COMUN" @selected($filtros['modo'] === 'CORTE_COMUN')>Fecha común</option>
            <option value="ULTIMO_REPORTE_POR_PROYECTO" @selected($filtros['modo'] === 'ULTIMO_REPORTE_POR_PROYECTO')>Último reporte</option>
        </select>
    </div>
    <div>
        <label for="fecha_corte" class="block text-xs font-medium text-slate-600 mb-1">Fecha de corte</label>
        <select id="fecha_corte" name="fecha_corte" class="w-full rounded-lg border-slate-300 text-sm">
            @foreach($fechasCorteDisponibles as $fc)
                <option value="{{ $fc->fecha_reporte }}" @selected($fechaCorteSeleccionada === $fc->fecha_reporte)>{{ $fc->fecha_reporte }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="estado" class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
        <select id="estado" name="estado" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">Todos</option>
            @foreach(['Pendiente', 'En curso', 'Cerrado'] as $e)
                <option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>{{ $e }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-span-2 lg:col-span-1">
        <label for="id_responsable" class="block text-xs font-medium text-slate-600 mb-1">Responsable</label>
        <select id="id_responsable" name="id_responsable" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="">Todos</option>
            @foreach($responsables as $r)
                <option value="{{ $r->id_responsable }}" @selected(($filtros['id_responsable'] ?? '') == $r->id_responsable)>{{ $r->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="origen" class="block text-xs font-medium text-slate-600 mb-1">Origen</label>
        <select id="origen" name="origen" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="OPERATIVO" @selected($origen === 'OPERATIVO')>Operativo</option>
            <option value="DW" @selected($origen === 'DW')>Data Mart</option>
        </select>
    </div>
    <div class="col-span-2 lg:col-span-1 flex gap-2">
        <button type="submit" class="flex-1 px-3 py-2 rounded-lg bg-brand-800 text-white text-xs font-semibold hover:bg-brand-900">Aplicar</button>
        <a href="{{ route('tablero') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-700 hover:bg-slate-50">Limpiar</a>
    </div>
</form>

{{-- Cuatro indicadores (RF04) --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500">Avance de Hitos (LB)</p>
        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $resumen['avance_hitos']['valor_formateado'] }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $resumen['avance_hitos']['numerador'] }} de {{ $resumen['avance_hitos']['denominador'] }} hitos concluidos</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500">Proyectos con Retraso</p>
        <p class="text-2xl font-bold mt-1 tabular-nums {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $resumen['proyectos_retraso']['valor_formateado'] }}</p>
        <p class="text-xs text-slate-500 mt-1">{{ $resumen['proyectos_retraso']['numerador'] }} de {{ $resumen['proyectos_retraso']['denominador'] }} proyectos activos</p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500">Desviación Presupuestal</p>
        <p class="text-2xl font-bold mt-1 tabular-nums {{ ($desv ?? 0) > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $resumen['desviacion_presupuestal']['valor_formateado'] }}</p>
        <p class="text-xs text-slate-500 mt-1">
            Gasto S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }} ·
            Plan S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}
        </p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <p class="text-xs font-medium text-slate-500">Proyectos por Estado</p>
        <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $resumen['total_proyectos'] }}</p>
        <p class="text-xs text-slate-500 mt-1">
            En curso {{ $resumen['conteo_estados']['En curso'] }} · Pendiente {{ $resumen['conteo_estados']['Pendiente'] }} · Cerrado {{ $resumen['conteo_estados']['Cerrado'] }}
        </p>
    </div>
</div>
<p class="text-[11px] text-slate-500 -mt-3">Desviación positiva: gasto superior a lo planificado al mismo corte. “Sin datos” o “No aplica” indican que no hay registros suficientes o el denominador es cero.</p>

{{-- Alertas (RF08) --}}
@if($alertas->count() > 0)
    <div class="bg-white rounded-xl border border-amber-200">
        <h2 class="px-4 py-3 text-sm font-semibold text-amber-900 border-b border-amber-100">Alertas ({{ $alertas->count() }})</h2>
        <ul class="divide-y divide-slate-100 max-h-60 overflow-y-auto text-xs">
            @foreach($alertas as $alerta)
                <li class="px-4 py-2.5 flex flex-wrap justify-between gap-2">
                    <span><strong class="font-mono text-brand-900">PGD {{ $alerta->codigo_pgd }}</strong> · {{ $alerta->detalle }}</span>
                    <span class="text-slate-500">{{ $alerta->responsable }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Detalle por proyecto --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <h2 class="px-4 py-3 text-sm font-semibold text-slate-900 border-b border-slate-200">Detalle de Proyectos en el Corte Evaluado</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-slate-600 text-left">
                <tr>
                    <th class="py-2.5 px-4 font-medium">Proyecto</th>
                    <th class="py-2.5 px-4 font-medium">Responsable</th>
                    <th class="py-2.5 px-4 font-medium">Estado</th>
                    <th class="py-2.5 px-4 font-medium text-center">Hitos LB</th>
                    <th class="py-2.5 px-4 font-medium text-right">Planificado (S/)</th>
                    <th class="py-2.5 px-4 font-medium text-right">Gasto (S/)</th>
                    <th class="py-2.5 px-4 font-medium text-right">Desviación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($datos['proyectos'] as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-4">
                            <a href="{{ route('proyectos.show', $p['id_proyecto']) }}" class="font-medium text-slate-900 hover:text-brand-700 hover:underline">
                                <span class="font-mono text-brand-800">{{ $p['codigo_pgd'] }}</span> · {{ $p['nombre'] }}
                            </a>
                            <span class="block text-[11px] text-slate-500">{{ $p['objetivo']['codigo'] ?? '' }}</span>
                        </td>
                        <td class="py-2.5 px-4 whitespace-nowrap">{{ $p['responsable']['nombre'] }}</td>
                        <td class="py-2.5 px-4 whitespace-nowrap">
                            <x-badge size="sm" :variant="['En curso' => 'success', 'Pendiente' => 'warning'][$p['estado']] ?? 'neutral'">{{ $p['estado'] }}</x-badge>
                        </td>
                        <td class="py-2.5 px-4 text-center whitespace-nowrap tabular-nums">
                            @if($p['hitos']['evaluable'])
                                {{ $p['hitos']['concluidos_lb'] }}/{{ $p['hitos']['total_lb'] }}
                                @if($p['hitos']['con_retraso'])
                                    <span class="text-rose-700 font-semibold">· {{ $p['hitos']['vencidos_lb'] }} vencido(s)</span>
                                @endif
                            @else
                                <span class="text-slate-400">Sin línea base</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums whitespace-nowrap">
                            {{ $p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null ? number_format($p['financiero']['presupuesto_planificado'], 2) : '—' }}
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums whitespace-nowrap">
                            {{ $p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null ? number_format($p['financiero']['gasto_acumulado'], 2) : '—' }}
                        </td>
                        <td class="py-2.5 px-4 text-right tabular-nums whitespace-nowrap">
                            {{ $p['financiero']['tiene_corte'] ? $p['financiero']['desviacion_formateada'] : 'Sin corte' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">No se encontraron proyectos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Evolución financiera --}}
@if(count($evolucionCortes) > 0)
    <div class="bg-white rounded-xl border border-slate-200 p-4">
        <h2 class="text-sm font-semibold text-slate-900">Planificado y gasto acumulado por fecha de corte (S/)</h2>
        <div class="h-64 mt-3"><canvas id="graficoEvolucion" aria-label="Gráfico de planificado y gasto por fecha de corte"></canvas></div>
    </div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lienzo = document.getElementById('graficoEvolucion');
        if (!lienzo || typeof window.Chart === 'undefined') return;
        const cortes = @json($evolucionCortes);
        const soles = new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2 });
        new window.Chart(lienzo, {
            type: 'bar',
            data: {
                labels: cortes.map(c => c.fecha_reporte),
                datasets: [
                    { label: 'Planificado', data: cortes.map(c => +c.total_planificado), backgroundColor: '#94a3b8', maxBarThickness: 32 },
                    { label: 'Gasto', data: cortes.map(c => +c.total_gasto), backgroundColor: '#1e3a8a', maxBarThickness: 32 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: c => c.dataset.label + ': S/ ' + soles.format(c.raw) } }
                },
                scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + soles.format(v) } }, x: { grid: { display: false } } }
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
