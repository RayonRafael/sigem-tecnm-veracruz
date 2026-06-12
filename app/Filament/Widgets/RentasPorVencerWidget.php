<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentasPorVencerWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $rentasPorVencer = Inventario::where('tipo_propiedad', 'Rentado')
            ->whereNotNull('fecha_fin_renta')
            ->where('fecha_fin_renta', '<=', Carbon::now()->addDays(30))
            ->where('estado', '!=', 'Devuelto a Proveedor')
            ->count();

        return [
            Stat::make('Rentas por Vencer', $rentasPorVencer)
                ->description('Próximos 30 días')
                ->color('warning'),
        ];
    }
}