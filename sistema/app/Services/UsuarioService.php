<?php
namespace App\Services;
use App\Models\Responsable;
class UsuarioService
{
    public static function cambiarEstado(int $idResponsable, int $nuevoEstado, ?string $motivo = null): Responsable
    {
        return AdministrarUsuarios::guardar($idResponsable, ['activo'=>$nuevoEstado], $motivo);
    }
}
