<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\MaterialResource as BaseMaterialResource;

class MaterialResource extends BaseMaterialResource
{
    use ConCatalogoSoloLectura;
}
