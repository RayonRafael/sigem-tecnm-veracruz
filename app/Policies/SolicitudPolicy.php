<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Solicitud;

class SolicitudPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador';
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Solicitud $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario === $user->id;
    }
    public function create(User $user): bool { return true; }
    public function update(User $user, Solicitud $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario === $user->id;
    }
    public function delete(User $user, Solicitud $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario === $user->id;
    }
}
