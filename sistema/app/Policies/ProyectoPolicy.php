<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\Responsable;

class ProyectoPolicy
{
    /**
     * Determina si el usuario puede ver el listado de proyectos.
     */
    public function viewAny(Responsable $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede ver los detalles de un proyecto.
     */
    public function view(Responsable $user, Proyecto $proyecto): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede crear proyectos (solo Administrador).
     */
    public function create(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    /**
     * Determina si el usuario puede actualizar el proyecto.
     * Denegación por defecto: Consulta nunca puede actualizar.
     * Si está cerrado: solo Administrador puede intervenir (por ejemplo para reabrirlo con motivo).
     * Si no está cerrado: Administrador o Responsable titular asignado.
     */
    public function update(Responsable $user, Proyecto $proyecto): bool
    {
        if ($user->esConsulta()) {
            return false;
        }

        if ($user->esAdmin()) {
            return true;
        }

        if ($user->esResponsable()) {
            // Un responsable no puede modificar un proyecto cerrado
            if ($proyecto->estado === 'Cerrado') {
                return false;
            }
            return (int) $proyecto->id_responsable === (int) $user->id_responsable;
        }

        return false;
    }

    /**
     * Determina si el usuario puede modificar el presupuesto del proyecto (solo Administrador).
     */
    public function updateMonto(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    /**
     * Determina si el usuario puede reasignar el responsable del proyecto (solo Administrador).
     */
    public function updateResponsable(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    /**
     * Determina si el usuario puede modificar el objetivo estratégico del proyecto (solo Administrador).
     */
    public function updateObjetivo(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    /**
     * Determina si el usuario puede eliminar un proyecto (solo Administrador).
     */
    public function delete(Responsable $user, Proyecto $proyecto): bool
    {
        return $user->esAdmin();
    }

    /**
     * Determina si el usuario puede cerrar un proyecto.
     */
    public function cerrar(Responsable $user, Proyecto $proyecto): bool
    {
        if ($proyecto->estado === 'Cerrado') {
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
     * Determina si el usuario puede reabrir un proyecto cerrado (solo Administrador con motivo).
     */
    public function reabrir(Responsable $user, Proyecto $proyecto): bool
    {
        return $user->esAdmin() && $proyecto->estado === 'Cerrado';
    }
}
