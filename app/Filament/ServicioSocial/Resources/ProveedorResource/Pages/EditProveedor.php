<?php

namespace App\Filament\ServicioSocial\Resources\ProveedorResource\Pages;

use App\Filament\ServicioSocial\Resources\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
