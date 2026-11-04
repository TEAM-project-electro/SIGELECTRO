<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoGd;
use App\Models\Responsable;
use App\Services\EtlService;
use App\Services\IndicadoresService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TableroController extends Controller
{
    protected IndicadoresService $indicadoresService;
    protected EtlService $etlService;

    public function __construct(IndicadoresService $indicadoresService, EtlService $etlService)
    {
        $this->indicadoresService = $indicadoresService;
        $this->etlService = $etlService;
    }

    /**
     * Muestra el tablero interactivo de seguimiento gerencial.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'modo' => ['nullable', 'string', Rule::in([IndicadoresService::MODO_CORTE_COMUN, IndicadoresService::MODO_ULTIMO_REPORTE])],
            'fecha_corte' => ['nullable', 'date_format:Y-m-d'],
            'id_objetivo' => ['nullable', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['nullable', 'integer', 'exists:responsable,id_responsable'],
            'estado' => ['nullable', 'string', Rule::in(['Pendiente', 'En curso', 'Cerrado'])],
            'q' => ['nullable', 'string', 'max:100'],
            'origen' => ['nullable', 'string', Rule::in(['OPERATIVO', 'DW'])],
        ]);

        $modo = $validated['modo'] ?? IndicadoresService::MODO_CORTE_COMUN;
        $origen = $validated['origen'] ?? 'OPERATIVO';

        // Fechas de corte disponibles en la base de datos
        $fechasCorteDisponibles = DB::table('avance_presupuestal')
            ->select('fecha_reporte', DB::raw('COUNT(*) as total_proyectos'))
            ->groupBy('fecha_reporte')
            ->orderByDesc('fecha_reporte')
            ->get();

        $fechaCortePredeterminada = $fechasCorteDisponibles->first()->fecha_reporte ?? '2024-06-30';
        $fechaCorte = $validated['fecha_corte'] ?? $fechaCortePredeterminada;

        $filtros = [
            'modo' => $modo,
            'fecha_corte' => $fechaCorte,
            'id_objetivo' => $validated['id_objetivo'] ?? null,
            'id_responsable' => $validated['id_responsable'] ?? null,
            'estado' => $validated['estado'] ?? null,
            'q' => $validated['q'] ?? null,
        ];

        // Estado del Data Warehouse y sincronización
        $estadoDw = $this->etlService->estaDesactualizado();

        // 1. Obtención de datos según el origen elegido
        if ($origen === 'DW') {
            // Cargar datos desde el Data Mart analítico
            $metricasDw = $this->etlService->obtenerMetricas();
            $proyectosDw = DB::connection('dw_lector')->table('v_kpi_proyecto')->get();

            $kpi = $metricasDw['kpi_cartera'];

            $datos = [
                'contexto' => [
                    'modo' => 'VISTA_DATA_MART',
                    'fecha_corte' => $estadoDw['ultima_carga'] ? substr($estadoDw['ultima_carga'], 0, 10) : 'N/A',
                    'rango_fechas_texto' => 'Corte analítico del Data Mart (último reporte por proyecto)',
                    'origen' => 'DW',
                    'revision_datos' => $estadoDw['revision_dw'],
                    'filtros' => $filtros,
                ],
                'resumen' => [
                    'total_proyectos' => (int) ($kpi->total_proyectos ?? 0),
                    'conteo_estados' => [
                        'Pendiente' => 0,
                        'En curso' => (int) ($metricasDw['proyectos_por_estado']->firstWhere('estado_actual', 'En curso')->cantidad_proyectos ?? 0),
                        'Cerrado' => (int) ($metricasDw['proyectos_por_estado']->firstWhere('estado_actual', 'Cerrado')->cantidad_proyectos ?? 0),
                    ],
                    'monto_total_cartera' => (float) ($kpi->monto_total_cartera ?? 0),
                    'avance_hitos' => [
                        'valor' => (float) ($kpi->avance_hitos_pct ?? 0),
                        'valor_formateado' => number_format((float) ($kpi->avance_hitos_pct ?? 0), 2) . ' %',
                        'numerador' => (int) ($kpi->total_hitos_concluidos_lb ?? 0),
                        'denominador' => (int) ($kpi->total_hitos_lb ?? 0),
                        'unidad' => '%',
                        'estado' => 'OK',
                        'cobertura_proyectos' => ($kpi->total_proyectos ?? 0) . ' proyectos',
                        'explicacion' => 'Avance agregado en Data Warehouse (fact_hito)',
                    ],
                    'proyectos_retraso' => [
                        'valor' => (float) ($kpi->porcentaje_retraso ?? 0),
                        'valor_formateado' => number_format((float) ($kpi->porcentaje_retraso ?? 0), 2) . ' %',
                        'numerador' => (int) ($kpi->proyectos_con_retraso ?? 0),
                        'denominador' => (int) ($kpi->proyectos_activos_evaluables ?? 0),
                        'unidad' => '%',
                        'estado' => 'OK',
                        'cobertura_proyectos' => ($kpi->proyectos_activos_evaluables ?? 0) . ' activos evaluables',
                        'explicacion' => 'Proyectos con retraso según fact_avance_proyecto',
                    ],
                    'desviacion_presupuestal' => [
                        'valor' => (float) ($kpi->desviacion_presupuestal_pct ?? 0),
                        'valor_formateado' => number_format((float) ($kpi->desviacion_presupuestal_pct ?? 0), 2) . ' %',
                        'presupuesto_planificado' => (float) ($kpi->total_planificado ?? 0),
                        'gasto_acumulado' => (float) ($kpi->total_gasto ?? 0),
                        'diferencia' => (float) ($kpi->diferencia_total ?? 0),
                        'unidad' => '%',
                        'estado' => 'OK',
                        'cobertura_proyectos' => ($kpi->total_proyectos ?? 0) . ' proyectos',
                        'explicacion' => 'Desviación acumulada en Data Mart',
                    ],
                ],
                'proyectos' => $proyectosDw->map(function ($p) {
                    return [
                        'id_proyecto' => $p->id_proyecto,
                        'codigo_pgd' => $p->codigo_pgd,
                        'nombre' => $p->proyecto,
                        'monto' => (float) $p->monto_proyecto,
                        'estado' => $p->estado_actual,
                        'fuente' => 'Cartera PGD',
                        'objetivo' => ['codigo' => $p->objetivo_codigo, 'descripcion' => $p->objetivo_descripcion],
                        'responsable' => ['nombre' => $p->responsable],
                        'fecha_evaluacion' => $p->ultimo_corte,
                        'hitos' => [
                            'evaluable' => $p->hitos_linea_base > 0,
                            'total_lb' => $p->hitos_linea_base,
                            'concluidos_lb' => $p->hitos_concluidos,
                            'pendientes_lb' => $p->hitos_linea_base - $p->hitos_concluidos,
                            'vencidos_lb' => $p->hitos_vencidos,
                            'avance_pct' => (float) $p->avance_pct,
                            'avance_formateado' => number_format((float) $p->avance_pct, 2) . ' %',
                            'con_retraso' => (bool) $p->con_retraso,
                            'hitos_fuera_lb_vencidos' => 0,
                        ],
                        'financiero' => [
                            'tiene_corte' => true,
                            'fecha_reporte' => $p->ultimo_corte,
                            'presupuesto_planificado' => (float) $p->presupuesto_planificado,
                            'gasto_acumulado' => (float) $p->gasto_acumulado,
                            'diferencia' => (float) $p->diferencia_gasto,
                            'desviacion_pct' => (float) $p->desviacion_pct,
                            'desviacion_formateada' => number_format((float) $p->desviacion_pct, 2) . ' %',
                            'observacion' => null,
                        ],
                    ];
                })->toArray(),
            ];
            $alertas = $metricasDw['alertas'];
            $evolucionCortes = $metricasDw['evolucion_por_corte'];
        } else {
            // Cargar datos en vivo desde el motor operativo de la Etapa 10
            $datos = $this->indicadoresService->calcularCartera($filtros);

            // Obtener alertas de calidad operativa
            $alertas = DB::table('hito as h')
                ->join('proyecto as p', 'h.id_proyecto', '=', 'p.id_proyecto')
                ->join('responsable as r', 'p.id_responsable', '=', 'r.id_responsable')
                ->where('h.en_linea_base', 1)
                ->where('h.fecha_prevista', '<', $fechaCorte)
                ->where(function ($q) use ($fechaCorte) {
                    $q->whereNull('h.fecha_cierre')->orWhere('h.fecha_cierre', '>', $fechaCorte);
                })
                ->select(
                    DB::raw("'Hito vencido' as tipo_alerta"),
                    DB::raw("'ALTA' as severidad"),
                    DB::raw("CONCAT('Hito \"', h.descripcion, '\" venció el ', h.fecha_prevista, ' y sigue sin cierre') as detalle"),
                    'p.codigo_pgd',
                    'p.nombre as proyecto',
                    'r.nombre as responsable'
                )
                ->get();

            // Serie temporal para gráfico de evolución (cortes históricos en orden cronológico)
            $evolucionCortes = DB::table('avance_presupuestal')
                ->select(
                    'fecha_reporte',
                    DB::raw('COUNT(DISTINCT id_proyecto) as proyectos_reportados'),
                    DB::raw('SUM(presupuesto_planificado) as total_planificado'),
                    DB::raw('SUM(gasto_acumulado) as total_gasto'),
                    DB::raw('SUM(gasto_acumulado - presupuesto_planificado) as diferencia')
                )
                ->groupBy('fecha_reporte')
                ->orderBy('fecha_reporte', 'asc')
                ->get();
        }

        return view('tablero', [
            'usuario' => Auth::user(),
            'datos' => $datos,
            'filtros' => $filtros,
            'origen' => $origen,
            'fechasCorteDisponibles' => $fechasCorteDisponibles,
            'fechaCorteSeleccionada' => $fechaCorte,
            'objetivos' => ObjetivoGd::orderBy('codigo')->get(),
            'responsables' => Responsable::orderBy('nombre')->get(),
            'estadoDw' => $estadoDw,
            'alertas' => $alertas,
            'evolucionCortes' => $evolucionCortes,
        ]);
    }
}
