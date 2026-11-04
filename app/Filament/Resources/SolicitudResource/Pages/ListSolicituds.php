<?php

namespace App\Filament\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\SolicitudResource;
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
        return 'Peticiones de salida de material. Clic en el ojo 👁 o en la fila para desplegar el detalle en el panel lateral.';
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
