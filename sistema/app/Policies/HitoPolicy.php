<?php

namespace App\Policies;

use App\Models\Hito;
use App\Models\Proyecto;
use App\Models\Responsable;

class HitoPolicy
{
    /**
     * Determina si el usuario puede ver los hitos.
     */
    public function viewAny(Responsable $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede crear un hito en el proyecto.
     * Denegación por defecto para perfil Consulta.
     * No se pueden agregar hitos a proyectos cerrados.
     */
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

    /**
     * Determina si el usuario puede actualizar un hito.
     */
    public function update(Responsable $user, Hito $hito): bool
    {
        if ($user->esConsulta()) {
            return false;
        }

        $proyecto = $hito->proyecto;
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

    /**
     * Determina si el usuario puede eliminar un hito (solo Administrador).
     */
    public function delete(Responsable $user, Hito $hito): bool
    {
        return $user->esAdmin();
    }
}
