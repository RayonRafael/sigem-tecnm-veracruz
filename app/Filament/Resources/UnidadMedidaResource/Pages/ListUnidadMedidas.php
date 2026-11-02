<?php

namespace App\Filament\Resources\UnidadMedidaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnidadMedidas extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = UnidadMedidaResource::class;

    public function getTitle(): string
    {
        return '⚖️ Unidades de Medida';
    }

    public function getSubheading(): ?string
    {
        return 'Unidades de medida para el control de inventario y consumibles.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
