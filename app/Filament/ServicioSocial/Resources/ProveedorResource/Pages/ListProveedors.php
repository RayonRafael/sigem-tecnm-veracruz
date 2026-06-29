<?php

namespace App\Filament\ServicioSocial\Resources\ProveedorResource\Pages;

use App\Filament\ServicioSocial\Resources\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProveedors extends ListRecords
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
