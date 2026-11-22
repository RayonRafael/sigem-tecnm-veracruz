<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Filament\Concerns\ConDashboardComun;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    use ConDashboardComun;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Panel principal';
    protected static ?string $navigationGroup = 'Panel principal';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'SIGEM - Servicio Social';
    protected static string $view = 'filament.servicio-social.pages.dashboard';

    protected function esDashboardServicioSocial(): bool { return true; }

    public function solicitarCambioAreaAction(): Action { return $this->construirCambioAreaAction(false); }

    public function solicitarCambioArea($id): void { $this->mountAction('solicitarCambioArea', ['id' => $id, 'record' => $id]); }

    public function solicitarBajaAction(): Action { return $this->construirBajaAction(false); }

    public function solicitarBaja($id): void { $this->mountAction('solicitarBaja', ['id' => $id, 'record' => $id]); }

    public function getViewData(): array
    {
        return array_merge(
            $this->obtenerEstadisticasServicioSocial(),
            $this->obtenerDatosTablaYEstadoComun()
        );
    }
}
