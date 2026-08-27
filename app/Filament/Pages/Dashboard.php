<?php

namespace App\Filament\Pages;

use App\Models\BitacoraSistema;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Solicitud;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

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
        ];
    }

    public function getViewData(): array
    {
        $totalActivos = Inventario::count();
        $activosBueno = Inventario::whereIn('estado', ['Disponible', 'Asignado'])->count();
        $porcentajeBuenEstado = $totalActivos > 0 ? round(($activosBueno / $totalActivos) * 100) : 0;
        
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Pendiente Revision Admin', 'Solicitado'])->count();
        $mantenimientosTotales = Mantenimiento::count();
        
        $materialesStockBajoCount = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();
        $solicitudesPendientes = Solicitud::where('estado', 'Pendiente')->count();
        
        $creadosEsteMes = Inventario::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)->count();

        $actividadReciente = BitacoraSistema::with('usuario')->latest('fecha_hora')->limit(5)->get();

        return [
            'totalActivos' => $totalActivos,
            'activosBueno' => $activosBueno,
            'porcentajeBuenEstado' => $porcentajeBuenEstado,
            'mantenimientosPendientes' => $mantenimientosPendientes,
            'mantenimientosTotales' => $mantenimientosTotales,
            'materialesStockBajoCount' => $materialesStockBajoCount,
            'solicitudesPendientes' => $solicitudesPendientes,
            'creadosEsteMes' => $creadosEsteMes,
            
            'actividadReciente' => $actividadReciente,
        ];
    }
}
