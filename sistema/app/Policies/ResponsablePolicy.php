<?php
namespace App\Policies;
use App\Models\Responsable;
class ResponsablePolicy
{
    public function viewAny(Responsable $actor): bool { return $actor->esAdmin(); }
    public function create(Responsable $actor): bool { return $actor->esAdmin(); }
    public function update(Responsable $actor, Responsable $destino): bool { return $actor->esAdmin(); }
}
