<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DisponiblesWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Disponibles', Inventario::where('estado', 'Disponible')->count())
                ->description('Listos para asignar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}