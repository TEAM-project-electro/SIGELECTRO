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

        // 1. Obtención de datos según el origen elegido (A06: Consulta DW filtrada con paridad)
        if ($origen === 'DW') {
            $datos = $this->etlService->consultarDw($filtros);
            $metricasDw = $this->etlService->obtenerMetricas();
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
