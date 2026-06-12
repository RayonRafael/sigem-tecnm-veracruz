<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ActivosPorEstadoWidget;
use App\Filament\Widgets\DisponiblesWidget;
use App\Filament\Widgets\MantenimientosPendientesWidget;
use App\Filament\Widgets\RentasPorVencerWidget;
use App\Filament\Widgets\TotalActivosWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -2;
    protected static ?string $title = 'SIGEM - TecNM Veracruz';
    
    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md'      => 2,
            'xl'      => 2,
        ];
    }
    
    public function getWidgets(): array
    {
        return [
            TotalActivosWidget::class,
            DisponiblesWidget::class,
            RentasPorVencerWidget::class,
            MantenimientosPendientesWidget::class,
            ActivosPorEstadoWidget::class,
        ];
    }
}