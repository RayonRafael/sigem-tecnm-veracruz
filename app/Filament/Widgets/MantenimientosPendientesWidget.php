<?php

namespace App\Filament\Widgets;

use App\Models\Mantenimiento;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MantenimientosPendientesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        $pendientes = Mantenimiento::whereIn('estado', ['Solicitado', 'En proceso', 'Pendiente Revision Admin'])->count();

        return [
            Stat::make('Mantenimientos Pendientes', $pendientes)
                ->description('Requieren atención')
                ->descriptionIcon('heroicon-m-wrench')
                ->color('danger'),
        ];
    }
}