<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Gerencial PGD - Electrocentro S. A.</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 10px;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-break-inside-avoid {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            table {
                font-size: 9.5px;
            }
            thead {
                display: table-header-group;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 md:p-8 antialiased font-sans">

    <!-- Barra de Acciones (Oculta al Imprimir) -->
    <div class="no-print max-w-7xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-xl shadow-xs border border-slate-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('tablero', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al Tablero
            </a>
            <span class="text-xs text-slate-500 hidden sm:inline font-medium">
                Vista previa de reporte optimizada para formato A4 Horizontal.
            </span>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('reportes.csv', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-slate-900 transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar CSV
            </a>
            <button onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand-900 text-white rounded-lg text-xs font-bold hover:bg-brand-800 transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / PDF
            </button>
        </div>
    </div>

    <!-- Contenedor Principal Imprimible -->
    <div class="max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-xl shadow-xs border border-slate-200">

        <!-- 1. Encabezado Institucional del Reporte -->
        <div class="border-b-2 border-brand-900 pb-4 mb-6">
            <div class="flex justify-between items-start gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-brand-700"></span>
                        <span class="text-[11px] font-black tracking-widest text-brand-900 uppercase">
                            ELECTROCENTRO S. A. • SISTEMA DE INFORMACIÓN GERENCIAL (SIG)
                        </span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">
                        Reporte Gerencial de Seguimiento de Cartera PGD
                    </h1>
                    <div class="pt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="px-2 py-0.5 rounded font-bold bg-amber-50 text-amber-900 border border-amber-300 text-[10px]">
                            DATOS DEMOSTRATIVOS • PROTOTIPO ACADÉMICO
                        </span>
                        <span>• Moneda: <strong>PEN (S/)</strong></span>
                        <span>• Origen: <strong>{{ $origen === 'DW' ? 'Data Mart (dw_seguimiento_gd)' : 'Base Operativa (seguimiento_gd)' }}</strong></span>
                        <span>• Revisión: <strong class="font-mono">v{{ $revision_actual }}</strong></span>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500 shrink-0">
                    <div class="font-mono text-slate-700"><strong>Emisión:</strong> {{ $fecha_emision }}</div>
                    <div><strong>Usuario:</strong> {{ $usuario_emisor }}</div>
                    <div class="mt-1 text-[11px] font-bold text-brand-900 uppercase">Comité de Gobierno Digital</div>
                </div>
            </div>

            <!-- Manifiesto de Contexto Temporal -->
            <div class="mt-4 p-3.5 bg-slate-50/80 rounded-lg border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 font-bold block uppercase text-[10px] tracking-wider">Modalidad Temporal</span>
                    <strong class="text-slate-900">{{ $datos['contexto']['modo'] }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 font-bold block uppercase text-[10px] tracking-wider">Corte / Límite</span>
                    <strong class="text-slate-900 font-mono">{{ $datos['contexto']['fecha_corte'] }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 font-bold block uppercase text-[10px] tracking-wider">Rango Efectivo</span>
                    <span class="text-slate-700 font-mono">{{ $datos['contexto']['rango_fechas_texto'] }}</span>
                </div>
            </div>

            @if($revision_discrepancia)
                <div class="mt-3 p-3 bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-lg flex items-start gap-2">
                    <svg class="w-4 h-4 text-amber-700 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>
                        <strong>Aviso de concurrencia:</strong> La base operativa mutó luego de la consulta original en pantalla (Versión esperada: v{{ $revision_esperada }} vs versión actual: v{{ $revision_actual }}). Este reporte refleja fielmente la versión actual.
                    </span>
                </div>
            @endif
        </div>

        <!-- 2. Resumen de los Cuatro Indicadores Canónicos (RF04) -->
        @php
            $resumen = $datos['resumen'];
        @endphp
        <div class="mb-6 page-break-inside-avoid">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-3.5 bg-brand-900 rounded-sm"></span>
                1. Resumen Ejecutivo de Indicadores Canónicos (RF04)
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <!-- KPI 1: Avance -->
                <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Avance Hitos (LB)</div>
                    <div class="text-2xl font-black text-slate-900 mt-1 tabular-nums">
                        {{ $resumen['avance_hitos']['valor_formateado'] }}
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1">
                        {{ $resumen['avance_hitos']['numerador'] }} concluidos de {{ $resumen['avance_hitos']['denominador'] }} en LB
                    </div>
                    <div class="text-[9px] text-slate-500 mt-0.5">
                        Cob: {{ $resumen['avance_hitos']['cobertura_proyectos'] }}
                    </div>
                </div>

                <!-- KPI 2: Retraso -->
                <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Proyectos con Retraso</div>
                    <div class="text-2xl font-black {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-rose-700' : 'text-slate-900' }} mt-1 tabular-nums">
                        {{ $resumen['proyectos_retraso']['valor_formateado'] }}
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1">
                        {{ $resumen['proyectos_retraso']['numerador'] }} con retraso de {{ $resumen['proyectos_retraso']['denominador'] }} activos
                    </div>
                    <div class="text-[9px] text-slate-500 mt-0.5">
                        Cob: {{ $resumen['proyectos_retraso']['cobertura_proyectos'] }}
                    </div>
                </div>

                <!-- KPI 3: Desviación -->
                <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Desviación Presupuestal</div>
                    <div class="text-2xl font-black {{ ($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0 ? 'text-brand-900' : 'text-amber-800' }} mt-1 tabular-nums">
                        {{ $resumen['desviacion_presupuestal']['valor_formateado'] }}
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1 font-mono">
                        Gasto: S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }}
                    </div>
                    <div class="text-[9px] text-slate-500 mt-0.5 font-mono">
                        Plan: S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}
                    </div>
                </div>

                <!-- KPI 4: Composición -->
                <div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/60">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cartera PGD</div>
                    <div class="text-2xl font-black text-slate-900 mt-1 tabular-nums">
                        {{ $resumen['total_proyectos'] }} Proy.
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1">
                        En curso: {{ $resumen['conteo_estados']['En curso'] }} | Pend: {{ $resumen['conteo_estados']['Pendiente'] }} | Cerr: {{ $resumen['conteo_estados']['Cerrado'] }}
                    </div>
                    <div class="text-[9px] text-slate-700 mt-0.5 font-bold font-mono">
                        Total: S/ {{ number_format($resumen['monto_total_cartera'], 2) }}
                    </div>
                </div>
            </div>
            <div class="text-[9px] text-slate-500 mt-2 italic">
                * Desviación presupuestal negativa indica gasto por debajo del plan acumulado a la fecha y no presupone ahorro definitivo.
            </div>
        </div>

        <!-- 3. Detalle Individual de Proyectos -->
        <div class="mb-6">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center gap-2">
                <span class="w-1.5 h-3.5 bg-brand-900 rounded-sm"></span>
                2. Detalle de Proyectos en Cartera ({{ count($datos['proyectos']) }} proyectos)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-slate-300 text-[9.5px]">
                    <thead>
                        <tr class="bg-slate-100 text-slate-800 uppercase font-black border-b border-slate-300 text-[9px] tracking-wider">
                            <th class="py-2 px-2 border-r border-slate-300">Cód.</th>
                            <th class="py-2 px-2 border-r border-slate-300">Nombre del Proyecto</th>
                            <th class="py-2 px-2 border-r border-slate-300">Responsable</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-center">Estado</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-right">Monto (S/)</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-center">Fecha Corte</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-center">Hitos LB</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-center">Avance (%)</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-right">Plan (S/)</th>
                            <th class="py-2 px-2 border-r border-slate-300 text-right">Gasto (S/)</th>
                            <th class="py-2 px-2 text-center">Desv. (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($datos['proyectos'] as $p)
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-1.5 px-2 font-mono font-bold text-brand-900 border-r border-slate-200 whitespace-nowrap">
                                    {{ $p['codigo_pgd'] }}
                                </td>
                                <td class="py-1.5 px-2 font-bold text-slate-900 border-r border-slate-200">
                                    {{ $p['nombre'] }}
                                    <div class="text-[8.5px] text-slate-500 font-normal">
                                        {{ $p['objetivo']['codigo'] ?? '' }} - {{ $p['objetivo']['descripcion'] ?? '' }}
                                    </div>
                                </td>
                                <td class="py-1.5 px-2 border-r border-slate-200 whitespace-nowrap text-slate-700">
                                    {{ $p['responsable']['nombre'] }}
                                </td>
                                <td class="py-1.5 px-2 text-center border-r border-slate-200 whitespace-nowrap font-semibold">
                                    @if($p['estado'] === 'En curso')
                                        <span class="text-brand-900 font-bold">En curso</span>
                                    @elseif($p['estado'] === 'Pendiente')
                                        <span class="text-amber-800 font-bold">Pendiente</span>
                                    @else
                                        <span class="text-slate-600">Cerrado</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap tabular-nums">
                                    {{ number_format($p['monto'], 2) }}
                                </td>
                                <td class="py-1.5 px-2 text-center font-mono border-r border-slate-200 whitespace-nowrap text-slate-600 tabular-nums">
                                    {{ $p['fecha_evaluacion'] ?? 'Sin corte' }}
                                </td>
                                <td class="py-1.5 px-2 text-center border-r border-slate-200 whitespace-nowrap font-mono">
                                    @if($p['hitos']['evaluable'])
                                        {{ $p['hitos']['concluidos_lb'] }} / {{ $p['hitos']['total_lb'] }}
                                        @if($p['hitos']['con_retraso'])
                                            <span class="text-rose-700 font-bold">({{ $p['hitos']['vencidos_lb'] }} ven.)</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">Sin LB</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-center font-bold border-r border-slate-200 whitespace-nowrap tabular-nums">
                                    {{ $p['hitos']['avance_formateado'] }}
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap tabular-nums">
                                    @if($p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null)
                                        {{ number_format($p['financiero']['presupuesto_planificado'], 2) }}
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap tabular-nums">
                                    @if($p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null)
                                        {{ number_format($p['financiero']['gasto_acumulado'], 2) }}
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-center font-bold whitespace-nowrap tabular-nums">
                                    @if($p['financiero']['tiene_corte'])
                                        <span class="{{ ($p['financiero']['desviacion_pct'] ?? 0) < 0 ? 'text-brand-900' : 'text-amber-800' }}">
                                            {{ $p['financiero']['desviacion_formateada'] }}
                                        </span>
                                    @else
                                        <span class="text-[9px] text-amber-700 font-normal">Sin corte</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Sección de Firmas y Certificación (Visible en Impresión) -->
        <div class="mt-8 pt-6 border-t border-slate-200 page-break-inside-avoid">
            <div class="grid grid-cols-2 gap-12 text-center pt-8">
                <div class="border-t border-slate-400 pt-2">
                    <p class="text-xs font-bold text-slate-800">Responsable de Cartera PGD</p>
                    <p class="text-[10px] text-slate-500">Gerencia de Tecnologías de la Información y Transformación Digital</p>
                    <p class="text-[9px] text-slate-400 mt-1">Electrocentro S. A.</p>
                </div>
                <div class="border-t border-slate-400 pt-2">
                    <p class="text-xs font-bold text-slate-800">Comité de Gobierno Digital</p>
                    <p class="text-[10px] text-slate-500">Supervisión y Conformidad Ejecutiva</p>
                    <p class="text-[9px] text-slate-400 mt-1">Electrocentro S. A.</p>
                </div>
            </div>
        </div>

        <!-- 5. Pie de Reporte y Metadatos -->
        <div class="mt-6 pt-3 border-t border-slate-200 text-[9px] text-slate-400 flex flex-col md:flex-row justify-between items-center gap-2">
            <div>
                SIG Electrocentro S. A. • Documento generado conforme a la metodología de seguimiento de Gobierno Digital.
            </div>
            <div class="font-mono">
                Consistencia técnica: Rev. v{{ $revision_actual }} • Hash de trazabilidad activo
            </div>
        </div>

    </div>

</body>
</html>
