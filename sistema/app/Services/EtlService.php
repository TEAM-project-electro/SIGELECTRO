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
}
