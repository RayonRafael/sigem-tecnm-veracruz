<?php

namespace App\Filament\ServicioSocial\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\InventarioResource;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return \App\Filament\Resources\InventarioResource::hidratarDatosEdicion($data, $this->record);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return \App\Filament\Resources\InventarioResource::guardarCambiosEquipo($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Registro actualizado exitosamente';
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Registro eliminado exitosamente';
    }
}
