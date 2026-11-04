<?php

namespace App\Filament\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\MantenimientoResource;
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
                ->url(fn (): string => static::getResource()::getUrl('create')),
        ];
    }
}
