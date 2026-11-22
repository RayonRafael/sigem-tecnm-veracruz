<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ConDashboardComun;
use App\Models\Inventario;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\HtmlString;

class Dashboard extends BaseDashboard
{
    use ConDashboardComun;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Panel principal';

    protected static ?string $navigationLabel = 'Panel principal';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'SIGEM - TecNM Veracruz';

    protected static string $view = 'filament.pages.dashboard';

    public function cambiarAreaAction(): Action
    {
        return $this->construirCambioAreaAction(true);
    }

    public function cambiarArea($id): void
    {
        $this->mountAction('cambiarArea', ['id' => $id, 'record' => $id]);
    }

    public function darBajaAction(): Action
    {
        return $this->construirBajaAction(true);
    }

    public function darBaja($id): void
    {
        $this->mountAction('darBaja', ['id' => $id, 'record' => $id]);
    }

    public function eliminarEquipoAction(): Action
    {
        return DeleteAction::make('eliminarEquipo')
            ->label('Eliminar equipo')
            ->modalHeading(fn (?Inventario $record) => '¿Eliminar ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo') . '?')
            ->modalDescription(fn (?Inventario $record) => new HtmlString(
                'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · Esta acción no se puede deshacer'
            ))
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;

                return $id ? Inventario::find($id) : null;
            })
            ->successNotificationTitle('Registro eliminado correctamente');
    }

    public function eliminarEquipo($id): void
    {
        $this->mountAction('eliminarEquipo', ['id' => $id, 'record' => $id]);
    }

    public function getViewData(): array
    {
        return array_merge(
            $this->obtenerEstadisticasAdmin(),
            $this->obtenerDatosTablaYEstadoComun()
        );
    }
}
