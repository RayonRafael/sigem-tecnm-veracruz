<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\SolicitudResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicituds extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = SolicitudResource::class;

    public function getTitle(): string
    {
        return '📋 Lista de solicitudes de materiales';
    }

    public function getSubheading(): ?string
    {
        return 'Peticiones de salida de material. Clic en el ojo 👁 o en la fila para desplegar el detalle en el panel lateral.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
