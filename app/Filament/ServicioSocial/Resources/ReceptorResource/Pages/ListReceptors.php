<?php

namespace App\Filament\ServicioSocial\Resources\ReceptorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\ReceptorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceptors extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
