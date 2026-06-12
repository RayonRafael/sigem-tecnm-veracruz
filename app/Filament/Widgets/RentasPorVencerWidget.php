<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentasPorVencerWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    protected function getStats(): array
    {
        $rentasPorVencer = Inventario::where('tipo_propiedad', 'Rentado')
            ->where('estado', '!=', 'Devuelto a Proveedor')
            ->where('fecha_fin_renta', '<=', Carbon::now()->addDays(30))
            ->where('fecha_fin_renta', '>=', Carbon::now())
            ->count();

        return [
            Stat::make('Rentas por Vencer', $rentasPorVencer)
                ->description('En los próximos 30 días')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),
        ];
    }
}