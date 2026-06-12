<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\ActivosPorEstadoWidget;
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
            'sm'      => 1,
            'md'      => 2,
            'lg'      => 2,
            'xl'      => 2,
        ];
    }
    
    public function getWidgets(): array
    {
        return [
            StatsOverview::class,
            ActivosPorEstadoWidget::class,
        ];
    }
}