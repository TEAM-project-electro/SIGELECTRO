@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- 1. Encabezado de Contexto y Control de Datos -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                        DATOS DEMOSTRATIVOS
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Origen: {{ $origen === 'DW' ? 'Data Mart Analítico (dw_seguimiento_gd)' : 'Base Operativa en Vivo (seguimiento_gd)' }}
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs font-mono bg-blue-50 text-blue-800 border border-blue-200" title="Revisión de control de cambios">
                        Rev. v{{ $datos['contexto']['revision_datos'] }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mt-2">Tablero Gerencial de Seguimiento PGD</h1>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $datos['contexto']['rango_fechas_texto'] }}. Evaluando cartera de proyectos de gobierno digital de Electrocentro S. A.
                </p>
            </div>

            <!-- Selector de Origen y Estado DW -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg border border-slate-300 p-1 bg-slate-50 text-xs">
                    <a href="{{ request()->fullUrlWithQuery(['origen' => 'OPERATIVO']) }}"
                       class="px-3 py-1.5 rounded-md font-medium transition {{ $origen === 'OPERATIVO' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-700 hover:text-slate-900' }}">
                        Base Operativa
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['origen' => 'DW']) }}"
                       class="px-3 py-1.5 rounded-md font-medium transition {{ $origen === 'DW' ? 'bg-blue-900 text-white shadow-sm' : 'text-slate-700 hover:text-slate-900' }}">
                        Data Warehouse
                    </a>
                </div>

                @if($estadoDw['desactualizado'])
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        DW Desactualizado (v{{ $estadoDw['revision_dw'] }} vs v{{ $estadoDw['version_operativa'] }})
                    </span>
                    @if(Auth::user()->perfil === 'Administrador')
                        <button type="button"
                                onclick="ejecutarRecargaEtl()"
                                class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded-lg shadow-sm transition">
                            Sincronizar DW
                        </button>
                    @endif
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        DW Sincronizado
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. Filtros y Modalidad Temporal de Consulta -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
        <form method="GET" action="{{ route('tablero') }}" class="space-y-4">
            <input type="hidden" name="origen" value="{{ $origen }}">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Modalidad Temporal -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Modalidad Temporal
                    </label>
                    <select name="modo"
                            onchange="this.form.submit()"
                            class="w-full text-sm border-slate-300 rounded-lg shadow-sm focus:border-blue-800 focus:ring-blue-800">
                        <option value="CORTE_COMUN" {{ $filtros['modo'] === 'CORTE_COMUN' ? 'selected' : '' }}>
                            Corte Común (Fecha homogénea)
                        </option>
                        <option value="ULTIMO_REPORTE_POR_PROYECTO" {{ $filtros['modo'] === 'ULTIMO_REPORTE_POR_PROYECTO' ? 'selected' : '' }}>
                            Último Reporte por Proyecto (Heterogéneo)
                        </option>
                    </select>
                </div>

                <!-- Fecha de Corte -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Fecha de Corte / Límite
                    </label>
                    <div class="flex gap-2">
                        <select name="fecha_corte_select"
                                onchange="document.getElementById('fecha_corte_input').value = this.value; this.form.submit();"
                                class="text-sm border-slate-300 rounded-lg shadow-sm focus:border-blue-800 focus:ring-blue-800 w-1/2">
                            <option value="">Fechas disponibles...</option>
                            @foreach($fechasCorteDisponibles as $fc)
                                <option value="{{ $fc->fecha_reporte }}" {{ $fechaCorteSeleccionada === $fc->fecha_reporte ? 'selected' : '' }}>
                                    {{ $fc->fecha_reporte }} ({{ $fc->total_proyectos }} proy.)
                                </option>
                            @endforeach
                        </select>
                        <input type="date"
                               id="fecha_corte_input"
                               name="fecha_corte"
                               value="{{ $fechaCorteSeleccionada }}"
                               class="text-sm border-slate-300 rounded-lg shadow-sm focus:border-blue-800 focus:ring-blue-800 w-1/2">
                    </div>
                </div>

                <!-- Estado del Proyecto -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Estado Vigente
                    </label>
                    <select name="estado" class="w-full text-sm border-slate-300 rounded-lg shadow-sm focus:border-blue-800 focus:ring-blue-800">
                        <option value="">Todos los estados</option>
                        <option value="Pendiente" {{ ($filtros['estado'] ?? '') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="En curso" {{ ($filtros['estado'] ?? '') === 'En curso' ? 'selected' : '' }}>En curso</option>
                        <option value="Cerrado" {{ ($filtros['estado'] ?? '') === 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                    </select>
                </div>

                <!-- Responsable Titular -->
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                        Responsable
                    </label>
                    <select name="id_responsable" class="w-full text-sm border-slate-300 rounded-lg shadow-sm focus:border-blue-800 focus:ring-blue-800">
                        <option value="">Todos los responsables</option>
                        @foreach($responsables as $r)
                            <option value="{{ $r->id_responsable }}" {{ ($filtros['id_responsable'] ?? '') == $r->id_responsable ? 'selected' : '' }}>
                                {{ $r->nombre }} ({{ $r->perfil }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pt-2 border-t border-slate-100">
                <div class="text-xs text-slate-500 italic">
                    Nota metodológica: Estado, responsable y línea base son vigentes. En corte común, proyectos sin corte exacto se excluyen solo del cálculo financiero.
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('tablero') }}" class="px-3 py-1.5 border border-slate-300 text-slate-700 text-xs font-medium rounded-lg hover:bg-slate-50 transition">
                        Limpiar Filtros
                    </a>
                    <button type="submit" class="px-4 py-1.5 bg-blue-900 text-white text-xs font-medium rounded-lg hover:bg-blue-800 shadow-sm transition">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- 3. Las Cuatro Tarjetas de KPIs Canónicos -->
    @php
        $resumen = $datos['resumen'];
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

        <!-- KPI 1: Avance de Hitos de Línea Base -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Avance de Hitos (LB)</span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-50 text-blue-800">
                        {{ $resumen['avance_hitos']['numerador'] }} / {{ $resumen['avance_hitos']['denominador'] }}
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900">
                        {{ $resumen['avance_hitos']['valor_formateado'] }}
                    </span>
                </div>
                <!-- Barra de Progreso -->
                @if($resumen['avance_hitos']['valor'] !== null)
                    <div class="w-full bg-slate-100 rounded-full h-2 mt-3">
                        <div class="bg-blue-800 h-2 rounded-full" style="width: {{ min(100, max(0, $resumen['avance_hitos']['valor'])) }}%"></div>
                    </div>
                @endif
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-between">
                <span>Cobertura:</span>
                <span class="font-medium text-slate-700">{{ $resumen['avance_hitos']['cobertura_proyectos'] }}</span>
            </div>
        </div>

        <!-- KPI 2: Proyectos con Retraso -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Proyectos con Retraso</span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'bg-red-50 text-red-800' : 'bg-emerald-50 text-emerald-800' }}">
                        {{ $resumen['proyectos_retraso']['numerador'] }} / {{ $resumen['proyectos_retraso']['denominador'] }}
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-red-600' : 'text-slate-900' }}">
                        {{ $resumen['proyectos_retraso']['valor_formateado'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    Activos evaluables con al menos un hito de línea base vencido al corte.
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-between">
                <span>Activos evaluables:</span>
                <span class="font-medium text-slate-700">{{ $resumen['proyectos_retraso']['cobertura_proyectos'] }}</span>
            </div>
        </div>

        <!-- KPI 3: Desviación Presupuestal -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Desviación Presupuestal</span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-slate-100 text-slate-700">
                        Gasto vs Plan
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold {{ ($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0 ? 'text-blue-900' : 'text-amber-600' }}">
                        {{ $resumen['desviacion_presupuestal']['valor_formateado'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-2">
                    @if(($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0)
                        Gasto acumulado por debajo de lo planificado (no presupone ahorro definitivo).
                    @elseif(($resumen['desviacion_presupuestal']['valor'] ?? 0) > 0)
                        Gasto acumulado por encima del presupuesto planificado al corte.
                    @else
                        Ejecución presupuestal exactamente alineada al plan.
                    @endif
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500">
                <div class="flex justify-between">
                    <span>Planificado:</span>
                    <span class="font-medium text-slate-700">S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}</span>
                </div>
                <div class="flex justify-between mt-1">
                    <span>Gasto Ejecutado:</span>
                    <span class="font-medium text-slate-700">S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Composición de Estados de Cartera -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Proyectos por Estado</span>
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-slate-100 text-slate-700">
                        Total {{ $resumen['total_proyectos'] }}
                    </span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-100">
                        <div class="text-lg font-bold text-emerald-800">{{ $resumen['conteo_estados']['En curso'] }}</div>
                        <div class="text-[10px] font-semibold text-emerald-700 uppercase">En curso</div>
                    </div>
                    <div class="p-2 rounded-lg bg-amber-50 border border-amber-100">
                        <div class="text-lg font-bold text-amber-800">{{ $resumen['conteo_estados']['Pendiente'] }}</div>
                        <div class="text-[10px] font-semibold text-amber-700 uppercase">Pendiente</div>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-100 border border-slate-200">
                        <div class="text-lg font-bold text-slate-800">{{ $resumen['conteo_estados']['Cerrado'] }}</div>
                        <div class="text-[10px] font-semibold text-slate-600 uppercase">Cerrado</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-between">
                <span>Monto Cartera:</span>
                <span class="font-bold text-slate-800">S/ {{ number_format($resumen['monto_total_cartera'], 2) }}</span>
            </div>
        </div>

    </div>

    <!-- 4. Gráficos Analíticos Interactivos (Chart.js) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Gráfico 1: Evolución Financiera de Cortes -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                Evolución Financiera por Fecha de Corte
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Presupuesto planificado acumulado vs Gasto acumulado en las fechas de reporte registradas.
            </p>
            <div class="h-64 mt-4">
                <canvas id="graficoEvolucion"></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Composición por Estado -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                    Distribución de Cartera
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Proporción de proyectos según estado vigente.
                </p>
                <div class="h-48 mt-4 flex items-center justify-center">
                    <canvas id="graficoEstados"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-around text-center">
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-600 mr-1"></span>
                    En curso: <strong>{{ $resumen['conteo_estados']['En curso'] }}</strong>
                </div>
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 mr-1"></span>
                    Pendiente: <strong>{{ $resumen['conteo_estados']['Pendiente'] }}</strong>
                </div>
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-500 mr-1"></span>
                    Cerrado: <strong>{{ $resumen['conteo_estados']['Cerrado'] }}</strong>
                </div>
            </div>
        </div>

    </div>

    <!-- 5. Tabla Detallada de Proyectos Evaluados -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">Detalle de Proyectos en el Corte Evaluado</h2>
                <p class="text-xs text-slate-500">
                    Indicadores calculados proyecto por proyecto según la modalidad {{ $datos['contexto']['modo'] }}.
                </p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded bg-slate-100 text-slate-700">
                Mostrando {{ count($datos['proyectos']) }} proyectos
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 uppercase font-semibold">
                        <th class="py-3 px-4">Código</th>
                        <th class="py-3 px-4">Proyecto</th>
                        <th class="py-3 px-4">Responsable</th>
                        <th class="py-3 px-4 text-center">Estado</th>
                        <th class="py-3 px-4 text-center">Fecha Corte</th>
                        <th class="py-3 px-4 text-center">Hitos LB (Conc/Total)</th>
                        <th class="py-3 px-4 text-right">Planificado (S/)</th>
                        <th class="py-3 px-4 text-right">Gasto (S/)</th>
                        <th class="py-3 px-4 text-center">Desviación</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($datos['proyectos'] as $proy)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3 px-4 font-mono font-bold text-blue-900 whitespace-nowrap">
                                {{ $proy['codigo_pgd'] }}
                            </td>
                            <td class="py-3 px-4 max-w-xs">
                                <a href="{{ route('proyectos.show', $proy['id_proyecto']) }}" class="font-semibold text-slate-900 hover:text-blue-900 hover:underline">
                                    {{ $proy['nombre'] }}
                                </a>
                                <div class="text-[11px] text-slate-500 truncate" title="{{ $proy['objetivo']['descripcion'] ?? '' }}">
                                    {{ $proy['objetivo']['codigo'] ?? '' }} - {{ $proy['objetivo']['descripcion'] ?? '' }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                {{ $proy['responsable']['nombre'] }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['estado'] === 'En curso')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">En curso</span>
                                @elseif($proy['estado'] === 'Pendiente')
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">Pendiente</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200 text-slate-700">Cerrado</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-slate-600">
                                {{ $proy['fecha_evaluacion'] ?? 'Sin fecha' }}
                            </td>
                            <!-- Hitos LB -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['hitos']['evaluable'])
                                    <div class="font-medium text-slate-800">
                                        {{ $proy['hitos']['concluidos_lb'] }} / {{ $proy['hitos']['total_lb'] }}
                                        <span class="text-slate-500 font-normal">({{ $proy['hitos']['avance_formateado'] }})</span>
                                    </div>
                                    @if($proy['hitos']['con_retraso'])
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-100 text-red-700">
                                            {{ $proy['hitos']['vencidos_lb'] }} vencido(s)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">Sin línea base</span>
                                @endif
                            </td>
                            <!-- Planificado -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                @if($proy['financiero']['tiene_corte'] && $proy['financiero']['presupuesto_planificado'] !== null)
                                    {{ number_format($proy['financiero']['presupuesto_planificado'], 2) }}
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <!-- Gasto -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                @if($proy['financiero']['tiene_corte'] && $proy['financiero']['gasto_acumulado'] !== null)
                                    {{ number_format($proy['financiero']['gasto_acumulado'], 2) }}
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <!-- Desviación -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['financiero']['tiene_corte'])
                                    <span class="font-bold {{ ($proy['financiero']['desviacion_pct'] ?? 0) < 0 ? 'text-blue-900' : 'text-amber-700' }}">
                                        {{ $proy['financiero']['desviacion_formateada'] }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Sin corte exacto
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('proyectos.show', $proy['id_proyecto']) }}"
                                   class="text-blue-900 hover:text-blue-700 font-semibold underline text-xs">
                                    Ver Ficha
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-500">
                                No se encontraron proyectos que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 6. Panel de Alertas de Seguimiento (RF08) -->
    @if($alertas->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-amber-50 border-b border-amber-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-amber-900">Alertas de Calidad y Retrasos de Cronograma</h3>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">
                    {{ $alertas->count() }} alerta(s)
                </span>
            </div>
            <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto">
                @foreach($alertas as $alerta)
                    <div class="p-3 text-xs flex items-center justify-between hover:bg-slate-50 transition">
                        <div>
                            <span class="font-bold text-slate-900">[{{ $alerta->codigo_pgd ?? 'PGD' }}]</span>
                            <span class="text-slate-700 ml-1">{{ $alerta->detalle }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500 whitespace-nowrap ml-4">
                            Resp: {{ $alerta->responsable }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<!-- Scripts de Gráficos (Chart.js) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Gráfico de Evolución Financiera
        const ctxEvolucion = document.getElementById('graficoEvolucion');
        if (ctxEvolucion) {
            const cortes = @json($evolucionCortes);
            const labels = cortes.map(c => c.fecha_reporte);
            const dataPlan = cortes.map(c => parseFloat(c.total_planificado));
            const dataGasto = cortes.map(c => parseFloat(c.total_gasto));

            new Chart(ctxEvolucion, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Planificado Acumulado (S/)',
                            data: dataPlan,
                            backgroundColor: '#94a3b8',
                            borderRadius: 4,
                        },
                        {
                            label: 'Gasto Acumulado (S/)',
                            data: dataGasto,
                            backgroundColor: '#1e3a8a',
                            borderRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return 'S/ ' + (value / 1000000).toFixed(1) + 'M';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }

        // 2. Gráfico de Distribución por Estado
        const ctxEstados = document.getElementById('graficoEstados');
        if (ctxEstados) {
            const conteos = @json($resumen['conteo_estados']);
            new Chart(ctxEstados, {
                type: 'doughnut',
                data: {
                    labels: ['En curso', 'Pendiente', 'Cerrado'],
                    datasets: [{
                        data: [conteos['En curso'], conteos['Pendiente'], conteos['Cerrado']],
                        backgroundColor: ['#059669', '#d97706', '#64748b'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        }
                    }
                }
            });
        }
    });

    // Función de recarga ETL vía AJAX
    function ejecutarRecargaEtl() {
        if (!confirm('¿Desea ejecutar la recarga completa del Data Warehouse ahora?')) return;

        fetch('{{ route("etl.ejecutar") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                alert('Data Warehouse actualizado con éxito.');
                window.location.reload();
            } else {
                alert('Error al sincronizar: ' + (data.mensaje || 'Error desconocido'));
            }
        })
        .catch(error => {
            alert('Error de conexión con el servidor.');
        });
    }
</script>
@endsection
