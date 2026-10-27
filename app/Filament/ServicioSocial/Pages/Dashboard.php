<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Filament\ServicioSocial\Resources\InventarioResource;
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
use Spatie\Activitylog\Models\Activity;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Panel principal';

    protected static ?string $navigationGroup = 'Panel principal';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'SIGEM - Servicio Social';

    protected static string $view = 'filament.servicio-social.pages.dashboard';

    public function registrarEquipoAction(): Action
    {
        return CreateAction::make('registrarEquipo')
            ->label("\u{FF0B} Registrar equipo")
            ->model(Inventario::class)
            ->slideOver()
            ->createAnother(false)
            ->steps(InventarioResource::getFormSteps())
            ->mutateFormDataUsing(function (array $data): array {
                $data['aprobado'] = false;
                $data['estado_registro'] = 'Pendiente';
                $data['aprobado_por'] = null;
                $data['id_usuario'] = auth()->id();

                return $data;
            })
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
            'xl' => 12,
            '2xl' => 12,
        ];
    }

    public function getViewData(): array
    {
        $userId = auth()->id();

        // 1. Welcome Card Stats (Specific for SS as requested)
        $misSolicitudes = Solicitud::where('id_usuario', $userId)->count();
        $misSolicitudesPendientes = Solicitud::where('id_usuario', $userId)->pendientes()->count();
        $misMantenimientos = Mantenimiento::where('id_usuario_solicita', $userId)->count();
        $totalActivos = Inventario::count();

        // Bitacora activity just for this user
        $actividadReciente = Activity::with('causer')
            ->where('causer_id', $userId)
            ->where('causer_type', User::class)
            ->latest()->limit(5)->get();

        // Colecciones limitadas (mini tablas) y completas (modales)
        $inventariosCompletos = Inventario::with(['material', 'material.marca', 'material.tipo'])->latest('created_at')->take(50)->get();
        $inventariosRecientes = $inventariosCompletos->take(3);

        $solicitudesCompletas = Solicitud::with('usuario')->where('id_usuario', $userId)->latest('created_at')->take(50)->get();
        $solicitudesRecientes = $solicitudesCompletas->where('estado', 'Pendiente')->take(3);
        if ($solicitudesRecientes->isEmpty()) {
            $solicitudesRecientes = $solicitudesCompletas->take(3);
        }

        $mantenimientosCompletos = Mantenimiento::with(['inventario', 'inventario.material', 'usuarioSolicita'])->where('id_usuario_solicita', $userId)->latest('created_at')->take(50)->get();
        $mantenimientosRecientes = $mantenimientosCompletos->whereIn('estado', ['Pendiente', 'Solicitado', 'Pendiente Revision Admin', 'En proceso'])->take(3);
        if ($mantenimientosRecientes->isEmpty()) {
            $mantenimientosRecientes = $mantenimientosCompletos->take(3);
        }

        $inventarioDisponibles = Inventario::disponibles()->count();
        $inventarioAsignados = Inventario::asignados()->count();
        $inventarioEnMantenimiento = Inventario::enMantenimiento()->count();
        $inventarioDanados = Inventario::danados()->count();

        $mantenimientoEnRevision = Mantenimiento::where('id_usuario_solicita', $userId)->enRevision()->count();
        $mantenimientoEnProceso = Mantenimiento::where('id_usuario_solicita', $userId)->enProceso()->count();
        $mantenimientoCompletados = Mantenimiento::where('id_usuario_solicita', $userId)->completados()->count();

        $solicitudesAutorizadas = Solicitud::where('id_usuario', $userId)->autorizadas()->count();
        $solicitudesRechazadas = Solicitud::where('id_usuario', $userId)->rechazadas()->count();

        // Catálogos Completos (Read-only for SS)
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

            'totalRegistrosCatalogos' => $totalRegistrosCatalogos,
        ];
    }
}
