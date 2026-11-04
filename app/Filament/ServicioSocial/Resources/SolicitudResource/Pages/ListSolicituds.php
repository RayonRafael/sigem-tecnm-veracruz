<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\SolicitudResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSolicituds extends ListRecords
{
    use ConDashboardBreadcrumb;

    protected static string $resource = SolicitudResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->query('abrir') === 'create') {
            $this->redirect(static::getResource()::getUrl('create'));
        }
    }

    public function getTitle(): string
    {
        return '📋 Solicitud de Material/Equipo';
    }

    public function getSubheading(): ?string
    {
        return 'Trámites que has enviado. Da clic en la fila o en el ojo 👁 para desplegar el detalle en el panel lateral.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('＋ Nueva solicitud')
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }
}
