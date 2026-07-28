<?php

namespace App\Filament\Resources\UnidadMedidaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;

use App\Filament\Resources\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUnidadMedida extends CreateRecord
{
    use ConDashboardBreadcrumb;
    protected static string $resource = UnidadMedidaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }
}
