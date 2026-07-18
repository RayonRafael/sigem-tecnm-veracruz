<?php

namespace App\Filament\Pages;

use App\Models\Area;
use App\Models\BitacoraSistema;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\MarcaMaterial;
use App\Models\Material;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\UnidadMedida;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;

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
        // 1. Welcome Card Stats
        $totalActivos = Inventario::count();
        $activosBueno = Inventario::whereIn('estado', ['Bueno', 'Operativo'])->count();
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Pendiente', 'Solicitado'])->count();
        $materialesStockBajoCount = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();

        // 2. Actividad Reciente (5 items)
        $actividadReciente = BitacoraSistema::with('usuario')->latest('fecha_hora')->limit(5)->get();

        // 3. Inventario (completos y limitados)
        $inventariosCompletos = Inventario::with(['material', 'material.marca', 'material.tipo'])->latest('created_at')->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        // 4. Solicitudes (completas y limitadas)
        $solicitudesCompletas = Solicitud::with('usuario')->where('estado', 'Pendiente')->latest('created_at')->get();
        $solicitudesRecientes = $solicitudesCompletas->take(3);

        // 5. Mantenimiento (completos y limitados)
        $mantenimientosCompletos = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])->whereIn('estado', ['Pendiente', 'Solicitado'])->latest('created_at')->get();
        $mantenimientosRecientes = $mantenimientosCompletos->take(3);

        // 6. Catálogos Completos para los Modales
        $departamentosList = Departamento::all();
        $materialesList = Material::with(['tipo', 'unidad'])->get();
        $areasList = Area::with('departamento')->get();
        $marcasList = MarcaMaterial::withCount('materiales')->get();
        $tiposList = TipoMaterial::all();
        $unidadesList = UnidadMedida::all();
        $proveedoresList = Proveedor::all();
        $receptoresList = Receptor::with('area.departamento')->get();
        $usuariosList = User::all();

        return [
            'totalActivos' => $totalActivos,
            'activosBueno' => $activosBueno,
            'mantenimientosPendientes' => $mantenimientosPendientes,
            'materialesStockBajoCount' => $materialesStockBajoCount,
            
            'actividadReciente' => $actividadReciente,
            'inventariosRecientes' => $inventariosRecientes,
            'solicitudesRecientes' => $solicitudesRecientes,
            'mantenimientosRecientes' => $mantenimientosRecientes,

            // Variables para modales completos
            'inventariosCompletos' => $inventariosCompletos,
            'solicitudesCompletas' => $solicitudesCompletas,
            'mantenimientosCompletos' => $mantenimientosCompletos,
            
            'departamentosList' => $departamentosList,
            'materialesList' => $materialesList,
            'areasList' => $areasList,
            'marcasList' => $marcasList,
            'tiposList' => $tiposList,
            'unidadesList' => $unidadesList,
            'proveedoresList' => $proveedoresList,
            'receptoresList' => $receptoresList,
            'usuariosList' => $usuariosList,
        ];
    }
}
