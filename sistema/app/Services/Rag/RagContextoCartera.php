<?php

namespace App\Services\Rag;

use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\IndicadoresService;
use Illuminate\Support\Facades\Gate;

/**
 * Datos vivos de cartera para el RAG: consultas predefinidas de IndicadoresService sobre la
 * conexión de solo lectura. El modelo nunca escribe SQL; cada proyecto pasa por ProyectoPolicy::view.
 */
class RagContextoCartera
{
    public function __construct(private IndicadoresService $indicadores)
    {
    }

    /** @return array<int,array{origen:string,titulo:string,seccion:string,texto:string}> */
    public function fragmentos(Responsable $usuario): array
    {
        $datos = $this->indicadores->calcularCartera(['modo' => IndicadoresService::MODO_ULTIMO_REPORTE]);
        $r = $datos['resumen'];
        $fragmentos = [[
            'origen' => 'cartera',
            'titulo' => 'Datos vivos de la cartera PGD',
            'seccion' => 'Resumen de indicadores',
            'texto' => sprintf(
                "Cartera PGD de Electrocentro (modo último reporte por proyecto, revisión de datos v%d). Total de proyectos: %d. Monto total de cartera: S/ %s. Estados: %s. Indicador 1 avance de hitos de línea base: %s. Indicador 2 proyectos con retraso: %s. Indicador 3 desviación presupuestal: %s.",
                $datos['contexto']['revision_datos'], $r['total_proyectos'], number_format((float) $r['monto_total_cartera'], 2),
                collect($r['conteo_estados'])->map(fn ($n, $e) => "{$e} {$n}")->implode(', '),
                $r['avance_hitos']['valor_formateado'] ?? 'Sin datos',
                $r['proyectos_retraso']['valor_formateado'] ?? 'Sin datos',
                $r['desviacion_presupuestal']['valor_formateado'] ?? 'Sin datos'
            ),
        ]];

        foreach ($datos['proyectos'] as $p) {
            $modelo = new Proyecto();
            $modelo->id_proyecto = $p['id_proyecto'];
            $modelo->id_responsable = $p['responsable']['id_responsable'];
            if (!Gate::forUser($usuario)->allows('view', $modelo)) {
                continue;
            }
            $f = $p['financiero'];
            $fragmentos[] = [
                'origen' => 'cartera',
                'titulo' => 'Datos vivos de la cartera PGD',
                'seccion' => "Proyecto {$p['codigo_pgd']}",
                'texto' => sprintf(
                    "Proyecto %s: %s. Estado: %s. Objetivo %s. Responsable: %s. Monto: S/ %s. Periodo %s-%s. Hitos de línea base concluidos %d de %d (avance %s; vencidos %d; con retraso: %s). Último corte presupuestal: %s; planificado S/ %s; gasto acumulado S/ %s; desviación %s.",
                    $p['codigo_pgd'], $p['nombre'], $p['estado'], $p['objetivo']['codigo'], $p['responsable']['nombre'],
                    number_format($p['monto'], 2), $p['anio_inicio'], $p['anio_fin'],
                    $p['hitos']['concluidos_lb'], $p['hitos']['total_lb'], $p['hitos']['avance_formateado'],
                    $p['hitos']['vencidos_lb'], $p['hitos']['con_retraso'] ? 'sí' : 'no',
                    $f['fecha_reporte'] ?? 'sin corte',
                    $f['presupuesto_planificado'] !== null ? number_format((float) $f['presupuesto_planificado'], 2) : 'sin dato',
                    $f['gasto_acumulado'] !== null ? number_format((float) $f['gasto_acumulado'], 2) : 'sin dato',
                    $f['desviacion_formateada'] ?? 'Sin datos'
                ),
            ];
        }
        return $fragmentos;
    }
}
