<?php

namespace App\Filament\Resources\DepartamentoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\DepartamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartamentos extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = DepartamentoResource::class;

    public function getTitle(): string
    {
        return '🏢 Departamentos';
    }

    public function getSubheading(): ?string
    {
        return 'Departamentos y divisiones académicas del instituto.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
