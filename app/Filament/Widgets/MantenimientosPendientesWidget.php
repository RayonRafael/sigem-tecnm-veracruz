<?php

namespace App\Filament\Widgets;

use App\Models\Mantenimiento;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MantenimientosPendientesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Solicitado', 'En proceso', 'Pendiente Revision Admin'])->count();

        return [
            Stat::make('Mantenimientos Pendientes', $mantenimientosPendientes)
                ->description('Por atender')
                ->color('danger'),
        ];
    }
}