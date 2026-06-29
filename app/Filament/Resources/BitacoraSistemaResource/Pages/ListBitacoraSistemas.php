<?php

namespace App\Filament\Resources\BitacoraSistemaResource\Pages;

use App\Filament\Resources\BitacoraSistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBitacoraSistemas extends ListRecords
{
    protected static string $resource = BitacoraSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
