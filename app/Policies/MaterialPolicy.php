<?php

namespace App\Policies;

use App\Policies\Concerns\ConCatalogoPolicy;

class MaterialPolicy
{
    use ConCatalogoPolicy;

    protected function permitirLecturaServicioSocial(): bool
    {
        return true;
    }
}
