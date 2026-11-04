<?php

namespace App\Http\Controllers;

use App\Models\ObjetivoGd;
use App\Models\Responsable;
use App\Services\EtlService;
use App\Services\IndicadoresService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    protected IndicadoresService $indicadoresService;
    protected EtlService $etlService;

    public function __construct(IndicadoresService $indicadoresService, EtlService $etlService)
    {
        $this->indicadoresService = $indicadoresService;
        $this->etlService = $etlService;
    }

    /**
     * Valida y obtiene los datos del reporte garantizando paridad exacta con el tablero.
     */
    protected function obtenerDatosReporte(Request $request): array
    {
        $validated = $request->validate([
            'modo' => ['nullable', 'string', Rule::in([IndicadoresService::MODO_CORTE_COMUN, IndicadoresService::MODO_ULTIMO_REPORTE])],
            'fecha_corte' => ['nullable', 'date_format:Y-m-d'],
            'id_objetivo' => ['nullable', 'integer', 'exists:objetivo_gd,id_objetivo'],
            'id_responsable' => ['nullable', 'integer', 'exists:responsable,id_responsable'],
            'estado' => ['nullable', 'string', Rule::in(['Pendiente', 'En curso', 'Cerrado'])],
            'q' => ['nullable', 'string', 'max:100'],
            'origen' => ['nullable', 'string', Rule::in(['OPERATIVO', 'DW'])],
            'revision_esperada' => ['nullable', 'integer'],
        ]);

        $modo = $validated['modo'] ?? IndicadoresService::MODO_CORTE_COMUN;
        $origen = $validated['origen'] ?? 'OPERATIVO';

        $fechaCorte = $validated['fecha_corte'] ?? (DB::table('avance_presupuestal')->max('fecha_reporte') ?? '2024-06-30');

        $filtros = [
            'modo' => $modo,
            'fecha_corte' => $fechaCorte,
            'id_objetivo' => $validated['id_objetivo'] ?? null,
            'id_responsable' => $validated['id_responsable'] ?? null,
            'estado' => $validated['estado'] ?? null,
            'q' => $validated['q'] ?? null,
        ];

        $estadoDw = $this->etlService->estaDesactualizado();

        if ($origen === 'DW') {
            $datos = $this->etlService->consultarDw($filtros);
        } else {
            $datos = $this->indicadoresService->calcularCartera($filtros);
        }

        // Validación de consistencia temporal de revisión
        $revisionActual = (int) $datos['contexto']['revision_datos'];
        $revisionEsperada = $validated['revision_esperada'] ?? null;
        $revisionDiscrepancia = ($revisionEsperada !== null && (int)$revisionEsperada !== $revisionActual);

        return [
            'datos' => $datos,
            'filtros' => $filtros,
            'origen' => $origen,
            'revision_actual' => $revisionActual,
            'revision_esperada' => $revisionEsperada,
            'revision_discrepancia' => $revisionDiscrepancia,
            'fecha_emision' => Carbon::now('America/Lima')->format('Y-m-d H:i:s'),
            'usuario_emisor' => Auth::user()->nombre . ' (' . Auth::user()->perfil . ')',
        ];
    }

    /**
     * Muestra la vista web imprimible (preparada para imprimir o guardar como PDF en navegador).
     */
    public function imprimir(Request $request)
    {
        $payload = $this->obtenerDatosReporte($request);

        return view('reportes.imprimir', $payload);
    }

    /**
     * Descarga el reporte consolidado y detallado en formato CSV con codificación UTF-8.
     */
    public function exportarCsv(Request $request): StreamedResponse
    {
        $payload = $this->obtenerDatosReporte($request);
        $datos = $payload['datos'];
        $resumen = $datos['resumen'];
        $contexto = $datos['contexto'];

        $nombreArchivo = 'reporte_sig_electrocentro_' . strtolower($contexto['modo']) . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nombreArchivo}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($payload, $datos, $resumen, $contexto) {
            $file = fopen('php://output', 'w');

            // Insertar BOM UTF-8 para garantizar apertura transparente en Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 1. Manifiesto y Metadatos Institucionales
            fputcsv($file, ['SIG ELECTROCENTRO S. A. - SEGUIMIENTO DE CARTERA PGD']);
            fputcsv($file, ['AVISO', 'DATOS DEMOSTRATIVOS - Prototipo Académico (Sin valor oficial)']);
            fputcsv($file, ['Fecha de Emisión', $payload['fecha_emision'] . ' (America/Lima)']);
            fputcsv($file, ['Usuario Emisor', $this->sanitizarCsv($payload['usuario_emisor'])]);
            fputcsv($file, ['Modalidad Temporal', $contexto['modo']]);
            fputcsv($file, ['Fecha de Corte / Límite', $contexto['fecha_corte']]);
            fputcsv($file, ['Rango Fechas Efectivas', $contexto['rango_fechas_texto']]);
            fputcsv($file, ['Origen de Datos', $contexto['origen']]);
            fputcsv($file, ['Revisión Técnica', 'v' . $payload['revision_actual']]);
            fputcsv($file, ['Moneda', 'PEN (S/)']);

            if ($payload['revision_discrepancia']) {
                fputcsv($file, ['ADVERTENCIA', "La cartera mutó tras la consulta inicial (Rev. esperada: v{$payload['revision_esperada']} vs v{$payload['revision_actual']})"]);
            }

            fputcsv($file, []); // Línea en blanco

            // 2. Resumen de los Cuatro Indicadores Canónicos
            fputcsv($file, ['RESUMEN EJECUTIVO DE INDICADORES (RF04)']);
            fputcsv($file, ['Indicador', 'Valor', 'Unidad', 'Numerador', 'Denominador', 'Cobertura', 'Interpretación']);
            fputcsv($file, [
                'Avance de Hitos (Línea Base)',
                $resumen['avance_hitos']['valor_formateado'],
                '%',
                $resumen['avance_hitos']['numerador'],
                $resumen['avance_hitos']['denominador'],
                $resumen['avance_hitos']['cobertura_proyectos'],
                'Hitos de línea base concluidos al corte sobre el total de línea base',
            ]);
            fputcsv($file, [
                'Proyectos con Retraso',
                $resumen['proyectos_retraso']['valor_formateado'],
                '%',
                $resumen['proyectos_retraso']['numerador'],
                $resumen['proyectos_retraso']['denominador'],
                $resumen['proyectos_retraso']['cobertura_proyectos'],
                'Proyectos activos evaluables con al menos un hito de línea base vencido',
            ]);
            fputcsv($file, [
                'Desviación Presupuestal',
                $resumen['desviacion_presupuestal']['valor_formateado'],
                '%',
                number_format($resumen['desviacion_presupuestal']['gasto_acumulado'], 2, '.', ''),
                number_format($resumen['desviacion_presupuestal']['presupuesto_planificado'], 2, '.', ''),
                $resumen['desviacion_presupuestal']['cobertura_proyectos'],
                'Gasto acumulado vs presupuesto planificado acumulado para cortes elegibles',
            ]);
            fputcsv($file, [
                'Composición de Estados de Cartera',
                "En curso: {$resumen['conteo_estados']['En curso']} | Pendiente: {$resumen['conteo_estados']['Pendiente']} | Cerrado: {$resumen['conteo_estados']['Cerrado']}",
                'Proyectos',
                $resumen['total_proyectos'],
                $resumen['total_proyectos'],
                "Monto total: S/ " . number_format($resumen['monto_total_cartera'], 2, '.', ''),
                'Distribución de proyectos por estado vigente',
            ]);

            fputcsv($file, []); // Línea en blanco

            // 3. Detalle Proyecto por Proyecto
            fputcsv($file, ['DETALLE INDIVIDUAL DE PROYECTOS']);
            fputcsv($file, [
                'Código PGD',
                'Nombre del Proyecto',
                'Objetivo PGD',
                'Responsable Titular',
                'Estado Vigente',
                'Monto Presupuestado (S/)',
                'Fecha Corte Evaluada',
                'Hitos LB Concluidos',
                'Hitos LB Totales',
                'Avance Hitos (%)',
                'Con Retraso',
                'Hitos LB Vencidos',
                'Presupuesto Planificado (S/)',
                'Gasto Acumulado (S/)',
                'Diferencia Financiera (S/)',
                'Desviación Presupuestal (%)',
                'Observación Financiera',
            ]);

            foreach ($datos['proyectos'] as $p) {
                fputcsv($file, [
                    $this->sanitizarCsv($p['codigo_pgd'], true), // Conservar ceros y texto
                    $this->sanitizarCsv($p['nombre']),
                    $this->sanitizarCsv(($p['objetivo']['codigo'] ?? '') . ' - ' . ($p['objetivo']['descripcion'] ?? '')),
                    $this->sanitizarCsv($p['responsable']['nombre']),
                    $p['estado'],
                    number_format($p['monto'], 2, '.', ''),
                    $p['fecha_evaluacion'] ?? 'Sin fecha',
                    $p['hitos']['evaluable'] ? $p['hitos']['concluidos_lb'] : 'No aplica',
                    $p['hitos']['evaluable'] ? $p['hitos']['total_lb'] : 'No aplica',
                    $p['hitos']['evaluable'] ? ($p['hitos']['avance_pct'] !== null ? number_format($p['hitos']['avance_pct'], 2, '.', '') : 'No aplica') : 'Sin línea base',
                    $p['hitos']['evaluable'] ? ($p['hitos']['con_retraso'] ? 'SÍ' : 'NO') : 'No aplica',
                    $p['hitos']['evaluable'] ? $p['hitos']['vencidos_lb'] : 0,
                    $p['financiero']['tiene_corte'] && $p['financiero']['presupuesto_planificado'] !== null ? number_format($p['financiero']['presupuesto_planificado'], 2, '.', '') : 'Sin corte exacto',
                    $p['financiero']['tiene_corte'] && $p['financiero']['gasto_acumulado'] !== null ? number_format($p['financiero']['gasto_acumulado'], 2, '.', '') : 'Sin corte exacto',
                    $p['financiero']['tiene_corte'] && $p['financiero']['diferencia'] !== null ? number_format($p['financiero']['diferencia'], 2, '.', '') : 'Sin corte exacto',
                    $p['financiero']['tiene_corte'] && $p['financiero']['desviacion_pct'] !== null ? number_format($p['financiero']['desviacion_pct'], 2, '.', '') : ($p['financiero']['tiene_corte'] ? 'No aplica' : 'Sin corte exacto'),
                    $p['financiero']['observacion'] ?? 'Corte conforme',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Mitiga inyección de fórmulas CSV (CSV Injection) sin alterar números legítimos con signo.
     */
    protected function sanitizarCsv(?string $valor, bool $forzarTexto = false): string
    {
        if ($valor === null) {
            return '';
        }

        $trimmed = trim($valor);

        // Si es número entero o decimal válido (incluso negativo como -6.40 o +2.50), no alterar
        if (!$forzarTexto && is_numeric($trimmed)) {
            return $trimmed;
        }

        // Mitigar caracteres peligrosos al inicio para evitar ejecución de fórmulas en Excel (=, +, -, @, \t, \r)
        if ($forzarTexto || preg_match('/^[=\+\-@\t\r]/', $trimmed)) {
            return "'" . $trimmed;
        }

        return $trimmed;
    }
}
