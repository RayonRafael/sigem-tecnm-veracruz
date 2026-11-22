<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ConCatalogoPolicy;
use Illuminate\Database\Eloquent\Model;

class UserPolicy
{
    use ConCatalogoPolicy;

    public function delete(User $user, Model $modelInstance): bool
    {
        if ($user->id === $modelInstance->getKey()) {
            return false; // No puede eliminarse a sí mismo
        }

        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, Model $modelInstance): bool
    {
        if ($user->id === $modelInstance->getKey()) {
            return false; // No puede eliminarse a sí mismo
        }

        return $this->isAdmin($user);
    }
}
