<?php

namespace App\Policies;

use App\Models\Mantenimiento;
use App\Models\User;

class MantenimientoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mantenimiento.ver');
    }

    public function view(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasPermissionTo('mantenimiento.ver')
            && $mantenimiento->id_usuario_solicita === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('mantenimiento.crear');
    }

    public function update(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasPermissionTo('mantenimiento.editar')
            && $mantenimiento->id_usuario_solicita === $user->id
            && $mantenimiento->estado === 'Solicitado';
    }

    public function delete(User $user, Mantenimiento $mantenimiento): bool
    {
        return $user->hasPermissionTo('mantenimiento.completar');
    }

    public function restore(User $user, Mantenimiento $mantenimiento): bool
    {
        return false;
    }

    public function forceDelete(User $user, Mantenimiento $mantenimiento): bool
    {
        return false;
    }
}
