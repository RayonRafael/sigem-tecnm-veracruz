<?php

namespace App\Filament\ServicioSocial\Resources\UnidadMedidaResource\Pages;

use App\Filament\ServicioSocial\Resources\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnidadMedida extends CreateRecord
{
    protected static string $resource = UnidadMedidaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
