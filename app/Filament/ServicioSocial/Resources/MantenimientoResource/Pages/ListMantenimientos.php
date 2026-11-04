<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMantenimientos extends ListRecords
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MantenimientoResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->query('abrir') === 'create') {
            $this->redirect(static::getResource()::getUrl('create'));
        }
    }

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
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }
}
