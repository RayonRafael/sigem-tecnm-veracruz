<?php

namespace App\Filament\Resources\MarcaMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\MarcaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarcaMaterials extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MarcaMaterialResource::class;

    public function getTitle(): string
    {
        return '🏷️ Marcas de Material';
    }

    public function getSubheading(): ?string
    {
        return 'Marcas registradas para la clasificación de consumibles y equipos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
