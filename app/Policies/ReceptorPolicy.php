<?php

namespace App\Policies;

use App\Policies\Concerns\ConCatalogoPolicy;

class ReceptorPolicy
{
    use ConCatalogoPolicy;

    protected function permitirLecturaServicioSocial(): bool
    {
        return true;
    }
}
