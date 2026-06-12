<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    /**
     * StatsOverviewWidget::getColumns() solo acepta int
     * 4 = 4 columnas (1 fila con las 4 stats)
     * 2 = 2 columnas (2x2 grid)
     */
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $totalActivos = Inventario::count();
        $disponibles = Inventario::where('estado', 'Disponible')->count();
        
        $rentasPorVencer = Inventario::where('tipo_propiedad', 'Rentado')
            ->where('estado', '!=', 'Devuelto a Proveedor')
            ->where('fecha_fin_renta', '<=', Carbon::now()->addDays(30))
            ->where('fecha_fin_renta', '>=', Carbon::now())
            ->count();
        
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', [
            'Solicitado', 
            'En proceso', 
            'Pendiente Revision Admin'
        ])->count();

        return [
            Stat::make('Total de Activos', $totalActivos)
                ->description('Equipos registrados')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('primary'),
                
            Stat::make('Disponibles', $disponibles)
                ->description('Listos para asignar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Rentas por Vencer', $rentasPorVencer)
                ->description('Próximos 30 días')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
                
            Stat::make('Mantenimientos Pendientes', $mantenimientosPendientes)
                ->description('Por atender')
                ->descriptionIcon('heroicon-m-wrench')
                ->color('danger'),
        ];
    }
}