<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Gerencial PGD - Electrocentro S. A.</title>
    <!-- Tailwind CSS compilado o CDN para modo de impresión autónomo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 11px;
            }
            .no-print {
                display: none !important;
            }
            .page-break-inside-avoid {
                break-inside: avoid;
            }
            table {
                font-size: 10px;
            }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 p-4 md:p-8">

    <!-- Barra de Acciones (Oculta al Imprimir) -->
    <div class="no-print max-w-7xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <a href="{{ route('tablero', request()->query()) }}"
               class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                &larr; Volver al Tablero
            </a>
            <span class="text-xs text-slate-500">
                Vista previa de impresión preparada para A4 Horizontal.
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reportes.csv', request()->query()) }}"
               class="px-4 py-2 bg-slate-700 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition shadow-sm">
                Descargar CSV
            </a>
            <button onclick="window.print()"
                    class="px-5 py-2 bg-blue-900 text-white rounded-lg text-xs font-semibold hover:bg-blue-800 transition shadow-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / Guardar como PDF
            </button>
        </div>
    </div>

    <!-- Contenedor Principal Imprimible -->
    <div class="max-w-7xl mx-auto bg-white p-6 md:p-10 rounded-xl shadow-sm border border-slate-200">

        <!-- 1. Encabezado Institucional del Reporte -->
        <div class="border-b-2 border-blue-900 pb-4 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-[10px] font-bold tracking-widest text-blue-900 uppercase">
                        SISTEMA DE INFORMACIÓN GERENCIAL (SIG)
                    </div>
                    <h1 class="text-xl md:text-2xl font-black text-slate-900 mt-1">
                        Reporte Gerencial de Seguimiento de Cartera PGD
                    </h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="px-2 py-0.5 rounded font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            DATOS DEMOSTRATIVOS - PROTOTIPO ACADÉMICO
                        </span>
                        <span>• Moneda: PEN (S/)</span>
                        <span>• Origen: {{ $origen === 'DW' ? 'Data Mart (dw_seguimiento_gd)' : 'Base Operativa (seguimiento_gd)' }}</span>
                        <span>• Revisión: <strong>v{{ $revision_actual }}</strong></span>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500">
                    <div><strong>Emisión:</strong> {{ $fecha_emision }}</div>
                    <div><strong>Usuario:</strong> {{ $usuario_emisor }}</div>
                    <div class="mt-1 font-semibold text-slate-700">Electrocentro S. A.</div>
                </div>
            </div>

            <!-- Manifiesto de Contexto Temporal -->
            <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-slate-500 font-semibold block uppercase text-[10px]">Modalidad Temporal:</span>
                    <strong class="text-slate-800">{{ $datos['contexto']['modo'] }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 font-semibold block uppercase text-[10px]">Corte / Límite:</span>
                    <strong class="text-slate-800">{{ $datos['contexto']['fecha_corte'] }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 font-semibold block uppercase text-[10px]">Rango Efectivo:</span>
                    <span class="text-slate-700">{{ $datos['contexto']['rango_fechas_texto'] }}</span>
                </div>
            </div>

            @if($revision_discrepancia)
                <div class="mt-3 p-2 bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded">
                    <strong>Aviso de concurrencia:</strong> La base operativa mutó luego de la consulta original en pantalla (Versión inicial: v{{ $revision_esperada }} vs versión actual: v{{ $revision_actual }}). Este reporte refleja fielmente la versión actual.
                </div>
            @endif
        </div>

        <!-- 2. Resumen de los Cuatro Indicadores Canónicos (RF04) -->
        @php
            $resumen = $datos['resumen'];
        @endphp
        <div class="mb-6 page-break-inside-avoid">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                1. Resumen Ejecutivo de Indicadores (RF04)
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- KPI 1: Avance -->
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Avance Hitos (LB)</div>
                    <div class="text-2xl font-black text-slate-900 mt-1">
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
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Proyectos con Retraso</div>
                    <div class="text-2xl font-black {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-red-700' : 'text-slate-900' }} mt-1">
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
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Desviación Presupuestal</div>
                    <div class="text-2xl font-black {{ ($resumen['desviacion_presupuestal']['valor'] ?? 0) < 0 ? 'text-blue-900' : 'text-amber-700' }} mt-1">
                        {{ $resumen['desviacion_presupuestal']['valor_formateado'] }}
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1">
                        Gasto: S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }}
                    </div>
                    <div class="text-[9px] text-slate-500 mt-0.5">
                        Plan: S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}
                    </div>
                </div>

                <!-- KPI 4: Composición -->
                <div class="p-4 rounded-lg border border-slate-200 bg-slate-50">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cartera PGD</div>
                    <div class="text-2xl font-black text-slate-900 mt-1">
                        {{ $resumen['total_proyectos'] }} Proy.
                    </div>
                    <div class="text-[10px] text-slate-600 mt-1">
                        En curso: {{ $resumen['conteo_estados']['En curso'] }} | Pend: {{ $resumen['conteo_estados']['Pendiente'] }} | Cerr: {{ $resumen['conteo_estados']['Cerrado'] }}
                    </div>
                    <div class="text-[9px] text-slate-500 mt-0.5 font-bold">
                        Total: S/ {{ number_format($resumen['monto_total_cartera'], 2) }}
                    </div>
                </div>
            </div>
            <div class="text-[10px] text-slate-500 mt-2 italic">
                * Desviación presupuestal negativa indica gasto por debajo del plan acumulado a la fecha y no presupone ahorro definitivo.
            </div>
        </div>

        <!-- 3. Detalle Individual de Proyectos -->
        <div class="mb-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3">
                2. Detalle de Proyectos en Cartera ({{ count($datos['proyectos']) }} proyectos)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-slate-300 text-[10px]">
                    <thead>
                        <tr class="bg-slate-100 text-slate-800 uppercase font-bold border-b border-slate-300">
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
                            <tr class="hover:bg-slate-50">
                                <td class="py-1.5 px-2 font-mono font-bold text-blue-900 border-r border-slate-200 whitespace-nowrap">
                                    {{ $p['codigo_pgd'] }}
                                </td>
                                <td class="py-1.5 px-2 font-semibold text-slate-900 border-r border-slate-200">
                                    {{ $p['nombre'] }}
                                    <div class="text-[9px] text-slate-500 font-normal">
                                        {{ $p['objetivo']['codigo'] ?? '' }} - {{ $p['objetivo']['descripcion'] ?? '' }}
                                    </div>
                                </td>
                                <td class="py-1.5 px-2 border-r border-slate-200 whitespace-nowrap">
                                    {{ $p['responsable']['nombre'] }}
                                </td>
                                <td class="py-1.5 px-2 text-center border-r border-slate-200 whitespace-nowrap">
                                    {{ $p['estado'] }}
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap">
                                    {{ number_format($p['monto'], 2) }}
                                </td>
                                <td class="py-1.5 px-2 text-center font-mono border-r border-slate-200 whitespace-nowrap text-slate-600">
                                    {{ $p['fecha_evaluacion'] ?? 'Sin corte' }}
                                </td>
                                <td class="py-1.5 px-2 text-center border-r border-slate-200 whitespace-nowrap">
                                    @if($p['hitos']['evaluable'])
                                        {{ $p['hitos']['concluidos_lb'] }} / {{ $p['hitos']['total_lb'] }}
                                        @if($p['hitos']['con_retraso'])
                                            <span class="text-red-600 font-bold">({{ $p['hitos']['vencidos_lb'] }} ven.)</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 italic">Sin LB</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-center font-bold border-r border-slate-200 whitespace-nowrap">
                                    {{ $p['hitos']['avance_formateado'] }}
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap">
                                    @if($p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null)
                                        {{ number_format($p['financiero']['presupuesto_planificado'], 2) }}
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap">
                                    @if($p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null)
                                        {{ number_format($p['financiero']['gasto_acumulado'], 2) }}
                                    @else
                                        <span class="text-slate-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="py-1.5 px-2 text-center font-bold whitespace-nowrap">
                                    @if($p['financiero']['tiene_corte'])
                                        <span class="{{ ($p['financiero']['desviacion_pct'] ?? 0) < 0 ? 'text-blue-900' : 'text-amber-700' }}">
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

        <!-- 4. Pie de Reporte y Certificación -->
        <div class="mt-8 pt-4 border-t border-slate-300 text-[9px] text-slate-500 flex flex-col md:flex-row justify-between items-center gap-2">
            <div>
                SIG Electrocentro S. A. • Documento generado automáticamente para fines de supervisión gerencial.
            </div>
            <div>
                Página 1 de 1 • Registro de consistencia: Rev. v{{ $revision_actual }}
            </div>
        </div>

    </div>

</body>
</html>
