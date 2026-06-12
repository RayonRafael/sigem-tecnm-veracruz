<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalActivosWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Activos', Inventario::count())
                ->description('Equipos registrados')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('primary'),
        ];
    }
}