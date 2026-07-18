<?php

namespace App\Filament\ServicioSocial\Resources\AreaResource\Pages;

use App\Filament\ServicioSocial\Resources\AreaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    protected static string $resource = AreaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
