<?php

namespace App\Filament\ServicioSocial\Resources\MaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\MaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterials extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MaterialResource::class;

    public function getTitle(): string
    {
        return '📦 Catálogo de Materiales';
    }

    public function getSubheading(): ?string
    {
        return 'Catálogo de materiales y equipos disponibles en el inventario.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
