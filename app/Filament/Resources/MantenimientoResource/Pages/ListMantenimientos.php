<?php

namespace App\Filament\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MantenimientoResource::class;

    public function getTitle(): string
    {
        return '🔧 Mantenimiento';
    }

    public function getSubheading(): ?string
    {
        return 'Reportes de servicio. Clic en el ojo 👁 o en la fila para desplegar el detalle en el panel lateral.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('＋ Nuevo mantenimiento')
                ->modalHeading('＋ Nuevo Mantenimiento')
                ->successNotificationTitle('Reporte de mantenimiento enviado'),
        ];
    }

    protected function mutateSlideOverCreateData(array $data): array
    {
        $data['id_usuario_solicita'] = auth()->id();
        $data['tipo_servicio'] = $data['tipo_servicio'] ?? 'Servicio Social';
        $data['estado'] = 'Pendiente Revision Admin';
        $data['fecha_solicitud'] = $data['fecha_solicitud'] ?? now();

        return $data;
    }
}
