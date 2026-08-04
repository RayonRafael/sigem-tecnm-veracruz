<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventario;

class InventarioPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador';
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Inventario $modelInstance): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Inventario $modelInstance): bool { return true; }
    public function delete(User $user, Inventario $modelInstance): bool { return $this->isAdmin($user); }
}
