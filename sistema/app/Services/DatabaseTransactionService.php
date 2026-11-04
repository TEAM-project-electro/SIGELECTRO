<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseTransactionService
{
    /**
     * Ejecuta una operación de escritura de negocio dentro de una transacción atómica
     * fijando el actor de auditoría (@id_responsable) y el motivo opcional (@motivo_cambio),
     * limpiando incondicionalmente ambas variables en el bloque finally.
     *
     * @param int $idResponsable ID del responsable autenticado obtenido de la sesión.
     * @param callable $callback Función con la lógica de negocio a ejecutar.
     * @param string|null $motivo Motivo justificado para cambios sensibles (Decisión 11).
     * @param string $connection Conexión a utilizar (por defecto 'escritor').
     * @return mixed
     * @throws Throwable
     */
    public static function run(int $idResponsable, callable $callback, ?string $motivo = null, string $connection = 'escritor')
    {
        $db = DB::connection($connection);

        $db->beginTransaction();
        try {
            // 1. Establecer variables de contexto en la misma conexión de escritura
            $db->statement('SET @id_responsable = ?', [$idResponsable]);
            $db->statement('SET @motivo_cambio = ?', [$motivo]);

            // 2. Ejecutar la operación de negocio
            $result = $callback($db);

            // 3. Confirmar la transacción
            $db->commit();

            return $result;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        } finally {
            // 4. Limpieza estricta e incondicional del contexto de conexión (prevención de contaminación)
            try {
                $db->statement('SET @id_responsable = NULL');
                $db->statement('SET @motivo_cambio = NULL');
            } catch (Throwable $cleanupException) {
                // Silenciar o registrar error en limpieza sin enmascarar la excepción original
            }
        }
    }
}
