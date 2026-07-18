<?php

namespace App\Filament\ServicioSocial\Resources\MaterialResource\Pages;

use App\Filament\ServicioSocial\Resources\MaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterial extends EditRecord
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
