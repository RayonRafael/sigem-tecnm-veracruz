<?php

namespace App\Policies;

use App\Policies\Concerns\ConCatalogoPolicy;

class AreaPolicy
{
    use ConCatalogoPolicy;

    protected function permitirLecturaServicioSocial(): bool
    {
        return true;
    }
}
