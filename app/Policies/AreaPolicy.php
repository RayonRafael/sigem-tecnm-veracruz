<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Area;

class AreaPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleEnum::ADMIN->value) || $user->tipo_usuario === \App\Enums\RoleEnum::ADMIN->value;
    }

    public function viewAny(User $user): bool { return $this->isAdmin($user); }
    public function view(User $user, Area $modelInstance): bool { return $this->isAdmin($user); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, Area $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, Area $modelInstance): bool { return $this->isAdmin($user); }
    public function restore(User $user, Area $modelInstance): bool { return $this->isAdmin($user); }
    public function forceDelete(User $user, Area $modelInstance): bool { return $this->isAdmin($user); }
}
