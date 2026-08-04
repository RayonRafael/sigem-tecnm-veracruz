<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UnidadMedida;

class UnidadMedidaPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador';
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, UnidadMedida $modelInstance): bool { return true; }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, UnidadMedida $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, UnidadMedida $modelInstance): bool { return $this->isAdmin($user); }
}
