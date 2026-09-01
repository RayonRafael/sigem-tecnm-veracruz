<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Proveedor;

class ProveedorPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleEnum::ADMIN->value) || $user->tipo_usuario === \App\Enums\RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool { return $this->isAdmin($user); }
    public function view(User $user, Proveedor $modelInstance): bool { return $this->isAdmin($user); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, Proveedor $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, Proveedor $modelInstance): bool { return $this->isAdmin($user); }
    public function restore(User $user, Proveedor $modelInstance): bool { return $this->isAdmin($user); }
    public function forceDelete(User $user, Proveedor $modelInstance): bool { return $this->isAdmin($user); }
}
