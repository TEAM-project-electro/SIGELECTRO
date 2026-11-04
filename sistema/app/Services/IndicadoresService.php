<?php

namespace App\Services;

use App\Models\Proyecto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndicadoresService
{
    public const MODO_CORTE_COMUN = 'CORTE_COMUN';
    public const MODO_ULTIMO_REPORTE = 'ULTIMO_REPORTE_POR_PROYECTO';

    /**
     * Calcula los indicadores de la cartera de proyectos según el contrato canónico.
     *
     * @param array $filtros
     *  - modo: 'CORTE_COMUN' (default) | 'ULTIMO_REPORTE_POR_PROYECTO'
     *  - fecha_corte: string 'YYYY-MM-DD' (Corte común o fecha límite)
     *  - id_objetivo: int|null
     *  - id_responsable: int|null
     *  - estado: string|null ('Pendiente' | 'En curso' | 'Cerrado')
     *  - q: string|null (búsqueda por código o nombre)
     * @return array
     */
    public function calcularCartera(array $filtros = []): array
    {
        return DB::connection('lector')->transaction(function () use ($filtros) {
            $modo = $filtros['modo'] ?? self::MODO_CORTE_COMUN;
            $fechaCorteParam = $filtros['fecha_corte'] ?? null;

            // Si en CORTE_COMUN no se envía fecha, tomar la última fecha de corte global de avance_presupuestal
            if ($modo === self::MODO_CORTE_COMUN && empty($fechaCorteParam)) {
                $fechaCorteParam = DB::connection('lector')->table('avance_presupuestal')->max('fecha_reporte') ?? '2024-06-30';
            }

            // 1. Obtener universo de proyectos filtrado
            $query = DB::connection('lector')->table('proyecto as p')
            ->join('objetivo_gd as o', 'p.id_objetivo', '=', 'o.id_objetivo')
            ->join('responsable as r', 'p.id_responsable', '=', 'r.id_responsable')
            ->select(
                'p.id_proyecto',
                'p.codigo_pgd',
                'p.nombre',
                'p.monto',
                'p.anio_inicio',
                'p.anio_fin',
                'p.estado',
                'p.fuente',
                'o.id_objetivo',
                'o.codigo as objetivo_codigo',
                'o.descripcion as objetivo_descripcion',
                'r.id_responsable',
                'r.nombre as responsable_nombre',
                'r.usuario as responsable_usuario'
            );

        if (!empty($filtros['id_objetivo'])) {
            $query->where('p.id_objetivo', $filtros['id_objetivo']);
        }

        if (!empty($filtros['id_responsable'])) {
            $query->where('p.id_responsable', $filtros['id_responsable']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('p.estado', $filtros['estado']);
        }

        if (!empty($filtros['q'])) {
            $term = '%' . trim($filtros['q']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('p.codigo_pgd', 'like', $term)
                  ->orWhere('p.nombre', 'like', $term);
            });
        }

        $proyectosRaw = $query->orderBy('p.codigo_pgd', 'asc')->get();
        $totalProyectos = $proyectosRaw->count();

        // 2. Pre-cargar hitos y avances de los proyectos para evitar N+1 y consultas cruzadas multiplicadas
        $proyectoIds = $proyectosRaw->pluck('id_proyecto')->toArray();

        $hitosPorProyecto = [];
        if (!empty($proyectoIds)) {
            $hitosDb = DB::connection('lector')->table('hito')
                ->whereIn('id_proyecto', $proyectoIds)
                ->orderBy('fecha_prevista', 'asc')
                ->get();
            foreach ($hitosDb as $h) {
                $hitosPorProyecto[$h->id_proyecto][] = $h;
            }
        }

        $avancesPorProyecto = [];
        if (!empty($proyectoIds)) {
            $avancesDb = DB::connection('lector')->table('avance_presupuestal')
                ->whereIn('id_proyecto', $proyectoIds)
                ->orderBy('fecha_reporte', 'asc')
                ->get();
            foreach ($avancesDb as $a) {
                $avancesPorProyecto[$a->id_proyecto][] = $a;
            }
        }

        $revision = DB::connection('lector')->table('control_revision')->where('id', 1)->value('version') ?? 1;
        return $this->evaluarDatos($proyectosRaw, $hitosPorProyecto, $avancesPorProyecto, $filtros, $fechaCorteParam, (int) $revision);
        });
    }

    /** Calcula sin consultar bases, usando el snapshot completo del origen elegido. */
    public function evaluarDatos($proyectosRaw, array $hitosPorProyecto, array $avancesPorProyecto, array $filtros, ?string $fechaCorteParam, int $revision): array
    {
        $modo = $filtros['modo'] ?? self::MODO_CORTE_COMUN;
        $totalProyectos = $proyectosRaw->count();
        // 3. Procesar proyecto por proyecto según la modalidad temporal
        $proyectosCalculados = [];
        $fechasEfectivas = [];

        // Acumuladores de Cartera
        $conteosEstados = [
            'Pendiente' => 0,
            'En curso' => 0,
            'Cerrado' => 0,
        ];

        // KPI 1: Avance Hitos
        $totalHitosLbCartera = 0;
        $concluidosHitosLbCartera = 0;
        $proyectosConLbCount = 0;

        // KPI 2: Proyectos con Retraso
        $proyectosActivosEvaluablesCount = 0;
        $proyectosActivosRetrasadosCount = 0;

        // KPI 3: Desviación Presupuestal
        $sumaPlanFinanciero = 0.00;
        $sumaGastoFinanciero = 0.00;
        $proyectosConCorteFinancieroCount = 0;

        $montoTotalCartera = 0.00;

        foreach ($proyectosRaw as $p) {
            $montoTotalCartera += (float) $p->monto;
            if (isset($conteosEstados[$p->estado])) {
                $conteosEstados[$p->estado]++;
            }

            $idp = $p->id_proyecto;
            $hitos = $hitosPorProyecto[$idp] ?? [];
            $avances = $avancesPorProyecto[$idp] ?? [];

            // Determinar fecha de evaluación y corte financiero
            $fechaEval = null;
            $corteFinanciero = null;
            $tieneCorteFinanciero = false;

            if ($modo === self::MODO_CORTE_COMUN) {
                $fechaEval = $fechaCorteParam;
                // Buscar avance exacto en fecha_reporte == $fechaCorteParam
                foreach ($avances as $av) {
                    if ($av->fecha_reporte === $fechaCorteParam) {
                        $corteFinanciero = $av;
                        $tieneCorteFinanciero = true;
                        break;
                    }
                }
            } else {
                // MODO_ULTIMO_REPORTE_POR_PROYECTO: MAX(fecha_reporte) <= fecha_limite
                $avancesValidos = array_filter($avances, function ($av) use ($fechaCorteParam) {
                    return empty($fechaCorteParam) || $av->fecha_reporte <= $fechaCorteParam;
                });
                if (!empty($avancesValidos)) {
                    // Como están ordenados por fecha asc, el último es el máximo
                    $corteFinanciero = end($avancesValidos);
                    $fechaEval = $corteFinanciero->fecha_reporte;
                    $tieneCorteFinanciero = true;
                }
            }

            if ($fechaEval) {
                $fechasEfectivas[] = $fechaEval;
            }

            // Evaluar Hitos
            $totalHitosLb = 0;
            $concluidosHitosLb = 0;
            $pendientesHitosLb = 0;
            $vencidosHitosLb = 0;
            $hitosFueraLbVencidos = 0;

            if ($fechaEval) {
                foreach ($hitos as $h) {
                    $esLb = (int) $h->en_linea_base === 1;
                    $concluido = !empty($h->fecha_cierre) && $h->fecha_cierre <= $fechaEval;
                    // Vencido: fecha_prevista < fechaEval AND (sin cierre o cierre posterior)
                    $vencido = ($h->fecha_prevista < $fechaEval) && (empty($h->fecha_cierre) || $h->fecha_cierre > $fechaEval);

                    if ($esLb) {
                        $totalHitosLb++;
                        if ($concluido) {
                            $concluidosHitosLb++;
                        } else {
                            $pendientesHitosLb++;
                            if ($vencido) {
                                $vencidosHitosLb++;
                            }
                        }
                    } else {
                        // Fuera de LB
                        if ($vencido) {
                            $hitosFueraLbVencidos++;
                        }
                    }
                }
            }

            // Proyecto evaluable para Avance de Hitos
            $tieneHitosLb = ($totalHitosLb > 0);
            if ($tieneHitosLb) {
                $proyectosConLbCount++;
                $totalHitosLbCartera += $totalHitosLb;
                $concluidosHitosLbCartera += $concluidosHitosLb;
            }

            $avanceProyectoPct = $tieneHitosLb ? round(($concluidosHitosLb / $totalHitosLb) * 100, 4) : null;

            // Proyecto activo y evaluable para Retraso
            $esActivo = in_array($p->estado, ['Pendiente', 'En curso'], true);
            $esEvaluableRetraso = $esActivo && $tieneHitosLb && !empty($fechaEval);
            $conRetraso = false;

            if ($esEvaluableRetraso) {
                $proyectosActivosEvaluablesCount++;
                if ($vencidosHitosLb > 0) {
                    $conRetraso = true;
                    $proyectosActivosRetrasadosCount++;
                }
            }

            // Datos Financieros del Proyecto
            $planPresupuesto = null;
            $gastoAcumulado = null;
            $diferenciaFinanciera = null;
            $desviacionProyectoPct = null;
            $observacionFinanciera = null;

            if ($tieneCorteFinanciero && $corteFinanciero) {
                $proyectosConCorteFinancieroCount++;
                $planPresupuesto = (float) $corteFinanciero->presupuesto_planificado;
                $gastoAcumulado = (float) $corteFinanciero->gasto_acumulado;
                $diferenciaFinanciera = $gastoAcumulado - $planPresupuesto;

                $sumaPlanFinanciero += $planPresupuesto;
                $sumaGastoFinanciero += $gastoAcumulado;

                if ($planPresupuesto > 0) {
                    $desviacionProyectoPct = round((($gastoAcumulado - $planPresupuesto) / $planPresupuesto) * 100, 4);
                }
            } else {
                $observacionFinanciera = ($modo === self::MODO_CORTE_COMUN)
                    ? 'Sin corte financiero exacto a la fecha común'
                    : 'Sin corte presupuestal disponible';
            }

            $proyectosCalculados[] = [
                'id_proyecto' => $p->id_proyecto,
                'codigo_pgd' => $p->codigo_pgd,
                'nombre' => $p->nombre,
                'monto' => (float) $p->monto,
                'anio_inicio' => $p->anio_inicio,
                'anio_fin' => $p->anio_fin,
                'estado' => $p->estado,
                'fuente' => $p->fuente,
                'objetivo' => [
                    'id_objetivo' => $p->id_objetivo,
                    'codigo' => $p->objetivo_codigo,
                    'descripcion' => $p->objetivo_descripcion,
                ],
                'responsable' => [
                    'id_responsable' => $p->id_responsable,
                    'nombre' => $p->responsable_nombre,
                    'usuario' => ($p->responsable_usuario ?? null),
                ],
                'fecha_evaluacion' => $fechaEval,
                'hitos' => [
                    'evaluable' => $tieneHitosLb,
                    'total_lb' => $totalHitosLb,
                    'concluidos_lb' => $concluidosHitosLb,
                    'pendientes_lb' => $pendientesHitosLb,
                    'vencidos_lb' => $vencidosHitosLb,
                    'avance_pct' => $avanceProyectoPct,
                    'avance_formateado' => $tieneHitosLb ? number_format($avanceProyectoPct, 2, '.', '') . ' %' : 'Sin línea base',
                    'con_retraso' => $conRetraso,
                    'hitos_fuera_lb_vencidos' => $hitosFueraLbVencidos,
                ],
                'financiero' => [
                    'tiene_corte' => $tieneCorteFinanciero,
                    'fecha_reporte' => $corteFinanciero ? $corteFinanciero->fecha_reporte : null,
                    'presupuesto_planificado' => $planPresupuesto,
                    'gasto_acumulado' => $gastoAcumulado,
                    'diferencia' => $diferenciaFinanciera,
                    'desviacion_pct' => $desviacionProyectoPct,
                    'desviacion_formateada' => $planPresupuesto !== null
                        ? ($planPresupuesto > 0
                            ? ($desviacionProyectoPct >= 0 ? '+' : '') . number_format($desviacionProyectoPct, 2, '.', '') . ' %'
                            : 'No aplica')
                        : 'Sin datos',
                    'observacion' => $observacionFinanciera,
                ],
            ];
        }

        // 4. Consolidar KPIs de Cartera (A11: Distinción entre Sin datos y No aplica)
        // KPI 1: Avance de Hitos
        $kpiAvanceValor = null;
        $kpiAvanceFormateado = 'Sin datos';
        $kpiAvanceEstado = 'SIN_DATOS';

        if ($totalHitosLbCartera > 0) {
            $kpiAvanceValor = ($concluidosHitosLbCartera / $totalHitosLbCartera) * 100;
            $kpiAvanceFormateado = number_format($kpiAvanceValor, 2, '.', '') . ' %';
            $kpiAvanceEstado = 'OK';
        }

        // KPI 2: Retraso
        $kpiRetrasoValor = null;
        $kpiRetrasoFormateado = 'Sin datos';
        $kpiRetrasoEstado = 'SIN_DATOS';

        if ($proyectosActivosEvaluablesCount > 0) {
            $kpiRetrasoValor = ($proyectosActivosRetrasadosCount / $proyectosActivosEvaluablesCount) * 100;
            $kpiRetrasoFormateado = number_format($kpiRetrasoValor, 2, '.', '') . ' %';
            $kpiRetrasoEstado = 'OK';
        }

        // KPI 3: Desviación Presupuestal
        $kpiDesviacionValor = null;
        $kpiDesviacionFormateado = 'Sin datos';
        $kpiDesviacionEstado = 'SIN_DATOS';

        if ($proyectosConCorteFinancieroCount > 0) {
            if ($sumaPlanFinanciero > 0) {
                $kpiDesviacionValor = (($sumaGastoFinanciero - $sumaPlanFinanciero) / $sumaPlanFinanciero) * 100;
                $signo = $kpiDesviacionValor >= 0 ? '+' : '';
                $kpiDesviacionFormateado = $signo . number_format($kpiDesviacionValor, 2, '.', '') . ' %';
                $kpiDesviacionEstado = 'OK';
            } else {
                $kpiDesviacionFormateado = 'No aplica';
                $kpiDesviacionEstado = 'NO_APLICA';
            }
        }

        // Revisión de datos técnica bajo el mismo snapshot de lectura


        $fechaMinReporte = !empty($fechasEfectivas) ? min($fechasEfectivas) : null;
        $fechaMaxReporte = !empty($fechasEfectivas) ? max($fechasEfectivas) : null;

        return [
            'contexto' => [
                'modo' => $modo,
                'fecha_corte' => $fechaCorteParam,
                'fecha_minima_reporte' => $fechaMinReporte,
                'fecha_maxima_reporte' => $fechaMaxReporte,
                'rango_fechas_texto' => ($modo === self::MODO_ULTIMO_REPORTE && $fechaMinReporte && $fechaMaxReporte)
                    ? "Reportes efectivos entre {$fechaMinReporte} y {$fechaMaxReporte}"
                    : "Fecha común: {$fechaCorteParam}",
                'origen' => 'OPERATIVO',
                'revision_datos' => (int) $revision,
                'filtros' => [
                    'id_objetivo' => $filtros['id_objetivo'] ?? null,
                    'id_responsable' => $filtros['id_responsable'] ?? null,
                    'estado' => $filtros['estado'] ?? null,
                    'q' => $filtros['q'] ?? null,
                ],
            ],
            'resumen' => [
                'total_proyectos' => $totalProyectos,
                'conteo_estados' => $conteosEstados,
                'monto_total_cartera' => $montoTotalCartera,
                'avance_hitos' => [
                    'valor' => $kpiAvanceValor !== null ? round($kpiAvanceValor, 4) : null,
                    'valor_formateado' => $kpiAvanceFormateado,
                    'numerador' => $concluidosHitosLbCartera,
                    'denominador' => $totalHitosLbCartera,
                    'unidad' => '%',
                    'estado' => $kpiAvanceEstado,
                    'cobertura_proyectos' => "{$proyectosConLbCount} de {$totalProyectos}",
                    'explicacion' => 'Suma de hitos de línea base concluidos al corte sobre el total de hitos de línea base.',
                ],
                'proyectos_retraso' => [
                    'valor' => $kpiRetrasoValor !== null ? round($kpiRetrasoValor, 4) : null,
                    'valor_formateado' => $kpiRetrasoFormateado,
                    'numerador' => $proyectosActivosRetrasadosCount,
                    'denominador' => $proyectosActivosEvaluablesCount,
                    'unidad' => '%',
                    'estado' => $kpiRetrasoEstado,
                    'cobertura_proyectos' => "{$proyectosActivosEvaluablesCount} evaluables de " . ($conteosEstados['Pendiente'] + $conteosEstados['En curso']) . ' activos',
                    'explicacion' => 'Proyectos activos (Pendiente / En curso) evaluables con al menos un hito de línea base vencido.',
                ],
                'desviacion_presupuestal' => [
                    'valor' => $kpiDesviacionValor !== null ? round($kpiDesviacionValor, 4) : null,
                    'valor_formateado' => $kpiDesviacionFormateado,
                    'presupuesto_planificado' => $sumaPlanFinanciero,
                    'gasto_acumulado' => $sumaGastoFinanciero,
                    'diferencia' => $sumaGastoFinanciero - $sumaPlanFinanciero,
                    'unidad' => '%',
                    'estado' => $kpiDesviacionEstado,
                    'cobertura_proyectos' => "{$proyectosConCorteFinancieroCount} de {$totalProyectos}",
                    'explicacion' => 'Variación porcentual entre gasto acumulado y presupuesto planificado acumulado para cortes elegibles.',
                ],
            ],
            'proyectos' => $proyectosCalculados,
        ];
    }
}
