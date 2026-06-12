<?php

namespace App\Filament\Resources\MarcaMaterialResource\Pages;

use App\Filament\Resources\MarcaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarcaMaterials extends ListRecords
{
    protected static string $resource = MarcaMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
