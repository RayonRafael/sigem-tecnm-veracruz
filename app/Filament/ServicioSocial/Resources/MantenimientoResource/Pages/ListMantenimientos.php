<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = MantenimientoResource::class;

    public function getTitle(): string
    {
        return '🔧 Mis Mantenimientos';
    }

    public function getSubheading(): ?string
    {
        return 'Reportes de equipo dañado que has enviado. Da clic en la fila o en el ojo 👁 para desplegar el detalle sin salir de la lista.';
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
