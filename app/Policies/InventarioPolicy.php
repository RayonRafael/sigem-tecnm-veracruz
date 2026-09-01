<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventario;

class InventarioPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasPermissionTo(\App\Enums\RoleEnum::PERM_ACCESS_ADMIN) || $user->tipo_usuario === \App\Enums\RoleEnum::ADMIN->value;
    }

    private function isShared(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasPermissionTo(\App\Enums\RoleEnum::PERM_ACCESS_SERVICIO) || $user->tipo_usuario === 'Servicio';
    }

    public function viewAny(User $user): bool { return $this->isShared($user); }
    public function view(User $user, Inventario $modelInstance): bool { return $this->isShared($user); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, Inventario $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, Inventario $modelInstance): bool { return $this->isAdmin($user); }
    public function restore(User $user, Inventario $modelInstance): bool { return $this->isAdmin($user); }
    public function forceDelete(User $user, Inventario $modelInstance): bool { return $this->isAdmin($user); }
}
