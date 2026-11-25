<?php

namespace App\Services\Gestion;

use Illuminate\Support\Facades\DB;

/**
 * Datos gerenciales de la ficha de un proyecto: presupuesto vigente y comprometido, valor ganado,
 * riesgos, documentos, beneficios e historial de cambios. Todo en solo lectura.
 */
class FichaGerencialService
{
    public function __construct(private ValorGanadoService $valorGanado)
    {
    }

    public function datos(int $idProyecto): array
    {
        $db = DB::connection('lector');
        if (!$db->getSchemaBuilder()->hasTable('riesgo_proyecto')) {
            return ['disponible' => false];
        }
        $parametros = AlertasService::parametros();

        $versiones = $db->table('proyecto_presupuesto_version as v')
            ->join('responsable as r', 'r.id_responsable', '=', 'v.id_responsable')
            ->where('v.id_proyecto', $idProyecto)
            ->select('v.*', 'r.nombre as responsable')
            ->orderBy('v.version')->get();
        $compromisos = $db->table('compromiso_presupuestal as c')
            ->join('responsable as r', 'r.id_responsable', '=', 'c.id_responsable')
            ->where('c.id_proyecto', $idProyecto)
            ->select('c.*', 'r.nombre as responsable')
            ->orderByDesc('c.fecha')->get();
        $vigente = (float) $db->table('proyecto')->where('id_proyecto', $idProyecto)->value('monto');
        $ultimoCorte = $db->table('avance_presupuestal')->where('id_proyecto', $idProyecto)->orderByDesc('fecha_reporte')->first();
        $comprometido = (float) $compromisos->where('estado', 'Vigente')->sum('monto');
        $devengado = $ultimoCorte ? (float) $ultimoCorte->gasto_acumulado : 0.0;

        $riesgos = $db->table('riesgo_proyecto as g')
            ->join('responsable as r', 'r.id_responsable', '=', 'g.id_responsable')
            ->where('g.id_proyecto', $idProyecto)
            ->select('g.*', 'r.nombre as dueno')
            ->orderByRaw("g.estado = 'Cerrado'")->orderByDesc('g.exposicion')->get()
            ->map(function ($g) use ($parametros) {
                $g->nivel = AlertasService::nivelRiesgo((int) $g->exposicion, $parametros);
                return $g;
            });
        $matriz = [];
        foreach ($riesgos->where('estado', '<>', 'Cerrado') as $g) {
            $matriz[$g->probabilidad][$g->impacto] = ($matriz[$g->probabilidad][$g->impacto] ?? 0) + 1;
        }

        $documentos = $db->table('documento_proyecto as d')
            ->join('responsable as r', 'r.id_responsable', '=', 'd.id_responsable')
            ->leftJoin('hito as h', 'h.id_hito', '=', 'd.id_hito')
            ->where('d.id_proyecto', $idProyecto)
            ->select('d.id_documento', 'd.tipo', 'd.titulo', 'd.nombre_original', 'd.tamano', 'd.hash_sha256', 'd.fecha_hora',
                'r.nombre as responsable', 'h.descripcion as hito')
            ->orderByDesc('d.fecha_hora')->get();

        $beneficios = $db->table('beneficio_proyecto as b')
            ->join('responsable as r', 'r.id_responsable', '=', 'b.id_responsable')
            ->where('b.id_proyecto', $idProyecto)
            ->select('b.*', 'r.nombre as responsable')
            ->orderBy('b.id_beneficio')->get()
            ->map(function ($b) {
                $b->logro = $b->valor_logrado !== null
                    ? round(((float) $b->valor_logrado - (float) $b->linea_base) / ((float) $b->meta - (float) $b->linea_base) * 100, 1)
                    : null;
                return $b;
            });

        return [
            'disponible' => true,
            'parametros' => $parametros,
            'presupuesto' => [
                'inicial' => (float) ($versiones->first()->monto ?? $vigente),
                'vigente' => $vigente,
                'versiones' => $versiones,
                'compromisos' => $compromisos,
                'comprometido' => $comprometido,
                'devengado' => $devengado,
                'fecha_devengado' => $ultimoCorte->fecha_reporte ?? null,
                'por_comprometer' => $vigente - $comprometido,
                'ejecucion' => $vigente > 0 ? round($devengado / $vigente * 100, 1) : null,
            ],
            'valor_ganado' => $this->valorGanado->calcular($idProyecto),
            'riesgos' => $riesgos,
            'matriz' => $matriz,
            'documentos' => $documentos,
            'beneficios' => $beneficios,
            'historial' => $this->historial($idProyecto),
        ];
    }

    /** Últimos cambios auditados del proyecto y de todo lo que cuelga de él. */
    private function historial(int $idProyecto, int $limite = 60)
    {
        $db = DB::connection('lector');
        $ids = [
            'proyecto' => [$idProyecto],
            'hito' => $db->table('hito')->where('id_proyecto', $idProyecto)->pluck('id_hito')->all(),
            'avance_presupuestal' => $db->table('avance_presupuestal')->where('id_proyecto', $idProyecto)->pluck('id_avance')->all(),
            'riesgo_proyecto' => $db->table('riesgo_proyecto')->where('id_proyecto', $idProyecto)->pluck('id_riesgo')->all(),
            'compromiso_presupuestal' => $db->table('compromiso_presupuestal')->where('id_proyecto', $idProyecto)->pluck('id_compromiso')->all(),
            'documento_proyecto' => $db->table('documento_proyecto')->where('id_proyecto', $idProyecto)->pluck('id_documento')->all(),
            'beneficio_proyecto' => $db->table('beneficio_proyecto')->where('id_proyecto', $idProyecto)->pluck('id_beneficio')->all(),
            'evaluacion_priorizacion' => $db->table('evaluacion_priorizacion')->where('id_proyecto', $idProyecto)->pluck('id_evaluacion')->all(),
        ];
        $ids['hito_peso'] = $ids['hito'];

        return $db->table('bitacora_cambio as b')
            ->join('responsable as r', 'r.id_responsable', '=', 'b.id_responsable')
            ->leftJoin('bitacora_motivo as m', 'm.id_cambio', '=', 'b.id_cambio')
            ->where(function ($q) use ($ids) {
                foreach ($ids as $tabla => $lista) {
                    if ($lista !== []) {
                        $q->orWhere(fn ($w) => $w->where('b.tabla_afectada', $tabla)->whereIn('b.id_registro', $lista));
                    }
                }
            })
            ->select('b.id_cambio', 'b.tabla_afectada', 'b.id_registro', 'b.campo', 'b.valor_anterior', 'b.valor_nuevo',
                'b.fecha_hora', 'r.nombre as responsable', 'm.motivo')
            ->orderByDesc('b.id_cambio')
            ->limit($limite)
            ->get();
    }
}
