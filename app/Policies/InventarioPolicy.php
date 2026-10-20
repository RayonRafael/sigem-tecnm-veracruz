<?php

namespace App\Policies;

use App\Models\Inventario;
use App\Models\User;

class InventarioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('inventario.ver');
    }

    public function view(User $user, Inventario $inventario): bool
    {
        return $user->hasPermissionTo('inventario.ver');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('inventario.crear');
    }

    public function update(User $user, Inventario $inventario): bool
    {
        return $user->hasPermissionTo('inventario.editar');
    }

    public function delete(User $user, Inventario $inventario): bool
    {
        return $user->hasPermissionTo('inventario.eliminar');
    }

    public function restore(User $user, Inventario $inventario): bool
    {
        return $user->hasPermissionTo('inventario.eliminar');
    }

    public function forceDelete(User $user, Inventario $inventario): bool
    {
        return $user->hasPermissionTo('inventario.eliminar');
    }
}
