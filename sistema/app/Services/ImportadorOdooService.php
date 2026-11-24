<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportadorOdooService
{
    private const ALIAS_COLUMNAS = [
        'id_externo_linea' => ['id', 'id externo', 'external id', 'id_externo', 'id_externo_linea'],
        'orden_compra' => ['order_id', 'referencia de pedido', 'orden', 'orden de compra', 'order reference', 'orden_compra', 'order_id/name'],
        'fecha_orden' => ['date_order', 'fecha de orden', 'fecha', 'order date', 'fecha_orden', 'order_id/date_order'],
        'proveedor' => ['partner_id', 'proveedor', 'vendor', 'order_id/partner_id'],
        'producto' => ['product_id', 'producto', 'product'],
        'descripcion' => ['name', 'descripcion', 'description'],
        'cantidad_comprada' => ['product_qty', 'cantidad', 'quantity', 'cantidad comprada', 'cantidad_comprada'],
        'cantidad_recibida' => ['qty_received', 'cantidad recibida', 'recibido', 'received', 'received qty', 'cantidad_recibida'],
        'importe_total' => ['price_total', 'total', 'importe', 'importe total', 'importe_total', 'price_subtotal'],
        'codigo_pgd' => ['codigo_pgd', 'proyecto'],
    ];

    /**
     * Lee y valida un archivo CSV exportado desde Odoo sin persistir cambios.
     *
     * @return array{valido: bool, filas: array<int, array<string, mixed>>, errores_generales: array<int, string>}
     */
    public function previsualizarDesdeCsv(string $contenidoCsv): array
    {
        $contenidoLimpio = preg_replace('/^\xEF\xBB\xBF/', '', $contenidoCsv) ?? $contenidoCsv;
        $lineas = preg_split("/\r\n|\n|\r/", trim($contenidoLimpio));

        if (!$lineas || count($lineas) < 2) {
            return [
                'valido' => false,
                'filas' => [],
                'errores_generales' => ['El archivo CSV debe incluir fila de encabezados y al menos un registro.'],
            ];
        }

        $delimitador = $this->detectarDelimitador($lineas[0]);
        $encabezadosCrudos = str_getcsv($lineas[0], $delimitador);
        $mapa = $this->mapearEncabezados($encabezadosCrudos);

        $faltantes = array_diff(['id_externo_linea', 'orden_compra', 'cantidad_comprada', 'cantidad_recibida'], array_keys($mapa));
        if (!empty($faltantes)) {
            return [
                'valido' => false,
                'filas' => [],
                'errores_generales' => ['Faltan columnas obligatorias de Odoo en el CSV: ' . implode(', ', $faltantes) . '.'],
            ];
        }

        $proyectosPorCodigo = DB::connection('lector')
            ->table('proyecto')
            ->select('id_proyecto', 'codigo_pgd', 'nombre')
            ->get()
            ->keyBy('codigo_pgd');

        $existentesPorExterno = DB::connection('lector')
            ->table('abastecimiento_compra')
            ->pluck('id_abastecimiento', 'id_externo_linea')
            ->all();

        $filas = [];
        $idsVistos = [];
        $hayErrores = false;

        foreach (array_slice($lineas, 1) as $indice => $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $numeroFila = $indice + 2;
            $celdas = str_getcsv($linea, $delimitador);
            $fila = $this->extraerFila($celdas, $mapa);
            $errores = $this->validarFila($fila, $proyectosPorCodigo, $idsVistos);

            if (!empty($fila['id_externo_linea'])) {
                $idsVistos[] = $fila['id_externo_linea'];
            }

            $proyecto = $fila['codigo_pgd'] !== null ? ($proyectosPorCodigo[$fila['codigo_pgd']] ?? null) : null;
            $comprada = is_numeric($fila['cantidad_comprada']) ? round((float) $fila['cantidad_comprada'], 2) : 0.0;
            $recibida = is_numeric($fila['cantidad_recibida']) ? round((float) $fila['cantidad_recibida'], 2) : 0.0;
            $saldo = round($comprada - $recibida, 2);

            if (!empty($errores)) {
                $hayErrores = true;
                $estadoValidacion = 'error';
            } elseif (isset($existentesPorExterno[$fila['id_externo_linea']])) {
                $estadoValidacion = 'actualizacion';
            } else {
                $estadoValidacion = 'nueva';
            }

            $filas[] = [
                'numero_fila' => $numeroFila,
                'estado_validacion' => $estadoValidacion,
                'errores' => $errores,
                'id_proyecto' => $proyecto ? (int) $proyecto->id_proyecto : null,
                'codigo_pgd' => $fila['codigo_pgd'],
                'nombre_proyecto' => $proyecto ? $proyecto->nombre : null,
                'id_externo_linea' => $fila['id_externo_linea'],
                'orden_compra' => $fila['orden_compra'],
                'fecha_orden' => $fila['fecha_orden'],
                'proveedor' => $fila['proveedor'],
                'producto' => $fila['producto'],
                'descripcion' => $fila['descripcion'],
                'cantidad_comprada' => $comprada,
                'cantidad_recibida' => $recibida,
                'saldo_pendiente' => $saldo,
                'importe_total' => $fila['importe_total'],
            ];
        }

        if (empty($filas)) {
            return [
                'valido' => false,
                'filas' => [],
                'errores_generales' => ['El archivo CSV no contiene filas de datos válidas.'],
            ];
        }

        return [
            'valido' => !$hayErrores,
            'filas' => $filas,
            'errores_generales' => [],
        ];
    }

    /**
     * Persiste de forma atómica e idempotente las líneas validadas desde CSV.
     *
     * @return array{insertadas: int, actualizadas: int, total: int}
     */
    public function confirmarDesdeCsv(string $contenidoCsv, int $idResponsable, ?string $archivoOrigen = null): array
    {
        $resultado = $this->previsualizarDesdeCsv($contenidoCsv);

        if (!$resultado['valido']) {
            $mensajes = $resultado['errores_generales'];
            foreach ($resultado['filas'] as $fila) {
                if (!empty($fila['errores'])) {
                    $mensajes[] = "Fila {$fila['numero_fila']}: " . implode(' ', $fila['errores']);
                }
            }
            throw ValidationException::withMessages([
                'archivo_csv' => !empty($mensajes) ? $mensajes : ['La carga contiene errores de validación y no se publicó.'],
            ]);
        }

        return DatabaseTransactionService::run($idResponsable, function ($db) use ($resultado, $idResponsable, $archivoOrigen) {
            $insertadas = 0;
            $actualizadas = 0;

            foreach ($resultado['filas'] as $fila) {
                $existente = $db->table('abastecimiento_compra')
                    ->where('id_externo_linea', $fila['id_externo_linea'])
                    ->lockForUpdate()
                    ->first();

                $datos = [
                    'id_proyecto' => $fila['id_proyecto'],
                    'orden_compra' => $fila['orden_compra'],
                    'fecha_orden' => $fila['fecha_orden'],
                    'proveedor' => $fila['proveedor'] !== '' ? $fila['proveedor'] : 'No especificado',
                    'producto' => $fila['producto'] !== '' ? $fila['producto'] : 'No especificado',
                    'descripcion' => $fila['descripcion'],
                    'cantidad_comprada' => $fila['cantidad_comprada'],
                    'cantidad_recibida' => $fila['cantidad_recibida'],
                    'saldo_pendiente' => $fila['saldo_pendiente'],
                    'importe_total' => $fila['importe_total'],
                    'archivo_origen' => $archivoOrigen,
                    'id_responsable_carga' => $idResponsable,
                    'fecha_carga' => now()->format('Y-m-d H:i:s'),
                ];

                if ($existente) {
                    $db->table('abastecimiento_compra')
                        ->where('id_abastecimiento', $existente->id_abastecimiento)
                        ->update($datos);
                    $actualizadas++;
                } else {
                    $datos['id_externo_linea'] = $fila['id_externo_linea'];
                    $db->table('abastecimiento_compra')->insert($datos);
                    $insertadas++;
                }
            }

            return [
                'insertadas' => $insertadas,
                'actualizadas' => $actualizadas,
                'total' => $insertadas + $actualizadas,
            ];
        }, 'Importación CSV de compras y recepciones Odoo');
    }

    private function detectarDelimitador(string $encabezado): string
    {
        return substr_count($encabezado, ';') > substr_count($encabezado, ',') ? ';' : ',';
    }

    /**
     * @param array<int, string> $encabezados
     * @return array<string, int>
     */
    private function mapearEncabezados(array $encabezados): array
    {
        $mapa = [];
        foreach ($encabezados as $indice => $nombre) {
            $norm = $this->normalizarTexto($nombre);
            foreach (self::ALIAS_COLUMNAS as $campo => $aliases) {
                if (!isset($mapa[$campo]) && in_array($norm, $aliases, true)) {
                    $mapa[$campo] = $indice;
                }
            }
        }
        return $mapa;
    }

    private function normalizarTexto(?string $texto): string
    {
        $limpio = mb_strtolower(trim((string) $texto), 'UTF-8');
        $limpio = strtr($limpio, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        return $limpio;
    }

    /**
     * @param array<int, string> $celdas
     * @param array<string, int> $mapa
     * @return array<string, mixed>
     */
    private function extraerFila(array $celdas, array $mapa): array
    {
        $obtener = fn (string $campo): string => isset($mapa[$campo]) ? trim((string) ($celdas[$mapa[$campo]] ?? '')) : '';

        $descripcion = $obtener('descripcion');
        $orden = $obtener('orden_compra');
        $codigoExplicito = $obtener('codigo_pgd');
        $codigoExtraido = $this->extraerCodigoProyecto($codigoExplicito !== '' ? $codigoExplicito : ($descripcion . ' ' . $orden));

        $fechaCruda = $obtener('fecha_orden');
        $fechaNormalizada = null;
        if ($fechaCruda !== '' && preg_match('/^(\d{4}-\d{2}-\d{2})/', $fechaCruda, $m)) {
            $fechaNormalizada = $m[1];
        }

        $importeCrudo = $this->limpiarNumero($obtener('importe_total'));

        return [
            'id_externo_linea' => $obtener('id_externo_linea'),
            'orden_compra' => $orden,
            'fecha_orden' => $fechaNormalizada,
            'proveedor' => $obtener('proveedor'),
            'producto' => $obtener('producto'),
            'descripcion' => $descripcion,
            'codigo_pgd' => $codigoExtraido,
            'cantidad_comprada' => $this->limpiarNumero($obtener('cantidad_comprada')),
            'cantidad_recibida' => $this->limpiarNumero($obtener('cantidad_recibida')),
            'importe_total' => is_numeric($importeCrudo) ? round((float) $importeCrudo, 2) : null,
        ];
    }

    private function limpiarNumero(string $valor): string
    {
        $limpio = trim(str_replace(['S/', 's/', ' '], '', $valor));
        if (preg_match('/^\d+,\d{1,2}$/', $limpio)) {
            $limpio = str_replace(',', '.', $limpio);
        } else {
            $limpio = str_replace(',', '', $limpio);
        }
        return $limpio;
    }

    private function extraerCodigoProyecto(string $texto): ?string
    {
        if (preg_match('/PROYGD-(\d{1,5})/i', $texto, $m)) {
            return str_pad($m[1], 3, '0', STR_PAD_LEFT);
        }
        if (preg_match('/^\d{1,5}$/', trim($texto))) {
            return str_pad(trim($texto), 3, '0', STR_PAD_LEFT);
        }
        return null;
    }

    /**
     * @param array<string, mixed> $fila
     * @param \Illuminate\Support\Collection<string, object> $proyectosPorCodigo
     * @param array<int, string> $idsVistos
     * @return array<int, string>
     */
    private function validarFila(array $fila, $proyectosPorCodigo, array $idsVistos): array
    {
        $errores = [];

        if ($fila['id_externo_linea'] === '') {
            $errores[] = 'Falta el ID externo de la línea.';
        } elseif (in_array($fila['id_externo_linea'], $idsVistos, true)) {
            $errores[] = 'ID externo duplicado dentro del archivo.';
        }

        if ($fila['orden_compra'] === '') {
            $errores[] = 'Falta el número de orden de compra.';
        }

        if ($fila['codigo_pgd'] === null || !$proyectosPorCodigo->has($fila['codigo_pgd'])) {
            $errores[] = 'Proyecto no identificado o inexistente en la cartera PGD.';
        }

        if (!is_numeric($fila['cantidad_comprada']) || (float) $fila['cantidad_comprada'] <= 0) {
            $errores[] = 'La cantidad comprada debe ser mayor que cero.';
        }

        if (!is_numeric($fila['cantidad_recibida']) || (float) $fila['cantidad_recibida'] < 0) {
            $errores[] = 'La cantidad recibida no puede ser negativa.';
        }

        if (is_numeric($fila['cantidad_comprada']) && is_numeric($fila['cantidad_recibida'])
            && (float) $fila['cantidad_recibida'] > (float) $fila['cantidad_comprada']) {
            $errores[] = 'La cantidad recibida no puede superar la cantidad comprada.';
        }

        return $errores;
    }
}
