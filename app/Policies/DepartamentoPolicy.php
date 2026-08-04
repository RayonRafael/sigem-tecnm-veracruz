<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Departamento;

class DepartamentoPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole('Administrador') || $user->tipo_usuario === 'Administrador';
    }

    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Departamento $modelInstance): bool { return true; }
    public function create(User $user): bool { return $this->isAdmin($user); }
    public function update(User $user, Departamento $modelInstance): bool { return $this->isAdmin($user); }
    public function delete(User $user, Departamento $modelInstance): bool { return $this->isAdmin($user); }
}
