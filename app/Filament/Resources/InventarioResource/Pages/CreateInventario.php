<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\InventarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventario extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = InventarioResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return InventarioResource::registrarMultiplesEquipos($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return InventarioResource::obtenerMensajeExito($this->record);
    }
}
