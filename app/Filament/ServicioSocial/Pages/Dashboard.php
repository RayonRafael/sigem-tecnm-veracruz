<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Models\Area;
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
    protected static ?string $navigationLabel = 'Panel';
    protected static ?string $navigationGroup = 'Panel';
    protected static ?string $title = 'SIGEM - Servicio Social';
    protected static string $view = 'filament.servicio-social.pages.dashboard';

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
        $userId = auth()->id();

        // 1. Welcome Card Stats (Specific for SS as requested)
        $misSolicitudes = Solicitud::where('id_usuario', $userId)->count();
        $misSolicitudesPendientes = Solicitud::where('id_usuario', $userId)->where('estado', 'Pendiente')->count();
        $misMantenimientos = Mantenimiento::where('id_usuario_solicita', $userId)->count();
        $totalActivos = Inventario::count();
        
        // Bitacora activity just for this user
        $actividadReciente = \App\Models\BitacoraSistema::with('usuario')->where('id_usuario', $userId)->latest('fecha_hora')->limit(5)->get();

        // Colecciones limitadas (mini tablas) y completas (modales)
        $inventariosCompletos = Inventario::with(['material', 'material.marca', 'material.tipo'])->latest('created_at')->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        $solicitudesCompletas = Solicitud::with('usuario')->where('id_usuario', $userId)->latest('created_at')->get();
        $solicitudesRecientes = $solicitudesCompletas->where('estado', 'Pendiente')->take(3);
        if ($solicitudesRecientes->isEmpty()) {
            $solicitudesRecientes = $solicitudesCompletas->take(3);
        }

        $mantenimientosCompletos = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])->where('id_usuario_solicita', $userId)->latest('created_at')->get();
        $mantenimientosRecientes = $mantenimientosCompletos->whereIn('estado', ['Pendiente', 'Solicitado', 'Pendiente Revision Admin', 'En proceso'])->take(3);
        if ($mantenimientosRecientes->isEmpty()) {
            $mantenimientosRecientes = $mantenimientosCompletos->take(3);
        }

        // Stats específicos para los mini módulos del nuevo layout (SS scope)
        $inventarioDisponibles = Inventario::where('estado', 'Disponible')->count();
        $inventarioAsignados = Inventario::where('estado', 'Asignado')->count();
        $inventarioEnMantenimiento = Inventario::where('estado', 'En Mantenimiento')->count();
        $inventarioDanados = Inventario::whereIn('estado', ['Dañado', 'Baja'])->count();

        $mantenimientoEnRevision = Mantenimiento::where('id_usuario_solicita', $userId)->whereIn('estado', ['Pendiente Revision Admin', 'Solicitado'])->count();
        $mantenimientoEnProceso = Mantenimiento::where('id_usuario_solicita', $userId)->where('estado', 'En proceso')->count();
        $mantenimientoCompletados = Mantenimiento::where('id_usuario_solicita', $userId)->where('estado', 'Completado')->count();

        $solicitudesAutorizadas = Solicitud::where('id_usuario', $userId)->where('estado', 'Autorizado')->count();
        $solicitudesRechazadas = Solicitud::where('id_usuario', $userId)->where('estado', 'Rechazado')->count();

        // Catálogos Completos (Read-only for SS)
        $departamentosList = Departamento::all();
        $materialesList = Material::with(['tipo', 'unidad', 'marca'])->get();
        $areasList = Area::with('departamento')->get();
        $marcasList = MarcaMaterial::withCount('materiales')->get();
        $tiposList = TipoMaterial::all();
        $unidadesList = UnidadMedida::all();
        $proveedoresList = Proveedor::all();
        $receptoresList = Receptor::with('area.departamento')->get();
        $usuariosList = User::with('roles')->get();
        
        $catalogosCount = 9;
        $totalRegistrosCatalogos = $departamentosList->count() + $materialesList->count() + $areasList->count() + 
                                   $marcasList->count() + $tiposList->count() + $unidadesList->count() + 
                                   $proveedoresList->count() + $receptoresList->count() + $usuariosList->count();

        return [
            'misSolicitudes' => $misSolicitudes,
            'misSolicitudesPendientes' => $misSolicitudesPendientes,
            'misMantenimientos' => $misMantenimientos,
            'totalActivos' => $totalActivos,
            
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
            
            'catalogosCount' => $catalogosCount,
            'totalRegistrosCatalogos' => $totalRegistrosCatalogos,
        ];
    }
}
