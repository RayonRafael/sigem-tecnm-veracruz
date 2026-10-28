<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InventarioResource;
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
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Panel principal';

    protected static ?string $navigationLabel = 'Panel principal';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'SIGEM - TecNM Veracruz';

    protected static string $view = 'filament.pages.dashboard';

    public function registrarEquipoAction(): Action
    {
        return CreateAction::make('registrarEquipo')
            ->label("\u{FF0B} Registrar equipo")
            ->model(Inventario::class)
            ->slideOver()
            ->createAnother(false)
            ->steps(InventarioResource::getFormSteps())
            ->extraAttributes([
                'class' => 'sigem-btn-primary',
                'title' => 'Registrar un nuevo equipo en el inventario',
            ])
            ->successNotificationTitle('Registro creado exitosamente');
    }

    public function verEquipoAction(): Action
    {
        return ViewAction::make('verEquipo')
            ->label('Ver equipo')
            ->modalHeading('Detalle de equipo')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;
                if ($id instanceof Inventario) {
                    return $id;
                }

                return Inventario::with([
                    'material.marca',
                    'material.tipo',
                    'proveedor',
                    'usuario',
                ])->find($id);
            })
            ->slideOver()
            ->infolist(fn (Infolist $infolist): Infolist => InventarioResource::infolist($infolist))
            ->modalCancelActionLabel('Cerrar');
    }

    public function verEquipo($id): void
    {
        $this->mountAction('verEquipo', ['id' => $id, 'record' => $id]);
    }

    public function editarEquipoAction(): Action
    {
        return EditAction::make('editarEquipo')
            ->label('Editar equipo')
            ->modalHeading('Editar equipo')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                return Inventario::find($arguments['id'] ?? $arguments['record']);
            })
            ->steps(InventarioResource::getFormSteps())
            ->slideOver()
            ->successNotificationTitle('Registro actualizado correctamente');
    }

    public function editarEquipo($id): void
    {
        $this->mountAction('editarEquipo', ['id' => $id]);
    }

    public function eliminarEquipoAction(): Action
    {
        return DeleteAction::make('eliminarEquipo')
            ->label('Eliminar equipo')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                return Inventario::find($arguments['id'] ?? $arguments['record']);
            })
            ->successNotificationTitle('Registro eliminado correctamente');
    }

    public function eliminarEquipo($id): void
    {
        $this->mountAction('eliminarEquipo', ['id' => $id]);
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 12,
            'sm' => 12,
            'md' => 12,
            'lg' => 12,
        ];
    }

    public function getViewData(): array
    {
        // 1. Estadísticas de Inventario (1 sola consulta agregada)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $inventarioStats = Inventario::toBase()
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'Disponible' THEN 1 END) as disponibles,
                COUNT(CASE WHEN estado = 'Asignado' THEN 1 END) as asignados,
                COUNT(CASE WHEN estado = 'En Mantenimiento' THEN 1 END) as en_mantenimiento,
                COUNT(CASE WHEN estado = 'Dañado' THEN 1 END) as danados,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as creados_este_mes
            ", [$startOfMonth, $endOfMonth])
            ->first();

        $totalActivos = (int) ($inventarioStats->total ?? 0);
        $inventarioDisponibles = (int) ($inventarioStats->disponibles ?? 0);
        $inventarioAsignados = (int) ($inventarioStats->asignados ?? 0);
        $inventarioEnMantenimiento = (int) ($inventarioStats->en_mantenimiento ?? 0);
        $inventarioDanados = (int) ($inventarioStats->danados ?? 0);
        $activosBueno = $inventarioDisponibles + $inventarioAsignados;
        $porcentajeBuenEstado = $totalActivos > 0 ? round(($activosBueno / $totalActivos) * 100) : 0;
        $creadosEsteMes = (int) ($inventarioStats->creados_este_mes ?? 0);

        // 2. Estadísticas de Mantenimiento (1 sola consulta agregada)
        $mantenimientoStats = Mantenimiento::toBase()
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado IN ('Pendiente Revision Admin', 'Solicitado') THEN 1 END) as en_revision,
                COUNT(CASE WHEN estado = 'En proceso' THEN 1 END) as en_proceso,
                COUNT(CASE WHEN estado = 'Completado' THEN 1 END) as completados
            ")
            ->first();

        $mantenimientosTotales = (int) ($mantenimientoStats->total ?? 0);
        $mantenimientosPendientes = (int) ($mantenimientoStats->en_revision ?? 0);
        $mantenimientoEnRevision = $mantenimientosPendientes;
        $mantenimientoEnProceso = (int) ($mantenimientoStats->en_proceso ?? 0);
        $mantenimientoCompletados = (int) ($mantenimientoStats->completados ?? 0);

        // 3. Estadísticas de Solicitudes (1 sola consulta agregada)
        $solicitudStats = Solicitud::toBase()
            ->selectRaw("
                COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN estado = 'Autorizado' THEN 1 END) as autorizadas,
                COUNT(CASE WHEN estado = 'Rechazado' THEN 1 END) as rechazadas
            ")
            ->first();

        $solicitudesPendientes = (int) ($solicitudStats->pendientes ?? 0);
        $solicitudesAutorizadas = (int) ($solicitudStats->autorizadas ?? 0);
        $solicitudesRechazadas = (int) ($solicitudStats->rechazadas ?? 0);

        // 4. Materiales con Stock Bajo (Usa el scope derivado)
        $materialesStockBajoCount = Material::stockBajo()->count();

        // 5. Actividad Reciente (5 items con eager loading de causer)
        $actividadReciente = Activity::with('causer')->latest()->limit(5)->get();

        // 6. Inventarios Recientes y Completos (con relaciones eager loaded para evitar N+1)
        $inventariosCompletos = Inventario::with(['material.marca', 'material.tipo', 'proveedor', 'usuario'])
            ->latest('created_at')
            ->take(50)
            ->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        // 7. Solicitudes Recientes y Completas (con usuario y receptor eager loaded)
        $solicitudesCompletas = Solicitud::with(['usuario', 'receptor'])
            ->latest('created_at')
            ->take(50)
            ->get();
        $solicitudesRecientes = $solicitudesCompletas->where('estado', 'Pendiente')->take(3);
        if ($solicitudesRecientes->isEmpty()) {
            $solicitudesRecientes = $solicitudesCompletas->take(3);
        }

        // 8. Mantenimientos Recientes y Completos (con inventario.material y usuarioSolicita eager loaded)
        $mantenimientosCompletos = Mantenimiento::with(['inventario.material', 'usuarioSolicita'])
            ->latest('created_at')
            ->take(50)
            ->get();
        $mantenimientosRecientes = $mantenimientosCompletos
            ->whereIn('estado', ['Pendiente', 'Solicitado', 'Pendiente Revision Admin', 'En proceso'])
            ->take(3);
        if ($mantenimientosRecientes->isEmpty()) {
            $mantenimientosRecientes = $mantenimientosCompletos->take(3);
        }

        // 9. Catálogos para Modales (Cacheados por 60 minutos con invalidación reactiva)
        $catalogosData = Cache::remember('sigem_dashboard_catalogos', now()->addMinutes(60), function () {
            $departamentosList = Departamento::latest()->take(50)->get();
            $materialesList = Material::with(['tipo', 'unidad', 'marca'])->latest()->take(50)->get();
            $areasList = Area::with('departamento')->latest()->take(50)->get();
            $marcasList = MarcaMaterial::withCount('materiales')->latest()->take(50)->get();
            $tiposList = TipoMaterial::latest()->take(50)->get();
            $unidadesList = UnidadMedida::latest()->take(50)->get();
            $proveedoresList = Proveedor::latest()->take(50)->get();
            $receptoresList = Receptor::with('area.departamento')->latest()->take(50)->get();
            $usuariosList = User::with('roles')->latest()->take(50)->get();

            $totalRegistrosCatalogos = $departamentosList->count() + $materialesList->count() + $areasList->count() +
                                       $marcasList->count() + $tiposList->count() + $unidadesList->count() +
                                       $proveedoresList->count() + $receptoresList->count() + $usuariosList->count();

            return compact(
                'departamentosList',
                'materialesList',
                'areasList',
                'marcasList',
                'tiposList',
                'unidadesList',
                'proveedoresList',
                'receptoresList',
                'usuariosList',
                'totalRegistrosCatalogos'
            );
        });

        return array_merge([
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
            'categoriasTop' => (function () {
                $todasCategorias = Inventario::query()
                    ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
                    ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                    ->selectRaw("COALESCE(tipo_material.nombre, 'General') as cat, count(*) as total")
                    ->groupBy('cat')
                    ->orderByDesc('total')
                    ->pluck('total', 'cat')
                    ->toArray();

                $top = [];
                $otras = 0;
                $idx = 0;
                foreach ($todasCategorias as $catNom => $catCant) {
                    if ($idx < 8) {
                        $top[$catNom] = $catCant;
                    } else {
                        $otras += $catCant;
                    }
                    $idx++;
                }
                if ($otras > 0) {
                    $top['Otras categorías'] = $otras;
                }

                return $top;
            })(),
        ], $catalogosData);
    }

    /**
     * Invalida la caché de catálogos cuando se realiza una mutación inline.
     */
    public function clearCatalogosCache(): void
    {
        Cache::forget('sigem_dashboard_catalogos');
    }

    // =========================================================================
    // MÉTODOS LIVEWIRE PARA OPERACIONES INLINE (CRUD) EN SLIDE-OVERS (BLINDADOS)
    // =========================================================================

    // DEPARTAMENTO
    public function saveDepartamento($id, $nombre)
    {
        if (empty(trim($nombre))) {
            return;
        }

        if ($id) {
            $dep = Departamento::findOrFail($id);
            Gate::authorize('update', $dep);
            $dep->nombre = trim($nombre);
            $dep->save();
        } else {
            Gate::authorize('create', Departamento::class);
            Departamento::create(['nombre' => trim($nombre)]);
        }

        $this->clearCatalogosCache();
    }

    public function deleteDepartamento($id)
    {
        $dep = Departamento::findOrFail($id);
        Gate::authorize('delete', $dep);
        $dep->delete();

        $this->clearCatalogosCache();
    }

    // ÁREA
    public function saveArea($id, $nombre, $id_departamento)
    {
        if (empty(trim($nombre)) || empty($id_departamento)) {
            return;
        }

        if ($id) {
            $area = Area::findOrFail($id);
            Gate::authorize('update', $area);
            $area->nombre = trim($nombre);
            $area->id_departamento = $id_departamento;
            $area->save();
        } else {
            Gate::authorize('create', Area::class);
            Area::create(['nombre' => trim($nombre), 'id_departamento' => $id_departamento]);
        }

        $this->clearCatalogosCache();
    }

    public function deleteArea($id)
    {
        $area = Area::findOrFail($id);
        Gate::authorize('delete', $area);
        $area->delete();

        $this->clearCatalogosCache();
    }

    // MARCA
    public function saveMarca($id, $nombre)
    {
        if (empty(trim($nombre))) {
            return;
        }

        if ($id) {
            $marca = MarcaMaterial::findOrFail($id);
            Gate::authorize('update', $marca);
            $marca->nombre = trim($nombre);
            $marca->save();
        } else {
            Gate::authorize('create', MarcaMaterial::class);
            MarcaMaterial::create(['nombre' => trim($nombre)]);
        }

        $this->clearCatalogosCache();
    }

    public function deleteMarca($id)
    {
        $marca = MarcaMaterial::findOrFail($id);
        Gate::authorize('delete', $marca);
        $marca->delete();

        $this->clearCatalogosCache();
    }

    // TIPO MATERIAL
    public function saveTipo($id, $nombre)
    {
        if (empty(trim($nombre))) {
            return;
        }

        if ($id) {
            $tipo = TipoMaterial::findOrFail($id);
            Gate::authorize('update', $tipo);
            $tipo->nombre = trim($nombre);
            $tipo->save();
        } else {
            Gate::authorize('create', TipoMaterial::class);
            TipoMaterial::create(['nombre' => trim($nombre)]);
        }

        $this->clearCatalogosCache();
    }

    public function deleteTipo($id)
    {
        $tipo = TipoMaterial::findOrFail($id);
        Gate::authorize('delete', $tipo);
        $tipo->delete();

        $this->clearCatalogosCache();
    }

    // UNIDAD MEDIDA
    public function saveUnidad($id, $nombre)
    {
        if (empty(trim($nombre))) {
            return;
        }

        if ($id) {
            $unidad = UnidadMedida::findOrFail($id);
            Gate::authorize('update', $unidad);
            $unidad->nombre = trim($nombre);
            $unidad->save();
        } else {
            Gate::authorize('create', UnidadMedida::class);
            UnidadMedida::create(['nombre' => trim($nombre)]);
        }

        $this->clearCatalogosCache();
    }

    public function deleteUnidad($id)
    {
        $unidad = UnidadMedida::findOrFail($id);
        Gate::authorize('delete', $unidad);
        $unidad->delete();

        $this->clearCatalogosCache();
    }
}
