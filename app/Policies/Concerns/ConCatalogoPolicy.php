<?php

namespace App\Policies\Concerns;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ConCatalogoPolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->hasPermissionTo(RoleEnum::PERM_ACCESS_ADMIN)
            || $user->tipo_usuario === RoleEnum::ADMIN->value;
    }

    protected function isShared(User $user): bool
    {
        return $this->isAdmin($user)
            || $user->hasPermissionTo(RoleEnum::PERM_ACCESS_SERVICIO)
            || $user->tipo_usuario === RoleEnum::SERVICIO_TIPO->value;
    }

    protected function permitirLecturaServicioSocial(): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return $this->permitirLecturaServicioSocial()
            ? $this->isShared($user)
            : $this->isAdmin($user);
    }

    public function view(User $user, Model $modelInstance): bool
    {
        return $this->permitirLecturaServicioSocial()
            ? $this->isShared($user)
            : $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user);
    }
}
