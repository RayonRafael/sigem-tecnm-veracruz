<?php

namespace App\Filament\Pages;

use App\Models\BitacoraSistema;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Solicitud;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Panel principal';
    protected static ?string $navigationLabel = 'Panel principal';
    protected static ?int $navigationSort = -2;
    protected static ?string $title = 'SIGEM - TecNM Veracruz';
    protected static string $view = 'filament.pages.dashboard';
    
    public function getColumns(): int | string | array
    {
        return [
            'default' => 12,
            'sm'      => 12,
            'md'      => 12,
            'lg'      => 12,
            'xl'      => 12,
            '2xl'     => 12,
        ];
    }

    public function getViewData(): array
    {
        // 1. Welcome Card Stats
        $totalActivos = Inventario::count();
        $activosBueno = Inventario::whereIn('estado', ['Bueno', 'Operativo'])->count();
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Pendiente', 'Solicitado'])->count();
        $materialesStockBajoCount = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();

        // 2. Actividad Reciente (5 items)
        $actividadReciente = BitacoraSistema::with('usuario')->latest('fecha_hora')->limit(5)->get();

        // 3. Inventario (mini tabla 3 rows)
        $inventariosRecientes = Inventario::with(['material', 'material.marca', 'material.tipo'])
            ->latest('created_at')
            ->limit(3)
            ->get();

        // 4. Solicitudes (mini tabla 3 rows)
        $solicitudesRecientes = Solicitud::with('usuario')
            ->where('estado', 'Pendiente')
            ->latest('created_at')
            ->limit(3)
            ->get();

        // 5. Mantenimiento (mini tabla 3 rows)
        $mantenimientosRecientes = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])
            ->whereIn('estado', ['Pendiente', 'Solicitado'])
            ->latest('created_at')
            ->limit(3)
            ->get();

        return [
            'totalActivos' => $totalActivos,
            'activosBueno' => $activosBueno,
            'mantenimientosPendientes' => $mantenimientosPendientes,
            'materialesStockBajoCount' => $materialesStockBajoCount,
            'actividadReciente' => $actividadReciente,
            'inventariosRecientes' => $inventariosRecientes,
            'solicitudesRecientes' => $solicitudesRecientes,
            'mantenimientosRecientes' => $mantenimientosRecientes,
        ];
    }
}