<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\ReceptorResource as BaseReceptorResource;

class ReceptorResource extends BaseReceptorResource
{
    use ConCatalogoSoloLectura;
}
