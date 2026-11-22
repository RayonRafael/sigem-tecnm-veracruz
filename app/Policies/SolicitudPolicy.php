<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ConCatalogoPolicy;
use Illuminate\Database\Eloquent\Model;

class SolicitudPolicy
{
    use ConCatalogoPolicy;

    protected function permitirLecturaServicioSocial(): bool { return true; }

    public function view(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user) || ($this->isShared($user) && $modelInstance->id_usuario === $user->id);
    }

    public function create(User $user): bool { return $this->isShared($user); }

    public function update(User $user, Model $modelInstance): bool
    {
        return $this->isAdmin($user)
            || ($this->isShared($user) && $modelInstance->id_usuario === $user->id && $modelInstance->estado === 'Pendiente');
    }
}
