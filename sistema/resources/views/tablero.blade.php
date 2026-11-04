@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- ============================================================ -->
    <!-- 1. BARRA DE CONTEXTO, ORIGEN Y ACCIONES GERENCIALES          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/90 p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                        DATOS DEMOSTRATIVOS PGD
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        Origen: {{ $origen === 'DW' ? 'Data Mart Analítico (dw_seguimiento_gd)' : 'Base Operativa en Vivo (seguimiento_gd)' }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-brand-50 text-brand-800 border border-brand-200"
                          title="Versión de control de cambios de la base de datos">
                        Rev. v{{ $datos['contexto']['revision_datos'] }}
                    </span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                    Tablero Gerencial de Seguimiento PGD
                </h1>
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $datos['contexto']['rango_fechas_texto'] }} &bull; Cartera de 10 Proyectos (S/ 8,623,550.00)</span>
                </p>
            </div>

            <!-- Controles de Conmutación Relacional vs DW y Sincronización -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Segmented Control de Origen -->
                <div class="inline-flex rounded-lg border border-slate-300 p-1 bg-slate-100/80 text-xs shadow-xs">
                    <a href="{{ request()->fullUrlWithQuery(['origen' => 'OPERATIVO']) }}"
                       class="px-3.5 py-1.5 rounded-md font-semibold transition {{ $origen === 'OPERATIVO' ? 'bg-brand-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Base Operativa
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['origen' => 'DW']) }}"
                       class="px-3.5 py-1.5 rounded-md font-semibold transition {{ $origen === 'DW' ? 'bg-brand-700 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                        Data Warehouse
                    </a>
                </div>

                <!-- Estado del DW / Sincronización -->
                @if($estadoDw['desactualizado'])
                    <div class="inline-flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-300">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span>DW Desactualizado (v{{ $estadoDw['revision_dw'] }} vs v{{ $estadoDw['version_operativa'] }})</span>
                        </span>
                        @if(Auth::user()->perfil === 'Administrador')
                            <button type="button"
                                    onclick="ejecutarRecargaEtl()"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Sincronizar DW
                            </button>
                        @endif
                    </div>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>DW Sincronizado (v{{ $estadoDw['revision_dw'] }})</span>
                    </span>
                @endif

                <!-- Botones de Exportación Segura -->
                <div class="flex items-center gap-1.5 pl-2 border-l border-slate-200">
                    <a href="{{ route('reportes.csv', array_merge(request()->query(), ['revision_esperada' => $datos['contexto']['revision_datos']])) }}"
                       title="Descargar datos en formato CSV UTF-8 con BOM y sanitizado contra inyecciones"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold rounded-lg shadow-xs transition">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('reportes.imprimir', array_merge(request()->query(), ['revision_esperada' => $datos['contexto']['revision_datos']])) }}"
                       target="_blank"
                       title="Abrir vista de impresión ejecutiva en formato A4 apaisado"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand-900 hover:bg-brand-800 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                        <svg class="w-3.5 h-3.5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Imprimir / PDF</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. PANEL DE FILTROS Y MODALIDAD TEMPORAL                     -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/90 p-5">
        <form method="GET" action="{{ route('tablero') }}" class="space-y-4">
            <input type="hidden" name="origen" value="{{ $origen }}">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Modalidad Temporal -->
                <div>
                    <label for="modo" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Modalidad Temporal
                    </label>
                    <select id="modo" name="modo"
                            onchange="this.form.submit()"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg shadow-xs focus:border-brand-600 focus:ring-brand-600 bg-slate-50 focus:bg-white font-medium">
                        <option value="CORTE_COMUN" {{ $filtros['modo'] === 'CORTE_COMUN' ? 'selected' : '' }}>
                            Corte Común (Sincrónico de Comité)
                        </option>
                        <option value="ULTIMO_REPORTE_POR_PROYECTO" {{ $filtros['modo'] === 'ULTIMO_REPORTE_POR_PROYECTO' ? 'selected' : '' }}>
                            Último Reporte por Proyecto (Operativo)
                        </option>
                    </select>
                </div>

                <!-- Fecha de Corte / Límite -->
                <div>
                    <label for="fecha_corte_select" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Fecha de Corte / Límite
                    </label>
                    <div class="flex gap-2">
                        <select id="fecha_corte_select" name="fecha_corte_select"
                                onchange="document.getElementById('fecha_corte_input').value = this.value; this.form.submit();"
                                class="w-3/5 text-xs border-slate-300 rounded-lg shadow-xs focus:border-brand-600 focus:ring-brand-600 bg-slate-50 focus:bg-white font-medium">
                            <option value="">Fechas registradas...</option>
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
                               class="w-2/5 text-xs border-slate-300 rounded-lg shadow-xs focus:border-brand-600 focus:ring-brand-600 bg-slate-50 focus:bg-white font-mono">
                    </div>
                </div>

                <!-- Estado del Proyecto -->
                <div>
                    <label for="estado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Estado Vigente
                    </label>
                    <select id="estado" name="estado"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg shadow-xs focus:border-brand-600 focus:ring-brand-600 bg-slate-50 focus:bg-white font-medium">
                        <option value="">Todos los estados</option>
                        <option value="En curso" {{ ($filtros['estado'] ?? '') === 'En curso' ? 'selected' : '' }}>En curso</option>
                        <option value="Cerrado" {{ ($filtros['estado'] ?? '') === 'Cerrado' ? 'selected' : '' }}>Cerrado</option>
                        <option value="Pendiente" {{ ($filtros['estado'] ?? '') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    </select>
                </div>

                <!-- Responsable Titular -->
                <div>
                    <label for="id_responsable" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Responsable Titular
                    </label>
                    <select id="id_responsable" name="id_responsable"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg shadow-xs focus:border-brand-600 focus:ring-brand-600 bg-slate-50 focus:bg-white font-medium">
                        <option value="">Todos los responsables</option>
                        @foreach($responsables as $r)
                            <option value="{{ $r->id_responsable }}" {{ ($filtros['id_responsable'] ?? '') == $r->id_responsable ? 'selected' : '' }}>
                                {{ $r->nombre }} ({{ $r->perfil }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <div class="text-[11px] text-slate-500 leading-normal">
                    <span class="font-semibold text-slate-700">Nota metodológica:</span> Estado, responsable y línea base son vigentes. En corte común, proyectos sin corte exacto se excluyen solo del cálculo financiero.
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('tablero') }}"
                       class="px-3.5 py-1.5 border border-slate-300 text-slate-700 text-xs font-semibold rounded-lg hover:bg-slate-50 transition">
                        Limpiar Filtros
                    </a>
                    <button type="submit"
                            class="px-4 py-1.5 bg-brand-700 hover:bg-brand-800 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ============================================================ -->
    <!-- 3. LAS CUATRO TARJETAS DE KPIS CANÓNICOS                     -->
    <!-- ============================================================ -->
    @php
        $resumen = $datos['resumen'];
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <!-- KPI 1: Avance de Hitos (Línea Base) -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between hover:shadow-sm transition">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        Avance de Hitos (LB)
                    </span>
                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded-full bg-brand-50 text-brand-800 border border-brand-200">
                        {{ $resumen['avance_hitos']['numerador'] }} / {{ $resumen['avance_hitos']['denominador'] }}
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 tabular-nums">
                        {{ $resumen['avance_hitos']['valor_formateado'] }}
                    </span>
                </div>
                <!-- Barra de Progreso Visual -->
                @if($resumen['avance_hitos']['valor'] !== null)
                    <div class="w-full bg-slate-100 rounded-full h-2 mt-3 overflow-hidden">
                        <div class="bg-brand-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ min(100, max(0, $resumen['avance_hitos']['valor'])) }}%"></div>
                    </div>
                @else
                    <div class="mt-3 text-xs text-slate-400 italic">
                        Sin hitos elegibles al corte
                    </div>
                @endif
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between items-center">
                <span>Cobertura:</span>
                <span class="font-semibold text-slate-700">{{ $resumen['avance_hitos']['cobertura_proyectos'] }}</span>
            </div>
        </div>

        <!-- KPI 2: Proyectos con Retraso -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between hover:shadow-sm transition">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        Proyectos con Retraso
                    </span>
                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded-full {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                        {{ $resumen['proyectos_retraso']['numerador'] }} / {{ $resumen['proyectos_retraso']['denominador'] }}
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tabular-nums {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">
                        {{ $resumen['proyectos_retraso']['valor_formateado'] }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                    {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'Iniciativas con al menos un hito de línea base vencido sin cierre.' : 'Ningún proyecto presenta hitos de línea base vencidos al corte.' }}
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between items-center">
                <span>Activos evaluables:</span>
                <span class="font-semibold text-slate-700">{{ $resumen['proyectos_retraso']['cobertura_proyectos'] }}</span>
            </div>
        </div>

        <!-- KPI 3: Desviación Presupuestal -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between hover:shadow-sm transition">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        Desviación Presupuestal
                    </span>
                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        Gasto vs Plan
                    </span>
                </div>
                <div class="mt-3 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold tabular-nums {{ ($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0 ? 'text-brand-900' : (($resumen['desviacion_presupuestal']['valor'] ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900') }}">
                        {{ $resumen['desviacion_presupuestal']['valor_formateado'] }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                    @if(($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0)
                        Gasto acumulado por debajo del plan (desfase temporal en el devengado).
                    @elseif(($resumen['desviacion_presupuestal']['valor'] ?? 0) > 0)
                        Gasto acumulado por encima del presupuesto planificado al corte.
                    @else
                        Ejecución presupuestal alineada al presupuesto planificado.
                    @endif
                </p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
                <div class="flex justify-between items-center">
                    <span>Planificado:</span>
                    <span class="font-semibold text-slate-800 font-mono">S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Gasto Devengado:</span>
                    <span class="font-semibold text-slate-800 font-mono">S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- KPI 4: Composición de Estados de Cartera -->
        <div class="bg-white p-5 rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between hover:shadow-sm transition">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        Proyectos por Estado
                    </span>
                    <span class="px-2 py-0.5 text-xs font-mono font-bold rounded-full bg-slate-100 text-slate-800 border border-slate-200">
                        Total {{ $resumen['total_proyectos'] }}
                    </span>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                        <div class="text-lg font-extrabold text-emerald-800 tabular-nums">{{ $resumen['conteo_estados']['En curso'] }}</div>
                        <div class="text-[10px] font-bold text-emerald-700 uppercase">En curso</div>
                    </div>
                    <div class="p-2 rounded-lg bg-amber-50 border border-amber-200">
                        <div class="text-lg font-extrabold text-amber-800 tabular-nums">{{ $resumen['conteo_estados']['Pendiente'] }}</div>
                        <div class="text-[10px] font-bold text-amber-700 uppercase">Pendiente</div>
                    </div>
                    <div class="p-2 rounded-lg bg-slate-100 border border-slate-200">
                        <div class="text-lg font-extrabold text-slate-800 tabular-nums">{{ $resumen['conteo_estados']['Cerrado'] }}</div>
                        <div class="text-[10px] font-bold text-slate-600 uppercase">Cerrado</div>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between items-center">
                <span>Presupuesto Cartera:</span>
                <span class="font-bold text-slate-900 font-mono">S/ {{ number_format($resumen['monto_total_cartera'], 2) }}</span>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- 4. GRÁFICOS ANALÍTICOS (Chart.js compilado localmente)        -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Gráfico 1: Evolución Financiera de Cortes -->
        <div class="lg:col-span-2 bg-white p-5 sm:p-6 rounded-xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Evolución Financiera por Fecha de Corte
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Presupuesto planificado vs Gasto devengado acumulado en las fechas de reporte registradas.
                    </p>
                </div>
                <span class="text-[11px] font-mono text-slate-400">Escala: Soles (PEN)</span>
            </div>
            <div class="h-64 mt-4">
                <canvas id="graficoEvolucion"></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Composición por Estado -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
            <div>
                <div class="pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Distribución de Cartera
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Proporción de proyectos según estado vigente.
                    </p>
                </div>
                <div class="h-48 mt-4 flex items-center justify-center">
                    <canvas id="graficoEstados"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-around text-center">
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-600 mr-1"></span>
                    <span class="text-slate-700 font-semibold">En curso:</span> <strong>{{ $resumen['conteo_estados']['En curso'] }}</strong>
                </div>
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 mr-1"></span>
                    <span class="text-slate-700 font-semibold">Pendiente:</span> <strong>{{ $resumen['conteo_estados']['Pendiente'] }}</strong>
                </div>
                <div>
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-500 mr-1"></span>
                    <span class="text-slate-700 font-semibold">Cerrado:</span> <strong>{{ $resumen['conteo_estados']['Cerrado'] }}</strong>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- 5. TABLA ANALÍTICA DE PROYECTOS EVALUADOS                    -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="p-5 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">
                    Detalle de Proyectos en el Corte Evaluado
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Indicadores consolidados iniciativa por iniciativa según la modalidad <span class="font-mono font-semibold">{{ $datos['contexto']['modo'] }}</span>.
                </p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-brand-50 text-brand-800 border border-brand-200 self-start sm:self-auto">
                {{ count($datos['proyectos']) }} proyectos en vista
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
                        <th class="py-3 px-4">Código PGD</th>
                        <th class="py-3 px-4">Nombre del Proyecto</th>
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
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 font-mono font-bold text-brand-800 whitespace-nowrap">
                                {{ $proy['codigo_pgd'] }}
                            </td>
                            <td class="py-3 px-4 max-w-xs">
                                <a href="{{ route('proyectos.show', $proy['id_proyecto']) }}"
                                   class="font-semibold text-slate-900 hover:text-brand-700 hover:underline transition">
                                    {{ $proy['nombre'] }}
                                </a>
                                <div class="text-[11px] text-slate-500 truncate mt-0.5" title="{{ $proy['objetivo']['descripcion'] ?? '' }}">
                                    {{ $proy['objetivo']['codigo'] ?? '' }} &bull; {{ $proy['objetivo']['descripcion'] ?? '' }}
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-slate-700">
                                {{ $proy['responsable']['nombre'] }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['estado'] === 'En curso')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        En curso
                                    </span>
                                @elseif($proy['estado'] === 'Pendiente')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                        Pendiente
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500 mr-1.5"></span>
                                        Cerrado
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-slate-600">
                                {{ $proy['fecha_evaluacion'] ?? 'Sin fecha' }}
                            </td>
                            <!-- Hitos LB -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['hitos']['evaluable'])
                                    <div class="font-semibold text-slate-800">
                                        {{ $proy['hitos']['concluidos_lb'] }} / {{ $proy['hitos']['total_lb'] }}
                                        <span class="text-slate-500 font-normal">({{ $proy['hitos']['avance_formateado'] }})</span>
                                    </div>
                                    @if($proy['hitos']['con_retraso'])
                                        <span class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                            {{ $proy['hitos']['vencidos_lb'] }} vencido(s)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">Sin línea base</span>
                                @endif
                            </td>
                            <!-- Planificado -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono tabular-nums">
                                @if($proy['financiero']['tiene_corte'] && $proy['financiero']['presupuesto_planificado'] !== null)
                                    {{ number_format($proy['financiero']['presupuesto_planificado'], 2) }}
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <!-- Gasto -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono tabular-nums">
                                @if($proy['financiero']['tiene_corte'] && $proy['financiero']['gasto_acumulado'] !== null)
                                    {{ number_format($proy['financiero']['gasto_acumulado'], 2) }}
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <!-- Desviación -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($proy['financiero']['tiene_corte'])
                                    <span class="font-bold tabular-nums {{ ($proy['financiero']['desviacion_pct'] ?? 0) < 0 ? 'text-brand-900' : 'text-amber-800' }}">
                                        {{ $proy['financiero']['desviacion_formateada'] }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        Sin corte exacto
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('proyectos.show', $proy['id_proyecto']) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:bg-brand-50 rounded transition">
                                    <span>Ver Ficha</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-500">
                                <p class="text-sm font-semibold">No se encontraron proyectos para los filtros seleccionados.</p>
                                <p class="text-xs text-slate-400 mt-1">Intente cambiando el estado o el responsable titular.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 6. PANEL DE ALERTAS TEMPRANAS (RF08)                          -->
    <!-- ============================================================ -->
    @if($alertas->count() > 0)
        <div class="bg-white rounded-xl shadow-xs border border-amber-200 overflow-hidden">
            <div class="p-4 bg-amber-50/80 border-b border-amber-200 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 rounded-lg bg-amber-100 text-amber-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-amber-950">Alertas de Calidad y Retrasos de Cronograma (RF08)</h3>
                        <p class="text-[11px] text-amber-700">Monitoreo proactivo de desviaciones de línea base e hitos vencidos</p>
                    </div>
                </div>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-200 text-amber-900 border border-amber-300">
                    {{ $alertas->count() }} alerta(s)
                </span>
            </div>
            <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                @foreach($alertas as $alerta)
                    <div class="p-3.5 text-xs flex items-center justify-between hover:bg-amber-50/30 transition">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded font-mono font-bold text-[11px] bg-slate-100 text-brand-900 border border-slate-200">
                                PGD {{ $alerta->codigo_pgd ?? 'PGD' }}
                            </span>
                            <span class="text-slate-800 font-medium">{{ $alerta->detalle }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500 whitespace-nowrap ml-4">
                            Responsable: <strong class="text-slate-700">{{ $alerta->responsable }}</strong>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<!-- ============================================================ -->
<!-- 7. INICIALIZACIÓN DE CHART.JS (Usando bundle local de Vite)   -->
<!-- ============================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Chart === 'undefined') {
            console.error('Chart.js no está disponible en window.Chart.');
            return;
        }

        // 1. Gráfico de Evolución Financiera
        const ctxEvolucion = document.getElementById('graficoEvolucion');
        if (ctxEvolucion) {
            const cortes = @json($evolucionCortes);
            const labels = cortes.map(c => c.fecha_reporte);
            const dataPlan = cortes.map(c => parseFloat(c.total_planificado));
            const dataGasto = cortes.map(c => parseFloat(c.total_gasto));

            new window.Chart(ctxEvolucion, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Planificado Acumulado (S/)',
                            data: dataPlan,
                            backgroundColor: '#94a3b8',
                            borderRadius: 4,
                            maxBarThickness: 36,
                        },
                        {
                            label: 'Gasto Devengado (S/)',
                            data: dataGasto,
                            backgroundColor: '#003366',
                            borderRadius: 4,
                            maxBarThickness: 36,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: { size: 11, family: 'Inter, sans-serif' },
                                boxWidth: 12,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': S/ ' + new Intl.NumberFormat('es-PE', { minimumFractionDigits: 2 }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return 'S/ ' + (value / 1000000).toFixed(1) + 'M';
                                },
                                font: { size: 10 }
                            },
                            grid: {
                                color: '#f1f5f9',
                            }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // 2. Gráfico de Distribución por Estado
        const ctxEstados = document.getElementById('graficoEstados');
        if (ctxEstados) {
            const conteos = @json($resumen['conteo_estados']);
            new window.Chart(ctxEstados, {
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
                    cutout: '70%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });

    // Función de recarga ETL vía AJAX con manejo de errores seguro
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
