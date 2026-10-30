<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Solicitud;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    public array $tableColumnSearches = [];

    public function resetTableColumnSearches(): void
    {
        $this->tableColumnSearches = [];
    }

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
                \Filament\Actions\Action::make('desde_detalle_cambio')
                    ->label('Cambio')
                    ->color('info')
                    ->icon('heroicon-m-arrows-right-left')
                    ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('update', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('editarEquipo', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                \Filament\Actions\Action::make('desde_detalle_baja')
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('delete', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('eliminarEquipo', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
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
        $creadosEsteMes = (int) ($inventarioStats->creados_este_mes ?? 0);

        // 2. Estadísticas de Mantenimiento
        $mantenimientosPendientes = Mantenimiento::toBase()
            ->whereIn('estado', ['Pendiente Revision Admin', 'Solicitado'])
            ->count();

        // 3. Estadísticas de Solicitudes
        $solicitudesPendientes = Solicitud::toBase()
            ->where('estado', 'Pendiente')
            ->count();

        // 4. Materiales con Stock Bajo (Usa el scope derivado)
        $materialesStockBajoCount = Material::stockBajo()->count();

        // Opciones de filtros para la tabla de inventario general
        $categoriasFiltro = ['' => 'Todas'];
        try {
            $categoriasFiltro += \App\Models\TipoMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        $areasFiltro = ['' => 'Todas'];
        try {
            $areasFiltro += \App\Models\Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        // 5. Inventarios (con filtros aplicados dinámicamente)
        $invQuery = Inventario::with(['material.marca', 'material.tipo', 'proveedor', 'usuario']);

        if (filled($this->tableColumnSearches['num_serie'] ?? null)) {
            $invQuery->where('num_serie', 'like', '%' . trim($this->tableColumnSearches['num_serie']) . '%');
        }
        if (filled($this->tableColumnSearches['material_nombre'] ?? null)) {
            $invQuery->whereHas('material', fn ($q) => $q->where('nombre', 'like', '%' . trim($this->tableColumnSearches['material_nombre']) . '%'));
        }
        if (filled($this->tableColumnSearches['material_tipo_nombre'] ?? null)) {
            $invQuery->whereHas('material.tipo', fn ($q) => $q->where('nombre', trim($this->tableColumnSearches['material_tipo_nombre'])));
        }
        if (filled($this->tableColumnSearches['estado'] ?? null)) {
            $invQuery->where('estado', $this->tableColumnSearches['estado']);
        }
        if (filled($this->tableColumnSearches['ubicacion_fisica'] ?? null)) {
            $invQuery->where('ubicacion_fisica', 'like', '%' . trim($this->tableColumnSearches['ubicacion_fisica']) . '%');
        }

        if (filled($this->tableColumnSearches['busqueda'] ?? null)) {
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
        if (filled($this->tableColumnSearches['fecha_desde'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '>=', $this->tableColumnSearches['fecha_desde']);
        }
        if (filled($this->tableColumnSearches['fecha_hasta'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '<=', $this->tableColumnSearches['fecha_hasta']);
        }
        $inventariosCompletos = $invQuery->latest('created_at')->take(50)->get();

        return [
            'totalActivos' => $totalActivos,
            'mantenimientosPendientes' => $mantenimientosPendientes,
            'materialesStockBajoCount' => $materialesStockBajoCount,
            'solicitudesPendientes' => $solicitudesPendientes,
            'creadosEsteMes' => $creadosEsteMes,

            'inventarioDisponibles' => $inventarioDisponibles,
            'inventarioAsignados' => $inventarioAsignados,
            'inventarioEnMantenimiento' => $inventarioEnMantenimiento,
            'inventarioDanados' => $inventarioDanados,

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
