<?php

namespace App\Filament\Widgets;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use Filament\Widgets\Widget;
use Carbon\Carbon;

class AlertasWidget extends Widget
{
    protected static ?string $heading = 'Alertas del Sistema';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 8;
    protected static string $view = 'filament.widgets.alertas-widget';

    protected function getViewData(): array
    {
        // Alertas de inventario dañado
        $inventarioDanado = Inventario::where('estado', 'Dañado')->count();
        
        // Alertas de rentas próximas a vencer
        $rentasProximas = Inventario::where('tipo_propiedad', 'Rentado')
            ->where('estado', '!=', 'Devuelto a Proveedor')
            ->where('fecha_fin_renta', '<=', Carbon::now()->addDays(7))
            ->where('fecha_fin_renta', '>=', Carbon::now())
            ->count();
        
        // Mantenimientos críticos
        $mantenimientosCriticos = Mantenimiento::whereIn('estado', [
            'Solicitado',
            'Pendiente Revision Admin'
        ])->count();

        return [
            'inventarioDanado' => $inventarioDanado,
            'rentasProximas' => $rentasProximas,
            'mantenimientosCriticos' => $mantenimientosCriticos,
        ];
    }
}
