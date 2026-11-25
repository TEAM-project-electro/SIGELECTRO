<?php

namespace App\Services\Gestion;

use Illuminate\Support\Facades\DB;

/**
 * Avance físico ponderado y gestión del valor ganado (EVM) de un proyecto a una fecha.
 *
 * - Avance físico = Σ peso de hitos de línea base cerrados a la fecha ÷ Σ peso de los hitos de línea base.
 *   Sin pesos definidos se usa igual peso y se indica que el avance no está ponderado.
 * - EV = avance físico × BAC (presupuesto vigente); PV = plan acumulado del corte; AC = gasto acumulado del corte.
 * - SPI, CPI y EAC solo se calculan con todos los hitos ponderados y un corte con PV y AC mayores que cero:
 *   sin esa base, un índice parecería exacto sin serlo.
 */
class ValorGanadoService
{
    public function calcular(int $idProyecto, ?string $fecha = null): array
    {
        $db = DB::connection('lector');
        $monto = (float) $db->table('proyecto')->where('id_proyecto', $idProyecto)->value('monto');

        $hitos = $db->table('hito as h')
            ->leftJoin('hito_peso as w', 'w.id_hito', '=', 'h.id_hito')
            ->where('h.id_proyecto', $idProyecto)
            ->where('h.en_linea_base', 1)
            ->select('h.id_hito', 'h.descripcion', 'h.fecha_prevista', 'h.fecha_cierre', 'w.peso')
            ->orderBy('h.fecha_prevista')
            ->get();

        $corteQuery = $db->table('avance_presupuestal')->where('id_proyecto', $idProyecto);
        if ($fecha !== null) {
            $corteQuery->where('fecha_reporte', '<=', $fecha);
        }
        $corte = $corteQuery->orderByDesc('fecha_reporte')->first();
        $fechaEval = $fecha ?? ($corte->fecha_reporte ?? now()->toDateString());

        $sinPeso = $hitos->whereNull('peso')->count();
        $ponderado = $hitos->isNotEmpty() && $sinPeso === 0;
        $total = $hitos->sum(fn ($h) => $h->peso !== null ? (float) $h->peso : 1.0);
        $logrado = $hitos->filter(fn ($h) => $h->fecha_cierre !== null && $h->fecha_cierre <= $fechaEval)
            ->sum(fn ($h) => $h->peso !== null ? (float) $h->peso : 1.0);
        $avance = $total > 0 ? $logrado / $total : null;

        $pv = $corte ? (float) $corte->presupuesto_planificado : null;
        $ac = $corte ? (float) $corte->gasto_acumulado : null;
        $ev = $avance !== null ? $avance * $monto : null;

        $motivo = match (true) {
            $hitos->isEmpty() => 'El proyecto no tiene hitos de línea base.',
            !$ponderado => "Faltan pesos en {$sinPeso} de {$hitos->count()} hitos de línea base.",
            $corte === null => 'No hay un corte presupuestal a la fecha.',
            $pv <= 0 || $ac <= 0 => 'El corte no tiene plan y gasto mayores que cero.',
            default => null,
        };

        $spi = $cpi = $eac = $vac = null;
        if ($motivo === null) {
            $spi = $ev / $pv;
            $cpi = $ev / $ac;
            $eac = $cpi > 0 ? $monto / $cpi : null;
            $vac = $eac !== null ? $monto - $eac : null;
        }

        return [
            'fecha' => $fechaEval,
            'fecha_corte' => $corte->fecha_reporte ?? null,
            'hitos' => $hitos,
            'ponderado' => $ponderado,
            'suma_pesos' => (float) $hitos->sum(fn ($h) => (float) ($h->peso ?? 0)),
            'avance_fisico' => $avance !== null ? round($avance * 100, 2) : null,
            'bac' => $monto,
            'pv' => $pv,
            'ev' => $ev !== null ? round($ev, 2) : null,
            'ac' => $ac,
            'spi' => $spi !== null ? round($spi, 3) : null,
            'cpi' => $cpi !== null ? round($cpi, 3) : null,
            'eac' => $eac !== null ? round($eac, 2) : null,
            'vac' => $vac !== null ? round($vac, 2) : null,
            'motivo_sin_indices' => $motivo,
        ];
    }
}
