<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\MarcaMaterialResource as BaseMarcaMaterialResource;

class MarcaMaterialResource extends BaseMarcaMaterialResource
{
    use ConCatalogoSoloLectura;

    protected static ?string $navigationLabel = 'Marcas de material';
}
