<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\SolicitudResource;
use App\Models\DetalleSolicitud;
use App\Models\Receptor;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSolicitud extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = SolicitudResource::class;

    protected static bool $canCreateAnother = false;

    protected ?int $productoIdParaDetalle = null;

    public function getTitle(): string
    {
        return '＋ Nueva Solicitud';
    }

    public function getSubheading(): ?string
    {
        return 'Completa la información y envíala. El administrador la revisará y verás el cambio de estado aquí.';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Enviar solicitud →')
            ->requiresConfirmation()
            ->modalHeading('Confirmar envío de solicitud')
            ->modalDescription('Tu registro se enviará al administrador para su revisión.')
            ->modalSubmitActionLabel('Sí, enviar solicitud');
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
        $tipo = $data['tipo_solicitud'] ?? 'Salida';
        $data['tipo_movimiento'] = match ($tipo) {
            'Salida' => 'Asignacion Permanente',
            'Entrada' => 'Asignacion Temporal',
            'Traslado' => 'Renta Externa',
            default => 'Asignacion Permanente',
        };
        unset($data['tipo_solicitud']);

        if (isset($data['id_producto'])) {
            $this->productoIdParaDetalle = (int) $data['id_producto'];
            unset($data['id_producto']);
        }

        $data['id_usuario'] = auth()->id();
        $data['estado'] = 'Pendiente';
        $data['id_receptor'] = $data['id_receptor'] ?? (Receptor::value('id_receptor') ?? 1);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->productoIdParaDetalle) {
            DetalleSolicitud::create([
                'id_solicitud' => $this->record->id_solicitud,
                'id_producto' => $this->productoIdParaDetalle,
                'cantidad' => 1,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Solicitud enviada exitosamente';
    }
}
