<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseTransactionService
{
    public static function verificarRevision(int $esperada): void
    {
        $actual = (int) DB::connection('escritor')->table('control_revision')->where('id', 1)->value('version');
        if ($esperada !== $actual) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'concurrencia' => 'Los datos cambiaron. Recargue la pantalla antes de guardar.',
            ]);
        }
    }
    /**
     * Ejecuta una operación de escritura de negocio dentro de una transacción atómica
     * aplicando el protocolo canónico de bloqueos (Decisión 6):
     * 1. Bloqueo FOR UPDATE sobre la revisión central (control_revision id=1).
     * 2. Bloqueo FOR UPDATE sobre los proyectos afectados ordenados por id_proyecto ASC.
     * 3. Establecimiento de variables de auditoría (@id_responsable, @motivo_cambio).
     * 4. Ejecución del callback de negocio.
     * 5. Limpieza incondicional en bloque finally.
     *
     * @param int $idResponsable ID del responsable autenticado obtenido de la sesión.
     * @param callable $callback Función con la lógica de negocio a ejecutar.
     * @param string|null $motivo Motivo justificado para cambios sensibles (Decisión 11).
     * @param string $connection Conexión a utilizar (por defecto 'escritor').
     * @param array $proyectosIds IDs de proyectos a bloquear en orden ascendente.
     * @return mixed
     * @throws Throwable
     */
    public static function run(int $idResponsable, callable $callback, ?string $motivo = null, string $connection = 'escritor', array $proyectosIds = [])
    {
        $db = DB::connection($connection);

        $db->beginTransaction();
        try {
            // 1. Establecer variables de contexto en la misma conexión de escritura
            $db->statement('SET @id_responsable = ?', [$idResponsable]);
            $db->statement('SET @motivo_cambio = ?', [$motivo]);

            // 2. Orden global de bloqueos: primero revisión central (FOR UPDATE)
            $db->select('SELECT id, version FROM control_revision WHERE id = 1 FOR UPDATE');

            // Una autorización anterior al bloqueo no garantiza que el actor siga vigente.
            if (\Illuminate\Support\Facades\Auth::check()) {
                $actor = $db->table('responsable')->select('id_responsable','activo','perfil')->where('id_responsable', $idResponsable)->first();
                abort_unless($actor && (int) $actor->activo === 1, 403, 'La cuenta ya no está activa.');
                $usuario = \Illuminate\Support\Facades\Auth::user();
                $usuario->perfil = $actor->perfil;
                $usuario->activo = (bool) $actor->activo;
            }

            // 3. Proyectos ordenados por ID (FOR UPDATE) para prevenir deadlocks
            if (!empty($proyectosIds)) {
                $sortedIds = array_unique(array_filter(array_map('intval', $proyectosIds)));
                sort($sortedIds);
                if (!empty($sortedIds)) {
                    $db->table('proyecto')
                        ->whereIn('id_proyecto', $sortedIds)
                        ->orderBy('id_proyecto', 'asc')
                        ->lockForUpdate()
                        ->get();
                }
            }

            // 4. Ejecutar la operación de negocio
            $result = $callback($db);

            // 5. Confirmar la transacción
            $db->commit();

            return $result;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        } finally {
            // 6. Limpieza estricta e incondicional del contexto de conexión (prevención de contaminación)
            try {
                $db->statement('SET @id_responsable = NULL');
                $db->statement('SET @motivo_cambio = NULL');
            } catch (Throwable $cleanupException) {
            }
        }
    }
}
