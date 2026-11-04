<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;

class EtlService
{
    /**
     * Ejecuta el procedimiento almacenado sp_etl_cargar usando la conexión técnica sig_etl.
     *
     * @return array Resumen de la carga realizada
     */
    public function ejecutar(): array
    {
        try {
            DB::connection('dw_etl')->statement('CALL sp_etl_cargar();');

            $ultimo = DB::connection('dw_etl')
                ->table('etl_control')
                ->orderByDesc('id_carga')
                ->first();

            return [
                'exito' => $ultimo && $ultimo->estado === 'Completado',
                'id_carga' => $ultimo->id_carga ?? null,
                'fecha_inicio' => $ultimo->fecha_inicio ?? null,
                'fecha_fin' => $ultimo->fecha_fin ?? null,
                'estado' => $ultimo->estado ?? 'Desconocido',
                'filas_avance' => $ultimo->filas_avance ?? 0,
                'filas_hito' => $ultimo->filas_hito ?? 0,
                'revision_origen' => $ultimo->revision_origen ?? 1,
                'mensaje' => $ultimo->mensaje ?? null,
            ];
        } catch (Exception $e) {
            return [
                'exito' => false,
                'estado' => 'Fallido',
                'mensaje' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene el último registro de ejecución del proceso ETL.
     */
    public function obtenerUltimoEstado(): ?object
    {
        return DB::connection('dw_lector')
            ->table('etl_control')
            ->orderByDesc('id_carga')
            ->first();
    }

    /**
     * Comprueba si el Data Warehouse está desactualizado respecto a la base operativa.
     */
    public function estaDesactualizado(): array
    {
        $versionOperativa = DB::connection('lector')
            ->table('control_revision')
            ->where('id', 1)
            ->value('version') ?? 1;

        $ultimoEtl = $this->obtenerUltimoEstado();
        $revisionEtl = $ultimoEtl ? $ultimoEtl->revision_origen : 0;
        $desactualizado = ($ultimoEtl === null || $ultimoEtl->estado !== 'Completado' || $versionOperativa > $revisionEtl);

        return [
            'desactualizado' => $desactualizado,
            'version_operativa' => (int) $versionOperativa,
            'revision_dw' => (int) $revisionEtl,
            'ultima_carga' => $ultimoEtl ? $ultimoEtl->fecha_fin : null,
            'estado_ultima_carga' => $ultimoEtl ? $ultimoEtl->estado : 'Sin cargas registradas',
        ];
    }

    /**
     * Consulta las vistas analíticas del Data Mart a través de bi_lector.
     */
    public function obtenerMetricas(): array
    {
        $kpiCartera = DB::connection('dw_lector')->table('v_kpi_cartera')->first();
        $proyectosPorEstado = DB::connection('dw_lector')->table('v_proyectos_por_estado')->get();
        $evolucion = DB::connection('dw_lector')->table('v_evolucion_por_corte')->get();
        $alertas = DB::connection('dw_lector')->table('v_alertas')->get();
        $calidad = DB::connection('dw_lector')->table('v_calidad_datos')->get();

        return [
            'kpi_cartera' => $kpiCartera,
            'proyectos_por_estado' => $proyectosPorEstado,
            'evolucion_por_corte' => $evolucion,
            'alertas' => $alertas,
            'calidad_datos' => $calidad,
        ];
    }

    /**
     * Consulta el Data Mart respetando filtros avanzados, modalidad temporal y paridad con el oráculo (A06).
     */
    public function consultarDw(array $filtros = []): array
    {
        $estadoDw = $this->estaDesactualizado();
        $modo = $filtros['modo'] ?? IndicadoresService::MODO_CORTE_COMUN;
        $fechaCorte = $filtros['fecha_corte'] ?? null;

        // Construir consulta analítica sobre hechos y dimensiones
        $query = DB::connection('dw_lector')->table('fact_avance_proyecto as fa')
            ->join('dim_proyecto as dp', 'fa.sk_proyecto', '=', 'dp.sk_proyecto')
            ->join('dim_objetivo as dob', 'fa.sk_objetivo', '=', 'dob.sk_objetivo')
            ->join('dim_responsable as dr', 'fa.sk_responsable', '=', 'dr.sk_responsable')
            ->select([
                'dp.id_proyecto',
                'dp.codigo_pgd',
                'dp.nombre as proyecto',
                'dp.estado_actual',
                'dp.es_activo',
                'dp.monto as monto_proyecto',
                'dr.id_responsable',
                'dr.nombre as responsable',
                'dob.id_objetivo',
                'dob.codigo as objetivo_codigo',
                'dob.descripcion as objetivo_descripcion',
                'fa.fecha_reporte',
                'fa.presupuesto_planificado',
                'fa.gasto_acumulado',
                'fa.diferencia_gasto',
                'fa.hitos_linea_base',
                'fa.hitos_concluidos',
                'fa.hitos_vencidos',
                'fa.con_retraso',
                'fa.es_ultimo_corte',
            ]);

        // Modalidad temporal
        if ($modo === IndicadoresService::MODO_CORTE_COMUN && !empty($fechaCorte)) {
            $query->where('fa.fecha_reporte', $fechaCorte);
        } else {
            if (!empty($fechaCorte)) {
                $query->where('fa.fecha_reporte', '<=', $fechaCorte);
            }
            $query->where('fa.es_ultimo_corte', 1);
        }

        // Filtro por Estado
        if (!empty($filtros['estado'])) {
            $query->where('dp.estado_actual', $filtros['estado']);
        }

        // Filtro por Responsable
        if (!empty($filtros['id_responsable'])) {
            $query->where('dr.id_responsable', $filtros['id_responsable']);
        }

        // Filtro por Objetivo
        if (!empty($filtros['id_objetivo'])) {
            $query->where('dob.id_objetivo', $filtros['id_objetivo']);
        }

        // Filtro por Búsqueda
        $busqueda = $filtros['q'] ?? $filtros['buscar'] ?? null;
        if (!empty($busqueda)) {
            $term = '%' . trim($busqueda) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('dp.codigo_pgd', 'like', $term)
                  ->orWhere('dp.nombre', 'like', $term);
            });
        }

        $proyectosDw = $query->orderBy('dp.codigo_pgd')->get();

        // Acumuladores de Cartera
        $totalProyectos = $proyectosDw->count();
        $montoTotalCartera = 0.0;
        $conteosEstados = ['Pendiente' => 0, 'En curso' => 0, 'Cerrado' => 0];

        $totalHitosLb = 0;
        $concluidosLb = 0;
        $proyectosConLb = 0;

        $activosEvaluables = 0;
        $conRetrasoCount = 0;

        $sumaPlan = 0.0;
        $sumaGasto = 0.0;
        $proyectosConCorte = 0;
        $fechasEfectivas = [];

        $listaProyectos = [];

        foreach ($proyectosDw as $p) {
            $montoTotalCartera += (float) $p->monto_proyecto;
            if (isset($conteosEstados[$p->estado_actual])) {
                $conteosEstados[$p->estado_actual]++;
            }

            if ($p->fecha_reporte) {
                $fechasEfectivas[] = $p->fecha_reporte;
            }

            $tieneLb = ((int) $p->hitos_linea_base > 0);
            if ($tieneLb) {
                $proyectosConLb++;
                $totalHitosLb += (int) $p->hitos_linea_base;
                $concluidosLb += (int) $p->hitos_concluidos;
            }

            $esActivo = in_array($p->estado_actual, ['Pendiente', 'En curso'], true);
            if ($esActivo && $tieneLb && $p->fecha_reporte) {
                $activosEvaluables++;
                if ((int) $p->con_retraso === 1) {
                    $conRetrasoCount++;
                }
            }

            $tieneCorteFinanciero = !is_null($p->presupuesto_planificado);
            $desvPct = null;
            if ($tieneCorteFinanciero) {
                $proyectosConCorte++;
                $plan = (float) $p->presupuesto_planificado;
                $gasto = (float) $p->gasto_acumulado;
                $sumaPlan += $plan;
                $sumaGasto += $gasto;
                if ($plan > 0) {
                    $desvPct = round((($gasto - $plan) / $plan) * 100, 2);
                }
            }

            $avancePct = $tieneLb ? round(((int) $p->hitos_concluidos / (int) $p->hitos_linea_base) * 100, 2) : null;

            $listaProyectos[] = [
                'id_proyecto' => $p->id_proyecto,
                'codigo_pgd' => $p->codigo_pgd,
                'nombre' => $p->proyecto,
                'monto' => (float) $p->monto_proyecto,
                'estado' => $p->estado_actual,
                'fuente' => 'Cartera PGD',
                'objetivo' => ['id_objetivo' => $p->id_objetivo, 'codigo' => $p->objetivo_codigo, 'descripcion' => $p->objetivo_descripcion],
                'responsable' => ['id_responsable' => $p->id_responsable, 'nombre' => $p->responsable],
                'fecha_evaluacion' => $p->fecha_reporte,
                'hitos' => [
                    'evaluable' => $tieneLb,
                    'total_lb' => (int) $p->hitos_linea_base,
                    'concluidos_lb' => (int) $p->hitos_concluidos,
                    'pendientes_lb' => (int) $p->hitos_linea_base - (int) $p->hitos_concluidos,
                    'vencidos_lb' => (int) $p->hitos_vencidos,
                    'avance_pct' => $avancePct,
                    'avance_formateado' => $tieneLb ? number_format($avancePct, 2) . ' %' : 'Sin línea base',
                    'con_retraso' => (bool) $p->con_retraso,
                    'hitos_fuera_lb_vencidos' => 0,
                ],
                'financiero' => [
                    'tiene_corte' => $tieneCorteFinanciero,
                    'fecha_reporte' => $p->fecha_reporte,
                    'presupuesto_planificado' => $tieneCorteFinanciero ? (float) $p->presupuesto_planificado : null,
                    'gasto_acumulado' => $tieneCorteFinanciero ? (float) $p->gasto_acumulado : null,
                    'diferencia' => $tieneCorteFinanciero ? (float) $p->diferencia_gasto : null,
                    'desviacion_pct' => $desvPct,
                    'desviacion_formateada' => $tieneCorteFinanciero
                        ? ((float) $p->presupuesto_planificado > 0
                            ? ($desvPct >= 0 ? '+' : '') . number_format($desvPct, 2) . ' %'
                            : 'No aplica')
                        : 'Sin datos',
                    'observacion' => null,
                ],
            ];
        }

        // Consolidación de KPIs (A11: Distinción entre Sin datos y No aplica)
        $kpiAvanceValor = null;
        $kpiAvanceFormateado = 'Sin datos';
        $kpiAvanceEstado = 'SIN_DATOS';
        if ($totalHitosLb > 0) {
            $kpiAvanceValor = round(($concluidosLb / $totalHitosLb) * 100, 2);
            $kpiAvanceFormateado = number_format($kpiAvanceValor, 2) . ' %';
            $kpiAvanceEstado = 'OK';
        }

        $kpiRetrasoValor = null;
        $kpiRetrasoFormateado = 'Sin datos';
        $kpiRetrasoEstado = 'SIN_DATOS';
        if ($activosEvaluables > 0) {
            $kpiRetrasoValor = round(($conRetrasoCount / $activosEvaluables) * 100, 2);
            $kpiRetrasoFormateado = number_format($kpiRetrasoValor, 2) . ' %';
            $kpiRetrasoEstado = 'OK';
        }

        $kpiDesvValor = null;
        $kpiDesvFormateado = 'Sin datos';
        $kpiDesvEstado = 'SIN_DATOS';
        if ($proyectosConCorte > 0) {
            if ($sumaPlan > 0) {
                $kpiDesvValor = round((($sumaGasto - $sumaPlan) / $sumaPlan) * 100, 2);
                $kpiDesvFormateado = ($kpiDesvValor >= 0 ? '+' : '') . number_format($kpiDesvValor, 2) . ' %';
                $kpiDesvEstado = 'OK';
            } else {
                $kpiDesvFormateado = 'No aplica';
                $kpiDesvEstado = 'NO_APLICA';
            }
        }

        $fechaMin = !empty($fechasEfectivas) ? min($fechasEfectivas) : null;
        $fechaMax = !empty($fechasEfectivas) ? max($fechasEfectivas) : null;

        return [
            'contexto' => [
                'modo' => $modo,
                'fecha_corte' => $fechaCorte ?? $fechaMax,
                'fecha_minima_reporte' => $fechaMin,
                'fecha_maxima_reporte' => $fechaMax,
                'rango_fechas_texto' => ($modo === IndicadoresService::MODO_ULTIMO_REPORTE && $fechaMin && $fechaMax)
                    ? "Reportes efectivos DW entre {$fechaMin} y {$fechaMax}"
                    : "Fecha corte DW: " . ($fechaCorte ?? $fechaMax ?? 'N/A'),
                'origen' => 'DW',
                'revision_datos' => $estadoDw['revision_dw'],
                'filtros' => $filtros,
            ],
            'resumen' => [
                'total_proyectos' => $totalProyectos,
                'conteo_estados' => $conteosEstados,
                'monto_total_cartera' => $montoTotalCartera,
                'avance_hitos' => [
                    'valor' => $kpiAvanceValor,
                    'valor_formateado' => $kpiAvanceFormateado,
                    'numerador' => $concluidosLb,
                    'denominador' => $totalHitosLb,
                    'unidad' => '%',
                    'estado' => $kpiAvanceEstado,
                    'cobertura_proyectos' => "{$proyectosConLb} de {$totalProyectos}",
                    'explicacion' => 'Avance agregado en Data Warehouse (fact_avance_proyecto)',
                ],
                'proyectos_retraso' => [
                    'valor' => $kpiRetrasoValor,
                    'valor_formateado' => $kpiRetrasoFormateado,
                    'numerador' => $conRetrasoCount,
                    'denominador' => $activosEvaluables,
                    'unidad' => '%',
                    'estado' => $kpiRetrasoEstado,
                    'cobertura_proyectos' => "{$activosEvaluables} activos evaluables",
                    'explicacion' => 'Proyectos con retraso según fact_avance_proyecto',
                ],
                'desviacion_presupuestal' => [
                    'valor' => $kpiDesvValor,
                    'valor_formateado' => $kpiDesvFormateado,
                    'presupuesto_planificado' => $sumaPlan,
                    'gasto_acumulado' => $sumaGasto,
                    'diferencia' => $sumaGasto - $sumaPlan,
                    'unidad' => '%',
                    'estado' => $kpiDesvEstado,
                    'cobertura_proyectos' => "{$proyectosConCorte} de {$totalProyectos}",
                    'explicacion' => 'Desviación acumulada en Data Mart',
                ],
            ],
            'proyectos' => $listaProyectos,
        ];
    }
}
