<?php

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\ReceptorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceptors extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = ReceptorResource::class;

    public function getTitle(): string
    {
        return '👤 Receptores';
    }

    public function getSubheading(): ?string
    {
        return 'Personas que reciben material y equipos del centro de cómputo.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
