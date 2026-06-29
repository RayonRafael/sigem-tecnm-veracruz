<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 12;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $totalActivos = Inventario::count();
        $disponibles = Inventario::where('estado', 'Disponible')->count();
        
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', [
            'Solicitado', 
            'En proceso', 
            'Pendiente Revision Admin'
        ])->count();

        $stockBajo = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();

        return [
            Stat::make('Total de Activos', $totalActivos)
                ->description('Equipos registrados en el sistema')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),
                
            Stat::make('Activos en Buen Estado', $disponibles)
                ->description('Equipos disponibles y operativos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Mantenimientos Pendientes', $mantenimientosPendientes)
                ->description('Solicitudes en espera o proceso')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
                
            Stat::make('Materiales con Stock Bajo', $stockBajo)
                ->description('Requieren reabastecimiento')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}