<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Receptor;

class ReceptorPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(\App\Enums\RoleEnum::ADMIN->value) || $user->tipo_usuario === \App\Enums\RoleEnum::ADMIN->value;
    }

    private function isShared(User $user): bool
    {
        return $this->isAdmin($user) || $user->hasRole(\App\Enums\RoleEnum::SERVICIO_SOCIAL->value) || $user->tipo_usuario === 'Servicio';
    }

    public function viewAny(User $user): bool { return $this->isShared($user); }
    public function view(User $user, Receptor $modelInstance): bool { return $this->isShared($user); }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, Receptor $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, Receptor $modelInstance): bool { return $this->isAdmin($user); }
    public function restore(User $user, Receptor $modelInstance): bool { return $this->isAdmin($user); }
    public function forceDelete(User $user, Receptor $modelInstance): bool { return $this->isAdmin($user); }
}
