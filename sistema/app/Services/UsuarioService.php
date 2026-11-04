<?php

namespace App\Services;

use App\Models\Responsable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsuarioService
{
    /**
     * Cambia el estado activo de un responsable asegurando la protección del último administrador.
     */
    public static function cambiarEstado(int $idResponsable, int $nuevoEstado, ?string $motivo = null): Responsable
    {
        $idActor = Auth::id() ?? $idResponsable;
        $motivoAudit = mb_substr($motivo ?? "Cambio de estado activo usuario #{$idResponsable} a {$nuevoEstado}", 0, 200);

        return DatabaseTransactionService::run($idActor, function () use ($idResponsable, $nuevoEstado) {
            // 1. Bloqueo pesimista de todos los administradores activos para evitar condición de carrera
            $adminsActivos = DB::connection('escritor')->table('responsable')
                ->where('perfil', 'Administrador')
                ->where('activo', 1)
                ->lockForUpdate()
                ->get();

            $usuario = DB::connection('escritor')->table('responsable')
                ->where('id_responsable', $idResponsable)
                ->lockForUpdate()
                ->first();

            if (!$usuario) {
                throw new RuntimeException("Usuario #{$idResponsable} no encontrado.");
            }

            // Si es un administrador que se intenta desactivar
            if ($usuario->perfil === 'Administrador' && $nuevoEstado === 0) {
                if ($adminsActivos->count() <= 1 && $adminsActivos->contains('id_responsable', $idResponsable)) {
                    throw new RuntimeException('Operación denegada: No se puede desactivar al único Administrador activo del sistema.');
                }
            }

            DB::connection('escritor')->table('responsable')
                ->where('id_responsable', $idResponsable)
                ->update(['activo' => $nuevoEstado]);

            return Responsable::find($idResponsable);
        }, $motivoAudit);
    }
}
