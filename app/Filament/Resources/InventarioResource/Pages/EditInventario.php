<?php

namespace App\Filament\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\InventarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventario extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = InventarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return InventarioResource::hidratarDatosEdicion($data, $this->record);
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return InventarioResource::guardarCambiosEquipo($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return url('/admin');
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
