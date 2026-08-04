<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Mantenimiento;

class MantenimientoPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador';
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Mantenimiento $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario_solicita === $user->id;
    }
    public function create(User $user): bool { return true; }
    public function update(User $user, Mantenimiento $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario_solicita === $user->id;
    }
    public function delete(User $user, Mantenimiento $modelInstance): bool {
        if ($this->isAdmin($user)) return true;
        return $modelInstance->id_usuario_solicita === $user->id;
    }
}
