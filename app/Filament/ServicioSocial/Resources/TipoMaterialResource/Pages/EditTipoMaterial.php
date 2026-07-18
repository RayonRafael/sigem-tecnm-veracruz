<?php

namespace App\Filament\ServicioSocial\Resources\TipoMaterialResource\Pages;

use App\Filament\ServicioSocial\Resources\TipoMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTipoMaterial extends EditRecord
{
    protected static string $resource = TipoMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }
}
