<?php

namespace App\Filament\ServicioSocial\Resources\TipoMaterialResource\Pages;

use App\Filament\ServicioSocial\Resources\TipoMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoMaterial extends CreateRecord
{
    protected static string $resource = TipoMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
