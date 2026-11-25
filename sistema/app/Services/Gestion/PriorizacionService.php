<?php

namespace App\Services\Gestion;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Priorización multicriterio de la cartera (debilidad explícita del PEI).
 *
 * Puntaje 0–100 = Σ peso × (calificación − 1) / 4 ÷ Σ peso × 100, sobre los criterios activos con peso > 0.
 * Solo entran al ranking los proyectos con todas esas calificaciones; el resto se muestra como incompleto.
 * El análisis de sensibilidad varía cada peso ±10 % y reporta si cambia el primer lugar.
 */
class PriorizacionService
{
    public function criterios(): Collection
    {
        return DB::connection('lector')->table('criterio_priorizacion')->orderBy('orden')->orderBy('id_criterio')->get();
    }

    public function ranking(): array
    {
        $criterios = $this->criterios();
        $vigentes = $criterios->filter(fn ($c) => (int) $c->activo === 1 && (float) $c->peso > 0)->values();
        $pesos = $vigentes->mapWithKeys(fn ($c) => [$c->id_criterio => (float) $c->peso])->all();

        $proyectos = DB::connection('lector')->table('proyecto as p')
            ->join('responsable as r', 'r.id_responsable', '=', 'p.id_responsable')
            ->where('p.estado', '<>', 'Cerrado')
            ->select('p.id_proyecto', 'p.codigo_pgd', 'p.nombre', 'p.estado', 'p.monto', 'r.nombre as responsable')
            ->orderBy('p.codigo_pgd')
            ->get();

        $evaluaciones = DB::connection('lector')->table('evaluacion_priorizacion as e')
            ->join('responsable as r', 'r.id_responsable', '=', 'e.id_responsable')
            ->select('e.id_proyecto', 'e.id_criterio', 'e.puntaje', 'e.sustento', 'e.fecha_hora', 'r.nombre as evaluador')
            ->get()
            ->groupBy('id_proyecto');

        $filas = $proyectos->map(function ($p) use ($evaluaciones, $pesos) {
            $porCriterio = ($evaluaciones->get($p->id_proyecto) ?? collect())->keyBy('id_criterio');
            $faltantes = array_values(array_diff(array_keys($pesos), $porCriterio->keys()->all()));
            $calificaciones = $porCriterio->map(fn ($e) => (int) $e->puntaje)->all();
            $completo = $pesos !== [] && $faltantes === [];

            return [
                'id_proyecto' => (int) $p->id_proyecto,
                'codigo_pgd' => $p->codigo_pgd,
                'nombre' => $p->nombre,
                'estado' => $p->estado,
                'monto' => (float) $p->monto,
                'responsable' => $p->responsable,
                'evaluaciones' => $porCriterio,
                'completo' => $completo,
                'faltantes' => count($faltantes),
                'puntaje' => $completo ? self::puntaje($calificaciones, $pesos) : null,
                'aportes' => $completo ? self::aportes($calificaciones, $pesos) : [],
            ];
        });

        $ordenados = $filas->where('completo', true)->sortByDesc('puntaje')->values()
            ->concat($filas->where('completo', false)->values());

        $completos = $filas->where('completo', true)->map(fn ($f) => [
            'id' => $f['id_proyecto'],
            'calificaciones' => $f['evaluaciones']->map(fn ($e) => (int) $e->puntaje)->all(),
        ])->values()->all();

        $valoresPeso = array_values($pesos);

        return [
            'criterios' => $criterios,
            'vigentes' => $vigentes,
            'suma_pesos' => array_sum($pesos),
            'pesos_iguales' => $valoresPeso !== [] && count(array_unique(array_map('strval', $valoresPeso))) === 1,
            'filas' => $ordenados,
            'evaluados' => count($completos),
            'sensibilidad' => self::sensibilidad($completos, $pesos, $vigentes),
        ];
    }

    /** @param array<int,int> $calificaciones @param array<int,float> $pesos */
    public static function puntaje(array $calificaciones, array $pesos): float
    {
        return round(array_sum(self::aportes($calificaciones, $pesos)), 2);
    }

    /** Aporte de cada criterio al puntaje (suman el puntaje total). */
    public static function aportes(array $calificaciones, array $pesos): array
    {
        $suma = array_sum($pesos);
        if ($suma <= 0) {
            return [];
        }
        $aportes = [];
        foreach ($pesos as $id => $peso) {
            $aportes[$id] = $peso * ((($calificaciones[$id] ?? 1) - 1) / 4) / $suma * 100;
        }
        return $aportes;
    }

    private static function primero(array $completos, array $pesos): ?int
    {
        $mejor = null;
        $mejorPuntaje = -1.0;
        foreach ($completos as $c) {
            $p = array_sum(self::aportes($c['calificaciones'], $pesos));
            if ($p > $mejorPuntaje + 1e-9) {
                $mejor = $c['id'];
                $mejorPuntaje = $p;
            }
        }
        return $mejor;
    }

    private static function sensibilidad(array $completos, array $pesos, Collection $vigentes): array
    {
        if (count($completos) < 2) {
            return ['aplica' => false, 'estable' => null, 'criterios' => []];
        }
        $base = self::primero($completos, $pesos);
        $cambian = [];
        foreach ($pesos as $id => $peso) {
            foreach ([0.9, 1.1] as $factor) {
                $variados = $pesos;
                $variados[$id] = $peso * $factor;
                if (self::primero($completos, $variados) !== $base) {
                    $cambian[$id] = optional($vigentes->firstWhere('id_criterio', $id))->nombre ?? (string) $id;
                    break;
                }
            }
        }
        return ['aplica' => true, 'estable' => $cambian === [], 'criterios' => array_values($cambian)];
    }
}
