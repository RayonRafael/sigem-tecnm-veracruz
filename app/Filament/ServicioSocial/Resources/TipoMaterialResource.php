<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\TipoMaterialResource as BaseTipoMaterialResource;

class TipoMaterialResource extends BaseTipoMaterialResource
{
    use ConCatalogoSoloLectura;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
}
