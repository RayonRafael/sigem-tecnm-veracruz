<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;

use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    use ConDashboardBreadcrumb;
    protected static string $resource = InventarioResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }
}
