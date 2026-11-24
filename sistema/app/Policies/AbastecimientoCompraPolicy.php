<?php

namespace App\Policies;

use App\Models\Responsable;

class AbastecimientoCompraPolicy
{
    public function viewAny(Responsable $user): bool
    {
        return true;
    }

    public function import(Responsable $user): bool
    {
        return $user->esAdmin();
    }
}
