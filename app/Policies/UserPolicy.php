<?php

namespace App\Policies;

use App\Models\User;
use App\Models\User;

class UserPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasPermissionTo(\App\Enums\RoleEnum::PERM_ACCESS_ADMIN) || $user->tipo_usuario === \App\Enums\RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool { return $this->isAdmin($user); }
    public function view(User $user, User $modelInstance): bool { return $this->isAdmin($user); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, User $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, User $modelInstance): bool { return $this->isAdmin($user); }
    public function restore(User $user, User $modelInstance): bool { return $this->isAdmin($user); }
    public function forceDelete(User $user, User $modelInstance): bool { return $this->isAdmin($user); }
}
