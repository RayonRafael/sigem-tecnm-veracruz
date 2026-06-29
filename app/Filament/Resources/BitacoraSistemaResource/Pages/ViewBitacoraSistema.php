<?php

namespace App\Filament\Resources\BitacoraSistemaResource\Pages;

use App\Filament\Resources\BitacoraSistemaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBitacoraSistema extends ViewRecord
{
    protected static string $resource = BitacoraSistemaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
