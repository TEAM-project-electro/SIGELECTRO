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
<body class="bg-slate-100 text-slate-900 min-h-screen p-4 md:p-6 antialiased font-sans">

    <!-- Barra de Acciones (No imprimible) -->
    <div class="no-print max-w-7xl mx-auto mb-4 flex flex-wrap items-center justify-between gap-3 bg-white p-3.5 rounded-xl border border-slate-200">
        <a href="{{ route('tablero', request()->query()) }}"
           class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">
            &larr; Volver al Tablero
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('reportes.csv', request()->query()) }}"
               class="px-3.5 py-1.5 border border-slate-300 text-slate-700 rounded-lg text-xs font-semibold hover:bg-slate-50">
                Exportar CSV
            </a>
            <button onclick="window.print()"
                    class="px-4 py-1.5 bg-brand-800 text-white rounded-lg text-xs font-semibold hover:bg-brand-900">
                Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <!-- Documento Imprimible -->
    <div class="max-w-7xl mx-auto bg-white p-6 rounded-xl border border-slate-200">

        <!-- Encabezado -->
        <div class="border-b border-slate-300 pb-4 mb-5">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <p class="text-[11px] font-bold tracking-wider text-brand-900 uppercase">
                        ELECTROCENTRO S. A. &middot; SEGUIMIENTO DEL PLAN DE GOBIERNO DIGITAL
                    </p>
                    <h1 class="text-xl font-bold text-slate-900 mt-0.5">
                        Reporte Gerencial de Cartera PGD
                    </h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="px-2 py-0.5 rounded font-semibold bg-amber-50 text-amber-900 border border-amber-200 text-[10px]">
                            DATOS DEMOSTRATIVOS
                        </span>
                        <span>&middot; Modalidad: <strong>{{ $datos['contexto']['modo'] }}</strong></span>
                        <span>&middot; Corte: <strong class="font-mono">{{ $datos['contexto']['fecha_corte'] }}</strong> ({{ $datos['contexto']['rango_fechas_texto'] }})</span>
                        <span>&middot; Origen: <strong>{{ $origen === 'DW' ? 'Data Mart Analítico' : 'Base Operativa' }}</strong></span>
                        <span>&middot; Revisión: <strong class="font-mono">v{{ $revision_actual }}</strong></span>
                    </div>
                </div>
                <div class="text-right text-xs text-slate-500 shrink-0">
                    <div>Emisión: <span class="font-mono text-slate-700">{{ $fecha_emision }}</span></div>
                    <div>Usuario: {{ $usuario_emisor }}</div>
                </div>
            </div>

            @if($revision_discrepancia)
                <div class="mt-3 p-2.5 bg-amber-50 border border-amber-300 text-amber-900 text-xs rounded-lg">
                    <strong>Aviso de concurrencia:</strong> Los datos cambiaron después de abrir la consulta (versión previa: v{{ $revision_esperada }}, versión actual: v{{ $revision_actual }}).
                </div>
            @endif
        </div>

        <!-- Indicadores Principales -->
        @php
            $resumen = $datos['resumen'];
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Avance Hitos (LB)</div>
                <div class="text-xl font-bold text-slate-900 mt-0.5">{{ $resumen['avance_hitos']['valor_formateado'] }}</div>
                <div class="text-[10px] text-slate-600 mt-0.5">
                    {{ $resumen['avance_hitos']['numerador'] }} de {{ $resumen['avance_hitos']['denominador'] }} hitos ({{ $resumen['avance_hitos']['cobertura_proyectos'] }} proy.)
                </div>
            </div>

            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Proyectos con Retraso</div>
                <div class="text-xl font-bold {{ $resumen['proyectos_retraso']['numerador'] > 0 ? 'text-rose-700' : 'text-slate-900' }} mt-0.5">
                    {{ $resumen['proyectos_retraso']['valor_formateado'] }}
                </div>
                <div class="text-[10px] text-slate-600 mt-0.5">
                    {{ $resumen['proyectos_retraso']['numerador'] }} de {{ $resumen['proyectos_retraso']['denominador'] }} activos
                </div>
            </div>

            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Desviación Presupuestal</div>
                <div class="text-xl font-bold text-slate-900 mt-0.5">{{ $resumen['desviacion_presupuestal']['valor_formateado'] }}</div>
                <div class="text-[10px] text-slate-600 mt-0.5 font-mono">
                    Gasto: S/ {{ number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2) }} / Plan: S/ {{ number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2) }}
                </div>
            </div>

            <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                <div class="text-[10px] font-bold text-slate-500 uppercase">Cartera PGD</div>
                <div class="text-xl font-bold text-slate-900 mt-0.5">{{ $resumen['total_proyectos'] }} Proyectos</div>
                <div class="text-[10px] text-slate-600 mt-0.5 font-mono">
                    Total: S/ {{ number_format($resumen['monto_total_cartera'], 2) }}
                </div>
            </div>
        </div>

        <!-- Tabla de Proyectos -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse border border-slate-300 text-[9.5px]">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 uppercase font-bold border-b border-slate-300 text-[9px]">
                        <th class="py-1.5 px-2 border-r border-slate-300">Cód.</th>
                        <th class="py-1.5 px-2 border-r border-slate-300">Proyecto</th>
                        <th class="py-1.5 px-2 border-r border-slate-300">Responsable</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-center">Estado</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-right">Monto (S/)</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-center">Corte</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-center">Hitos LB</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-center">Avance</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-right">Plan (S/)</th>
                        <th class="py-1.5 px-2 border-r border-slate-300 text-right">Gasto (S/)</th>
                        <th class="py-1.5 px-2 text-center">Desviación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($datos['proyectos'] as $p)
                        <tr>
                            <td class="py-1.5 px-2 font-mono font-bold text-brand-900 border-r border-slate-200 whitespace-nowrap">
                                {{ $p['codigo_pgd'] }}
                            </td>
                            <td class="py-1.5 px-2 font-semibold text-slate-900 border-r border-slate-200">
                                {{ $p['nombre'] }}
                            </td>
                            <td class="py-1.5 px-2 border-r border-slate-200 whitespace-nowrap text-slate-700">
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
                            <td class="py-1.5 px-2 text-center border-r border-slate-200 whitespace-nowrap font-mono">
                                @if($p['hitos']['evaluable'])
                                    {{ $p['hitos']['concluidos_lb'] }}/{{ $p['hitos']['total_lb'] }}
                                @else
                                    Sin LB
                                @endif
                            </td>
                            <td class="py-1.5 px-2 text-center font-semibold border-r border-slate-200 whitespace-nowrap">
                                {{ $p['hitos']['avance_formateado'] }}
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap">
                                {{ $p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null ? number_format($p['financiero']['presupuesto_planificado'], 2) : '-' }}
                            </td>
                            <td class="py-1.5 px-2 text-right font-mono border-r border-slate-200 whitespace-nowrap">
                                {{ $p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null ? number_format($p['financiero']['gasto_acumulado'], 2) : '-' }}
                            </td>
                            <td class="py-1.5 px-2 text-center font-semibold whitespace-nowrap">
                                {{ $p['financiero']['tiene_corte'] ? $p['financiero']['desviacion_formateada'] : 'Sin corte' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
