<?php

namespace App\Filament\ServicioSocial\Resources\MaterialResource\Pages;

use App\Filament\ServicioSocial\Resources\MaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    protected static string $resource = MaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
