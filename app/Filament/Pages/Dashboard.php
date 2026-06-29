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
        // Total de activos
        $totalActivos = Inventario::count();

        // Activos por estado
        $activosPorEstado = Inventario::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        // Mantenimientos pendientes (buscando 'Pendiente' o 'Solicitado')
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Pendiente', 'Solicitado'])->count();

        // Materiales con stock bajo
        $materialesStockBajo = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();

        // Inventario por ubicación/departamento
        $inventarioPorUbicacion = Inventario::selectRaw('ubicacion_fisica, COUNT(*) as total')
            ->groupBy('ubicacion_fisica')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // Actividad reciente: Primero intentar BitacoraSistema, si no hay datos usar Solicitud
        $actividadReciente = BitacoraSistema::with('usuario')->latest('fecha_hora')->limit(5)->get();
        $tipoActividad = 'bitacora';
        if ($actividadReciente->isEmpty()) {
            $actividadReciente = Solicitud::with('usuario')->latest()->limit(5)->get();
            $tipoActividad = 'solicitud';
        }

        // Solicitudes pendientes
        $solicitudesPendientes = Solicitud::with(['usuario', 'receptor'])
            ->where('estado', 'Pendiente')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'totalActivos' => $totalActivos,
            'activosPorEstado' => $activosPorEstado,
            'mantenimientosPendientes' => $mantenimientosPendientes,
            'materialesStockBajo' => $materialesStockBajo,
            'inventarioPorUbicacion' => $inventarioPorUbicacion,
            'actividadReciente' => $actividadReciente,
            'tipoActividad' => $tipoActividad,
            'solicitudesPendientes' => $solicitudesPendientes,
        ];
    }
}