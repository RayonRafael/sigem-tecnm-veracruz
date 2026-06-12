<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalActivosWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total de Activos', Inventario::count())
                ->description('Equipos registrados')
                ->color('primary'),
        ];
    }
}