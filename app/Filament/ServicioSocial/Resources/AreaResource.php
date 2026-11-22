<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\AreaResource as BaseAreaResource;

class AreaResource extends BaseAreaResource
{
    use ConCatalogoSoloLectura;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
}
