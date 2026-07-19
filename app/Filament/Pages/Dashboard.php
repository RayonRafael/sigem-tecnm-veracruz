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
        // 1. Welcome Card Stats
        $totalActivos = Inventario::count();
        $activosBueno = Inventario::whereIn('estado', ['Disponible', 'Asignado'])->count();
        $porcentajeBuenEstado = $totalActivos > 0 ? round(($activosBueno / $totalActivos) * 100) : 0;
        
        $mantenimientosPendientes = Mantenimiento::whereIn('estado', ['Pendiente Revision Admin', 'Solicitado'])->count();
        $mantenimientosTotales = Mantenimiento::count();
        
        $materialesStockBajoCount = Material::whereColumn('stock_actual', '<', 'stock_minimo')->count();
        $solicitudesPendientes = Solicitud::where('estado', 'Pendiente')->count();
        
        $creadosEsteMes = Inventario::whereMonth('created_at', Carbon::now()->month)
                                    ->whereYear('created_at', Carbon::now()->year)->count();

        // 2. Actividad Reciente (5 items)
        $actividadReciente = BitacoraSistema::with('usuario')->latest('fecha_hora')->limit(5)->get();

        // 3. Inventario (completos y limitados)
        $inventariosCompletos = Inventario::with(['material', 'material.marca', 'material.tipo', 'proveedor', 'usuario'])->latest('created_at')->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        // 4. Solicitudes (completas y limitadas)
        $solicitudesCompletas = Solicitud::with('usuario')->latest('created_at')->get();
        $solicitudesRecientes = $solicitudesCompletas->where('estado', 'Pendiente')->take(3);
        if ($solicitudesRecientes->isEmpty()) {
            $solicitudesRecientes = $solicitudesCompletas->take(3);
        }

        // 5. Mantenimiento (completos y limitados)
        $mantenimientosCompletos = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])->latest('created_at')->get();
        $mantenimientosRecientes = $mantenimientosCompletos->whereIn('estado', ['Pendiente', 'Solicitado', 'Pendiente Revision Admin', 'En proceso'])->take(3);
        if ($mantenimientosRecientes->isEmpty()) {
            $mantenimientosRecientes = $mantenimientosCompletos->take(3);
        }

        // Stats específicos para los mini módulos del nuevo layout
        $inventarioDisponibles = Inventario::where('estado', 'Disponible')->count();
        $inventarioAsignados = Inventario::where('estado', 'Asignado')->count();
        $inventarioEnMantenimiento = Inventario::where('estado', 'En Mantenimiento')->count();
        $inventarioDanados = Inventario::whereIn('estado', ['Dañado', 'Baja'])->count();

        $mantenimientoEnRevision = Mantenimiento::whereIn('estado', ['Pendiente Revision Admin', 'Solicitado'])->count();
        $mantenimientoEnProceso = Mantenimiento::where('estado', 'En proceso')->count();
        $mantenimientoCompletados = Mantenimiento::where('estado', 'Completado')->count();

        $solicitudesAutorizadas = Solicitud::where('estado', 'Autorizado')->count();
        $solicitudesRechazadas = Solicitud::where('estado', 'Rechazado')->count();

        // 6. Catálogos Completos para los Modales
        $departamentosList = Departamento::all();
        $materialesList = Material::with(['tipo', 'unidad', 'marca'])->get();
        $areasList = Area::with('departamento')->get();
        $marcasList = MarcaMaterial::withCount('materiales')->get();
        $tiposList = TipoMaterial::all();
        $unidadesList = UnidadMedida::all();
        $proveedoresList = Proveedor::all();
        $receptoresList = Receptor::with('area.departamento')->get();
        $usuariosList = User::with('roles')->get();
        
        $totalRegistrosCatalogos = $departamentosList->count() + $materialesList->count() + $areasList->count() + 
                                   $marcasList->count() + $tiposList->count() + $unidadesList->count() + 
                                   $proveedoresList->count() + $receptoresList->count() + $usuariosList->count();

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
            'inventariosRecientes' => $inventariosRecientes,
            'solicitudesRecientes' => $solicitudesRecientes,
            'mantenimientosRecientes' => $mantenimientosRecientes,

            // Variables para módulos
            'inventarioDisponibles' => $inventarioDisponibles,
            'inventarioAsignados' => $inventarioAsignados,
            'inventarioEnMantenimiento' => $inventarioEnMantenimiento,
            'inventarioDanados' => $inventarioDanados,

            'mantenimientoEnRevision' => $mantenimientoEnRevision,
            'mantenimientoEnProceso' => $mantenimientoEnProceso,
            'mantenimientoCompletados' => $mantenimientoCompletados,

            'solicitudesAutorizadas' => $solicitudesAutorizadas,
            'solicitudesRechazadas' => $solicitudesRechazadas,

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
            
            'totalRegistrosCatalogos' => $totalRegistrosCatalogos,
        ];
    }
    
    // =========================================================================
    // MÉTODOS LIVEWIRE PARA OPERACIONES INLINE (CRUD) EN SLIDE-OVERS
    // =========================================================================

    // DEPARTAMENTO
    public function saveDepartamento($id, $nombre)
    {
        if (empty(trim($nombre))) {
            return;
        }
        
        if ($id) {
            $dep = Departamento::find($id);
            if ($dep) {
                $dep->nombre = $nombre;
                $dep->save();
            }
        } else {
            Departamento::create(['nombre' => $nombre]);
        }
    }

    public function deleteDepartamento($id)
    {
        $dep = Departamento::find($id);
        if ($dep) $dep->delete();
    }

    // ÁREA
    public function saveArea($id, $nombre, $id_departamento)
    {
        if (empty(trim($nombre)) || empty($id_departamento)) {
            return;
        }
        
        if ($id) {
            $area = Area::find($id);
            if ($area) {
                $area->nombre = $nombre;
                $area->id_departamento = $id_departamento;
                $area->save();
            }
        } else {
            Area::create(['nombre' => $nombre, 'id_departamento' => $id_departamento]);
        }
    }

    public function deleteArea($id)
    {
        $area = Area::find($id);
        if ($area) $area->delete();
    }

    // MARCA
    public function saveMarca($id, $nombre)
    {
        if (empty(trim($nombre))) return;
        
        if ($id) {
            $marca = MarcaMaterial::find($id);
            if ($marca) {
                $marca->nombre = $nombre;
                $marca->save();
            }
        } else {
            MarcaMaterial::create(['nombre' => $nombre]);
        }
    }

    public function deleteMarca($id)
    {
        $marca = MarcaMaterial::find($id);
        if ($marca) $marca->delete();
    }

    // TIPO MATERIAL
    public function saveTipo($id, $nombre)
    {
        if (empty(trim($nombre))) return;
        
        if ($id) {
            $tipo = TipoMaterial::find($id);
            if ($tipo) {
                $tipo->nombre = $nombre;
                $tipo->save();
            }
        } else {
            TipoMaterial::create(['nombre' => $nombre]);
        }
    }

    public function deleteTipo($id)
    {
        $tipo = TipoMaterial::find($id);
        if ($tipo) $tipo->delete();
    }

    // UNIDAD MEDIDA
    public function saveUnidad($id, $nombre)
    {
        if (empty(trim($nombre))) return;
        
        if ($id) {
            $unidad = UnidadMedida::find($id);
            if ($unidad) {
                $unidad->nombre = $nombre;
                $unidad->save();
            }
        } else {
            UnidadMedida::create(['nombre' => $nombre]);
        }
    }

    public function deleteUnidad($id)
    {
        $unidad = UnidadMedida::find($id);
        if ($unidad) $unidad->delete();
    }
}
