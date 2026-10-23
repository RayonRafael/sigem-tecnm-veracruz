<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
