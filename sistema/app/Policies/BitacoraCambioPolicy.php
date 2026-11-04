<?php

namespace App\Policies;

use App\Models\BitacoraCambio;
use App\Models\Responsable;

class BitacoraCambioPolicy
{
    /**
     * Solo el Administrador puede consultar la bitácora técnica de auditoría.
     */
    public function viewAny(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    public function view(Responsable $user, BitacoraCambio $bitacora): bool
    {
        return $user->esAdmin();
    }

    public function create(Responsable $user): bool
    {
        return false; // Las entradas se generan exclusivamente por triggers en MariaDB
    }

    public function update(Responsable $user, BitacoraCambio $bitacora): bool
    {
        return false; // La bitácora es estrictamente inmutable
    }

    public function delete(Responsable $user, BitacoraCambio $bitacora): bool
    {
        return false; // Prohibido eliminar registros de auditoría
    }
}
