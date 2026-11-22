<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\Concerns\ConCatalogoSoloLectura;
use App\Filament\Resources\UnidadMedidaResource as BaseUnidadMedidaResource;

class UnidadMedidaResource extends BaseUnidadMedidaResource
{
    use ConCatalogoSoloLectura;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
}
