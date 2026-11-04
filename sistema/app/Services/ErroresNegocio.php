<?php
namespace App\Services;
use Illuminate\Database\QueryException;
class ErroresNegocio
{
    public static function mensaje(QueryException $error): string
    {
        return match ((int) ($error->errorInfo[1] ?? 0)) {
            1644 => (string) ($error->errorInfo[2] ?? 'La operación incumple una regla de negocio.'),
            1062 => 'Ya existe un registro con ese identificador o fecha.',
            1451, 1452 => 'La operación no puede completarse por registros relacionados.',
            1205, 1213 => 'Los datos están siendo modificados. Recargue e intente nuevamente.',
            default => 'No se pudo guardar la operación. Revise los datos e intente nuevamente.',
        };
    }
}
