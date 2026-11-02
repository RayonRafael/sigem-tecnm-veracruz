<?php

namespace App\Filament\ServicioSocial\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = InventarioResource::class;

    public function getTitle(): string
    {
        return '💻 Inventario general';
    }

    public function getSubheading(): ?string
    {
        return 'Busca, filtra y gestiona los equipos registrados en el sistema.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function mutateSlideOverData(array $data): array
    {
        $data['aprobado'] = false;
        $data['estado_registro'] = 'Pendiente';
        $data['aprobado_por'] = null;
        $data['id_usuario'] = auth()->id();

        return $data;
    }
}
