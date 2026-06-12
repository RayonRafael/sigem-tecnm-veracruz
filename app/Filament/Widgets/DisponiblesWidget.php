<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DisponiblesWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Disponibles', Inventario::where('estado', 'Disponible')->count())
                ->description('Listos para asignar')
                ->color('success'),
        ];
    }
}