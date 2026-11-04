<?php

namespace App\Filament\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\SolicitudResource;
use App\Models\DetalleSolicitud;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSolicitud extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = SolicitudResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tipo = $data['tipo_movimiento'] ?? '';
        $data['tipo_solicitud'] = match ($tipo) {
            'Asignacion Permanente', 'Salida' => 'Salida',
            'Asignacion Temporal', 'Entrada' => 'Entrada',
            'Renta Externa', 'Traslado' => 'Traslado',
            default => 'Salida',
        };

        $detalle = $this->record->detalles()->first();
        if ($detalle) {
            $data['id_producto'] = $detalle->id_producto;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['tipo_solicitud'])) {
            $data['tipo_movimiento'] = match ($data['tipo_solicitud']) {
                'Salida' => 'Asignacion Permanente',
                'Entrada' => 'Asignacion Temporal',
                'Traslado' => 'Renta Externa',
                default => 'Asignacion Permanente',
            };
            unset($data['tipo_solicitud']);
        }

        if (isset($data['id_producto'])) {
            $detalle = $this->record->detalles()->first();
            if ($detalle) {
                $detalle->update(['id_producto' => $data['id_producto']]);
            } else {
                DetalleSolicitud::create([
                    'id_solicitud' => $this->record->id_solicitud,
                    'id_producto' => $data['id_producto'],
                    'cantidad' => 1,
                ]);
            }
            unset($data['id_producto']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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
