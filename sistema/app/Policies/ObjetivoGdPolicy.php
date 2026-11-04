<?php

namespace App\Policies;

use App\Models\ObjetivoGd;
use App\Models\Responsable;

class ObjetivoGdPolicy
{
    public function viewAny(Responsable $user): bool
    {
        return true;
    }

    public function view(Responsable $user, ObjetivoGd $objetivo): bool
    {
        return true;
    }

    public function create(Responsable $user): bool
    {
        return $user->esAdmin();
    }

    public function update(Responsable $user, ObjetivoGd $objetivo): bool
    {
        return $user->esAdmin();
    }

    public function delete(Responsable $user, ObjetivoGd $objetivo): bool
    {
        return $user->esAdmin();
    }
}
