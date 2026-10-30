<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Filament\ServicioSocial\Resources\InventarioResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public array $tableColumnSearches = [];

    public function resetTableColumnSearches(): void
    {
        $this->tableColumnSearches = [];
    }

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
            ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? 'Detalle de equipo')
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? 'Sin categoría')
            ))
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
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions([
                \Filament\Actions\Action::make('desde_detalle_editar')
                    ->label('Modificar')
                    ->color('warning')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('update', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('editarEquipo', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
            ]);
    }

    public function verEquipo($id): void
    {
        $this->mountAction('verEquipo', ['id' => $id, 'record' => $id]);
    }

    public function editarEquipoAction(): Action
    {
        return EditAction::make('editarEquipo')
            ->label('Editar equipo')
            ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? 'equipo'))
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->num_serie ?? '—')
            ))
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
            ->modalHeading(fn (?Inventario $record) => '¿Eliminar ' . ($record?->material?->nombre ?? 'equipo') . '?')
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->num_serie ?? '—') . ' · Esta acción no se puede deshacer'
            ))
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

        // Opciones de filtros para la tabla de inventario general
        $categoriasFiltro = ['' => 'Todas'];
        try {
            $categoriasFiltro += \App\Models\TipoMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        $areasFiltro = ['' => 'Todas'];
        try {
            $areasFiltro += \App\Models\Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        // Inventarios para tabla principal del Dashboard (con filtros aplicados dinámicamente)
        $invQuery = Inventario::with(['material', 'material.marca', 'material.tipo']);

        if (! empty($this->tableColumnSearches['num_serie'])) {
            $invQuery->where('num_serie', 'like', '%' . trim($this->tableColumnSearches['num_serie']) . '%');
        }
        if (! empty($this->tableColumnSearches['material_nombre'])) {
            $invQuery->whereHas('material', fn ($q) => $q->where('nombre', 'like', '%' . trim($this->tableColumnSearches['material_nombre']) . '%'));
        }
        if (! empty($this->tableColumnSearches['material_tipo_nombre'])) {
            $invQuery->whereHas('material.tipo', fn ($q) => $q->where('nombre', trim($this->tableColumnSearches['material_tipo_nombre'])));
        }
        if (! empty($this->tableColumnSearches['estado'])) {
            $invQuery->where('estado', $this->tableColumnSearches['estado']);
        }
        if (! empty($this->tableColumnSearches['ubicacion_fisica'])) {
            $invQuery->where('ubicacion_fisica', 'like', '%' . trim($this->tableColumnSearches['ubicacion_fisica']) . '%');
        }

                if (! empty($this->tableColumnSearches['busqueda'])) {
            $term = '%' . trim($this->tableColumnSearches['busqueda']) . '%';
            $invQuery->where(function ($q) use ($term) {
                $q->where('num_serie', 'like', $term)
                    ->orWhere('ubicacion_fisica', 'like', $term)
                    ->orWhere('mac_address', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhereHas('material', fn ($m) => $m->where('nombre', 'like', $term)
                        ->orWhereHas('marca', fn ($b) => $b->where('nombre', 'like', $term)));
            });
        }
        if (! empty($this->tableColumnSearches['fecha_desde'])) {
            $invQuery->whereDate('fecha_registro', '>=', $this->tableColumnSearches['fecha_desde']);
        }
        if (! empty($this->tableColumnSearches['fecha_hasta'])) {
            $invQuery->whereDate('fecha_registro', '<=', $this->tableColumnSearches['fecha_hasta']);
        }
$inventariosCompletos = $invQuery->latest('created_at')->take(50)->get();

        $inventarioDisponibles = Inventario::disponibles()->count();
        $inventarioAsignados = Inventario::asignados()->count();
        $inventarioEnMantenimiento = Inventario::enMantenimiento()->count();
        $inventarioDanados = Inventario::danados()->count();

        $mantenimientoEnRevision = Mantenimiento::where('id_usuario_solicita', $userId)->enRevision()->count();

        $solicitudesAutorizadas = Solicitud::where('id_usuario', $userId)->autorizadas()->count();

        return [
            'misSolicitudes' => $misSolicitudes,
            'misSolicitudesPendientes' => $misSolicitudesPendientes,
            'misMantenimientos' => $misMantenimientos,
            'totalActivos' => $totalActivos,

            'inventarioDisponibles' => $inventarioDisponibles,
            'inventarioAsignados' => $inventarioAsignados,
            'inventarioEnMantenimiento' => $inventarioEnMantenimiento,
            'inventarioDanados' => $inventarioDanados,

            'mantenimientoEnRevision' => $mantenimientoEnRevision,
            'solicitudesAutorizadas' => $solicitudesAutorizadas,

            'categoriasFiltro' => $categoriasFiltro,
            'areasFiltro' => $areasFiltro,
            'inventariosCompletos' => $inventariosCompletos,
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
        ];
    }
}
