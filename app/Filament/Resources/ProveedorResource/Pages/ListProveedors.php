<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\ProveedorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProveedors extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = ProveedorResource::class;

    public function getTitle(): string
    {
        return '🚚 Proveedores';
    }

    public function getSubheading(): ?string
    {
        return 'Proveedores registrados para compras y mantenimiento.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
