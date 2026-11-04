<?php

namespace App\Filament\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\MantenimientoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMantenimiento extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MantenimientoResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return '＋ Nuevo Mantenimiento';
    }

    public function getSubheading(): ?string
    {
        return 'Reporta un equipo con fallas. El administrador lo revisará y autorizará el servicio.';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Enviar reporte →')
            ->requiresConfirmation()
            ->modalHeading('Confirmar envío de reporte')
            ->modalDescription('Tu reporte se enviará al administrador para su revisión.')
            ->modalSubmitActionLabel('Sí, enviar reporte');
    }

    protected function getSubmitFormAction(): Action
    {
        return $this->getCreateFormAction();
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Cancelar')
            ->url(static::getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id_usuario_solicita'] = auth()->id();
        $data['tipo_servicio'] = $data['tipo_servicio'] ?? 'Servicio Social';
        $data['estado'] = 'Pendiente Revision Admin';
        $data['fecha_solicitud'] = $data['fecha_solicitud'] ?? now();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Reporte de mantenimiento enviado';
    }
}
