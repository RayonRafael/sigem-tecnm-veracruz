<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    protected static string $resource = MantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
