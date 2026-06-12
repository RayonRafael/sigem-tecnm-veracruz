<?php

namespace App\Filament\Resources\TipoMaterialResource\Pages;

use App\Filament\Resources\TipoMaterialResource;
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
}
