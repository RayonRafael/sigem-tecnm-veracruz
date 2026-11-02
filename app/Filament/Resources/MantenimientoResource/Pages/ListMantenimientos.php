<?php

namespace App\Filament\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MantenimientoResource::class;

    public function getTitle(): string
    {
        return '🔧 Mantenimientos';
    }

    public function getSubheading(): ?string
    {
        return 'Reportes de servicio técnico. Clic en el ojo 👁 o en la fila para desplegar el detalle en el panel lateral.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
