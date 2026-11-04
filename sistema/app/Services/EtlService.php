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
                'mensaje' => $e instanceof \Illuminate\Database\QueryException ? ErroresNegocio::mensaje($e) : 'No se pudo completar la carga ETL.',
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
        $db = DB::connection('dw_lector');
        return $db->transaction(function () use ($db, $filtros) {
            $modo = $filtros['modo'] ?? IndicadoresService::MODO_CORTE_COMUN;
            $fecha = $filtros['fecha_corte'] ?? null;
            if ($modo === IndicadoresService::MODO_CORTE_COMUN && !$fecha) {
                $fecha = $db->table('fact_avance_proyecto')->max('fecha_reporte') ?? '2024-06-30';
            }
            $query = $db->table('dim_proyecto as p')
                ->join('dim_objetivo as o', 'p.id_objetivo', '=', 'o.id_objetivo')
                ->join('dim_responsable as r', 'p.id_responsable', '=', 'r.id_responsable')
                ->select('p.id_proyecto', 'p.codigo_pgd', 'p.nombre', 'p.monto', 'p.anio_inicio', 'p.anio_fin',
                    'p.estado_actual as estado', 'p.fuente', 'o.id_objetivo', 'o.codigo as objetivo_codigo',
                    'o.descripcion as objetivo_descripcion', 'r.id_responsable', 'r.nombre as responsable_nombre');
            foreach (['id_objetivo'=>'o.id_objetivo', 'id_responsable'=>'r.id_responsable', 'estado'=>'p.estado_actual'] as $key=>$column) {
                if (!empty($filtros[$key])) $query->where($column, $filtros[$key]);
            }
            $q = trim($filtros['q'] ?? $filtros['buscar'] ?? '');
            if ($q !== '') $query->where(fn ($query) => $query->where('p.codigo_pgd', 'like', '%'.$q.'%')->orWhere('p.nombre', 'like', '%'.$q.'%'));
            $proyectos = $query->orderBy('p.codigo_pgd')->get();
            $ids = $proyectos->pluck('id_proyecto')->all();
            $hitos = $db->table('fact_hito as h')->join('dim_tiempo as tp', 'h.sk_tiempo_previsto', '=', 'tp.sk_tiempo')
                ->leftJoin('dim_tiempo as tc', 'h.sk_tiempo_cierre', '=', 'tc.sk_tiempo')
                ->whereIn('h.id_proyecto', $ids)
                ->select('h.id_proyecto', 'h.en_linea_base', 'tp.fecha as fecha_prevista', 'tc.fecha as fecha_cierre')->get()
                ->groupBy('id_proyecto')->map(fn ($rows) => $rows->all())->all();
            $avances = $db->table('fact_avance_proyecto')->whereIn('id_proyecto', $ids)->orderBy('fecha_reporte')->get()
                ->groupBy('id_proyecto')->map(fn ($rows) => $rows->all())->all();
            $carga = $db->table('etl_control')->where('estado', 'Completado')->orderByDesc('id_carga')->first();
            $datos = app(IndicadoresService::class)->evaluarDatos($proyectos, $hitos, $avances, $filtros, $fecha, (int) ($carga->revision_origen ?? 0));
            $datos['contexto']['origen'] = 'DW';
            return $datos;
        });
    }
}
