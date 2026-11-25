<?php

namespace App\Services\Gestion;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Alertas preventivas y de control de la cartera activa (Pendiente y En curso), a una fecha de referencia.
 * Los umbrales viven en parametro_alerta (los ajusta el Administrador); cada alerta lleva al detalle que la origina.
 */
class AlertasService
{
    public const PREDETERMINADOS = [
        'dias_aviso_hito' => 15,
        'dias_sin_actualizar' => 100,
        'umbral_desviacion' => -5,
        'umbral_sobregasto' => 0,
        'exposicion_critica' => 15,
        'exposicion_media' => 8,
        'exigir_acta_cierre' => 0,
    ];

    private const SEVERIDAD_ORDEN = ['ALTA' => 0, 'MEDIA' => 1, 'BAJA' => 2];

    public static function parametros(): array
    {
        try {
            $guardados = DB::connection('lector')->table('parametro_alerta')->pluck('valor', 'clave')
                ->map(fn ($v) => (float) $v)->all();
        } catch (\Throwable $e) {
            $guardados = [];
        }
        return array_merge(self::PREDETERMINADOS, $guardados);
    }

    public static function nivelRiesgo(int $exposicion, ?array $parametros = null): string
    {
        $p = $parametros ?? self::parametros();
        return match (true) {
            $exposicion >= $p['exposicion_critica'] => 'Crítico',
            $exposicion >= $p['exposicion_media'] => 'Medio',
            default => 'Bajo',
        };
    }

    /** @return array{fecha:string, parametros:array, alertas:Collection, resumen:array} */
    public function generar(?string $fecha = null, ?int $soloResponsable = null): array
    {
        $ref = Carbon::parse($fecha ?? now()->toDateString())->startOfDay();
        $p = self::parametros();
        $db = DB::connection('lector');
        $alertas = collect();

        $proyectos = $db->table('proyecto as p')
            ->join('responsable as r', 'r.id_responsable', '=', 'p.id_responsable')
            ->where('p.estado', '<>', 'Cerrado')
            ->when($soloResponsable, fn ($q) => $q->where('p.id_responsable', $soloResponsable))
            ->select('p.id_proyecto', 'p.codigo_pgd', 'p.nombre', 'r.nombre as responsable')
            ->get()
            ->keyBy('id_proyecto');
        $ids = $proyectos->keys()->all();
        if ($ids === []) {
            return ['fecha' => $ref->toDateString(), 'parametros' => $p, 'alertas' => $alertas, 'resumen' => $this->resumen($alertas)];
        }

        $agregar = function (string $tipo, string $severidad, int $idProyecto, string $detalle, string $ancla) use ($alertas, $proyectos) {
            $proy = $proyectos->get($idProyecto);
            $alertas->push((object) [
                'tipo' => $tipo, 'severidad' => $severidad, 'id_proyecto' => $idProyecto,
                'codigo_pgd' => $proy->codigo_pgd, 'proyecto' => $proy->nombre, 'responsable' => $proy->responsable,
                'detalle' => $detalle, 'url' => route('proyectos.show', $idProyecto).'#'.$ancla,
            ]);
        };

        // Hitos de línea base vencidos y próximos a vencer.
        $limite = $ref->copy()->addDays((int) $p['dias_aviso_hito']);
        $hitos = $db->table('hito')->whereIn('id_proyecto', $ids)->where('en_linea_base', 1)->whereNull('fecha_cierre')
            ->where('fecha_prevista', '<=', $limite->toDateString())
            ->select('id_proyecto', 'descripcion', 'fecha_prevista')->orderBy('fecha_prevista')->get();
        foreach ($hitos as $h) {
            $prevista = Carbon::parse($h->fecha_prevista);
            if ($prevista->lt($ref)) {
                $dias = (int) $prevista->diffInDays($ref);
                $agregar('Hito vencido', 'ALTA', $h->id_proyecto, "«{$h->descripcion}» venció el {$h->fecha_prevista} ({$dias} días de atraso).", 'hitos');
            } else {
                $dias = (int) $ref->diffInDays($prevista);
                $agregar('Hito próximo a vencer', 'MEDIA', $h->id_proyecto, "«{$h->descripcion}» vence el {$h->fecha_prevista} (en {$dias} días).", 'hitos');
            }
        }

        // Último corte por proyecto: antigüedad y desviación.
        $ultimos = $db->table('avance_presupuestal as a')
            ->whereIn('a.id_proyecto', $ids)
            ->whereRaw('a.fecha_reporte = (SELECT MAX(b.fecha_reporte) FROM avance_presupuestal b WHERE b.id_proyecto = a.id_proyecto)')
            ->select('a.id_proyecto', 'a.fecha_reporte', 'a.presupuesto_planificado', 'a.gasto_acumulado')
            ->get()->keyBy('id_proyecto');
        $corteMinimo = $ref->copy()->subDays((int) $p['dias_sin_actualizar']);
        foreach ($ids as $id) {
            $u = $ultimos->get($id);
            if ($u === null) {
                $agregar('Proyecto sin actualizar', 'MEDIA', $id, 'No tiene ningún corte presupuestal registrado.', 'cortes');
                continue;
            }
            if (Carbon::parse($u->fecha_reporte)->lt($corteMinimo)) {
                $agregar('Proyecto sin actualizar', 'MEDIA', $id, "Último corte del {$u->fecha_reporte}, hace más de {$p['dias_sin_actualizar']} días.", 'cortes');
            }
            if ((float) $u->presupuesto_planificado > 0) {
                $desv = ((float) $u->gasto_acumulado - (float) $u->presupuesto_planificado) / (float) $u->presupuesto_planificado * 100;
                $texto = number_format($desv, 2).' % en el corte del '.$u->fecha_reporte;
                if ($desv <= $p['umbral_desviacion']) {
                    $agregar('Desfase de devengado', 'MEDIA', $id, "Gasto por debajo del plan: {$texto}. Puede indicar retraso.", 'cortes');
                } elseif ($desv > $p['umbral_sobregasto']) {
                    $agregar('Gasto sobre el plan', 'MEDIA', $id, "Gasto por encima del plan: {$texto}.", 'cortes');
                }
            }
        }

        // Riesgos críticos abiertos.
        try {
            $riesgos = $db->table('riesgo_proyecto')->whereIn('id_proyecto', $ids)->where('estado', '<>', 'Cerrado')
                ->where('exposicion', '>=', $p['exposicion_critica'])->select('id_proyecto', 'tipo', 'descripcion', 'exposicion')->get();
            foreach ($riesgos as $r) {
                $agregar('Riesgo crítico', 'ALTA', $r->id_proyecto, "{$r->tipo} con exposición {$r->exposicion}/25: «{$r->descripcion}».", 'riesgos');
            }

            // Priorización incompleta: proyectos activos sin todas las calificaciones vigentes.
            $vigentes = $db->table('criterio_priorizacion')->where('activo', 1)->where('peso', '>', 0)->pluck('id_criterio');
            if ($vigentes->isNotEmpty()) {
                $conteo = $db->table('evaluacion_priorizacion')->whereIn('id_proyecto', $ids)->whereIn('id_criterio', $vigentes)
                    ->groupBy('id_proyecto')->selectRaw('id_proyecto, COUNT(*) n')->pluck('n', 'id_proyecto');
                foreach ($ids as $id) {
                    $n = (int) ($conteo[$id] ?? 0);
                    if ($n < $vigentes->count()) {
                        $agregar('Priorización incompleta', 'BAJA', $id, "Calificado en {$n} de {$vigentes->count()} criterios de priorización.", 'gestion');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Esquema anterior a la gestión gerencial.
        }

        $ordenadas = $alertas->sortBy([
            fn ($a, $b) => self::SEVERIDAD_ORDEN[$a->severidad] <=> self::SEVERIDAD_ORDEN[$b->severidad],
            fn ($a, $b) => strcmp($a->codigo_pgd, $b->codigo_pgd),
        ])->values();

        return ['fecha' => $ref->toDateString(), 'parametros' => $p, 'alertas' => $ordenadas, 'resumen' => $this->resumen($ordenadas)];
    }

    private function resumen(Collection $alertas): array
    {
        return [
            'total' => $alertas->count(),
            'por_severidad' => ['ALTA' => $alertas->where('severidad', 'ALTA')->count(), 'MEDIA' => $alertas->where('severidad', 'MEDIA')->count(), 'BAJA' => $alertas->where('severidad', 'BAJA')->count()],
            'por_tipo' => $alertas->countBy('tipo')->sortDesc()->all(),
            'proyectos' => $alertas->pluck('id_proyecto')->unique()->count(),
        ];
    }
}
