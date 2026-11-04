<?php

namespace App\Policies;

use App\Models\AvancePresupuestal;
use App\Models\Proyecto;
use App\Models\Responsable;

class AvancePresupuestalPolicy
{
    public function viewAny(Responsable $user): bool
    {
        return true;
    }

    public function create(Responsable $user, Proyecto $proyecto): bool
    {
        if ($user->esConsulta() || $proyecto->estado === 'Cerrado') {
            return false;
        }

        if ($user->esAdmin()) {
            return true;
        }

        if ($user->esResponsable()) {
            return (int) $proyecto->id_responsable === (int) $user->id_responsable;
        }

        return false;
    }

    public function update(Responsable $user, AvancePresupuestal $avance): bool
    {
        if ($user->esConsulta()) {
            return false;
        }

        $proyecto = $avance->proyecto;
        if (!$proyecto || $proyecto->estado === 'Cerrado') {
            return false;
        }

        if ($user->esAdmin()) {
            return true;
        }

        if ($user->esResponsable()) {
            return (int) $proyecto->id_responsable === (int) $user->id_responsable;
        }

        return false;
    }

    public function delete(Responsable $user, AvancePresupuestal $avance): bool
    {
        return $user->esAdmin();
    }
}
