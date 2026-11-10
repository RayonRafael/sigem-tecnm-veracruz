<?php

namespace App\Filament\ServicioSocial\Resources\InventarioResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Concerns\ConSlideOver;
use App\Filament\ServicioSocial\Resources\InventarioResource;
use App\Models\Inventario;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventarios extends ListRecords
{
    use ConDashboardBreadcrumb;
    use ConSlideOver;

    protected static string $resource = InventarioResource::class;

    public function getTitle(): string
    {
        return '💻 Inventario general';
    }

    public function getSubheading(): ?string
    {
        return 'Busca, filtra y gestiona los equipos registrados en el sistema.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label("\u{FF0B} Registrar equipo")
                ->slideOver()
                ->form(\App\Filament\Resources\InventarioResource::getMockupFormSchema())
                ->modalSubmitActionLabel('Registrar')
                ->modalCancelActionLabel('Cancelar')
                ->extraAttributes([
                    'class' => 'sigem-btn-primary',
                    'title' => 'Registrar un nuevo equipo en el inventario',
                ])
                ->using(fn (array $data): Inventario => \App\Filament\Resources\InventarioResource::registrarMultiplesEquipos($data))
                ->successNotificationTitle(fn (?Inventario $record, Actions\CreateAction $action): string => \App\Filament\Resources\InventarioResource::obtenerMensajeExito($record ?? $action->getRecord())),
        ];
    }

    protected function configureCreateAction(Actions\CreateAction | \Filament\Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        $action->url(null);
        $action->slideOver();
        $action->form(\App\Filament\Resources\InventarioResource::getMockupFormSchema());
        $action->modalSubmitActionLabel('Registrar');
        $action->modalCancelActionLabel('Cancelar');
        $action->using(fn (array $data): Inventario => \App\Filament\Resources\InventarioResource::registrarMultiplesEquipos($data));
        $action->successNotificationTitle(fn (?Inventario $record, $act): string => \App\Filament\Resources\InventarioResource::obtenerMensajeExito($record ?? (method_exists($act, 'getRecord') ? $act->getRecord() : null)));
    }

    protected function mutateSlideOverData(array $data): array
    {
        $data['aprobado'] = false;
        $data['estado_registro'] = 'Pendiente';
        $data['aprobado_por'] = null;
        $data['id_usuario'] = auth()->id();

        return $data;
    }
}
