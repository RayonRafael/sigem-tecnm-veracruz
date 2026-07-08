<?php

namespace App\Filament\ServicioSocial\Resources\MarcaMaterialResource\Pages;

use App\Filament\ServicioSocial\Resources\MarcaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarcaMaterial extends EditRecord
{
    protected static string $resource = MarcaMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
