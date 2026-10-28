<?php

namespace App\Filament\ServicioSocial\Resources\AreaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\AreaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAreas extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = AreaResource::class;

    public function getTitle(): string
    {
        return '📍 Áreas';
    }

    public function getSubheading(): ?string
    {
        return 'Áreas, laboratorios y oficinas dependientes de los departamentos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
