<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ConModalesBajaCambio;
use App\Filament\Resources\InventarioResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Solicitud;
use App\Support\SigemExcelExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;

class Dashboard extends BaseDashboard
{
    use WithPagination;

    public array $tableColumnSearches = [];
    public int $perPage = 10;
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    public function updatedTableColumnSearches(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function resetTableColumnSearches(): void
    {
        $this->tableColumnSearches = [];
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function getFilteredTableQuery(): Builder
    {
        return InventarioResource::obtenerInventariosDashboardQuery(
            $this->tableColumnSearches,
            $this->sortColumn,
            $this->sortDirection
        );
    }

    public function exportarExcel()
    {
        return SigemExcelExporter::exportarInventario($this);
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
            ->form(InventarioResource::getMockupFormSchema())
            ->modalSubmitActionLabel('Registrar')
            ->modalCancelActionLabel('Cancelar')
            ->extraAttributes([
                'class' => 'sigem-btn-primary',
                'title' => 'Registrar un nuevo equipo en el inventario',
            ])
            ->using(fn (array $data): Inventario => InventarioResource::registrarMultiplesEquipos($data))
            ->successNotificationTitle(fn (?Inventario $record, CreateAction $action): string => InventarioResource::obtenerMensajeExito($record ?? $action->getRecord()));
    }

    public function verEquipoAction(): Action
    {
        return ViewAction::make('verEquipo')
            ->label('Ver equipo')
            ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categoría')
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
                    'departamento',
                    'area.departamento',
                    'salon',
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
                    ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true) && auth()->user()?->can('update', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('cambiarArea', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                \Filament\Actions\Action::make('desde_detalle_baja')
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true) && auth()->user()?->can('delete', $record))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('darBaja', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
            ]);
    }

    public function verEquipo($id): void
    {
        $this->mountAction('verEquipo', ['id' => $id, 'record' => $id]);
    }

    public function cambiarAreaAction(): Action
    {
        return Action::make('cambiarArea')
            ->label('Cambiar área')
            ->modalHeading('Cambiar área de equipo')
            ->modalDescription('Se actualizará la ubicación física del equipo de forma inmediata.')
            ->model(Inventario::class)
            ->record(fn (array $arguments) => Inventario::find($arguments['id'] ?? $arguments['record']))
            ->form(ConModalesBajaCambio::getModalCambioAreaFormSchema(true))
            ->modalSubmitActionLabel('Confirmar cambio')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) {
                $record->cambiarAreaDirecto(
                    $data['departamento_id'],
                    $data['area_id'],
                    $data['aula'] ?? null,
                    null,
                    $data['motivo'] ?? null,
                    auth()->id()
                );
                Notification::make()->title('Área del equipo actualizada correctamente')->success()->send();
            });
    }

    public function cambiarArea($id): void
    {
        $this->mountAction('cambiarArea', ['id' => $id, 'record' => $id]);
    }

    public function darBajaAction(): Action
    {
        return Action::make('darBaja')
            ->label('Dar de baja')
            ->modalHeading('Confirmar baja de equipo')
            ->modalDescription('El equipo pasará a estado Dado de baja de manera inmediata.')
            ->model(Inventario::class)
            ->record(fn (array $arguments) => Inventario::find($arguments['id'] ?? $arguments['record']))
            ->form(ConModalesBajaCambio::getModalBajaFormSchema())
            ->modalSubmitActionLabel('Confirmar baja')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) {
                $record->darBajaDirecta(
                    $data['motivo_baja'],
                    $data['observaciones_baja'],
                    auth()->id()
                );
                Notification::make()->title('Equipo dado de baja correctamente')->success()->send();
            });
    }

    public function darBaja($id): void
    {
        $this->mountAction('darBaja', ['id' => $id, 'record' => $id]);
    }

    public function editarEquipoAction(): Action
    {
        return EditAction::make('editarEquipo')
            ->label('Editar equipo')
            ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
            ))
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                return Inventario::with(['material.marca', 'material.tipo', 'area', 'salon'])
                    ->find($arguments['id'] ?? $arguments['record']);
            })
            ->form([
                Tabs::make('Tabs')
                    ->tabs(InventarioResource::getEditFormTabs())
                    ->columnSpanFull(),
            ])
            ->slideOver()
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->mutateRecordDataUsing(fn (array $data, ?Inventario $record = null): array => InventarioResource::hidratarDatosEdicion($data, $record))
            ->using(fn (Inventario $record, array $data): Inventario => InventarioResource::guardarCambiosEquipo($record, $data))
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
            ->modalHeading(fn (?Inventario $record) => '¿Eliminar ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo') . '?')
            ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · Esta acción no se puede deshacer'
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
                COUNT(CASE WHEN estado = 'Solicitud de baja' THEN 1 END) as solicitudes_baja,
                COUNT(CASE WHEN estado = 'Cambio de área' THEN 1 END) as solicitudes_cambio_area,
                COUNT(CASE WHEN estado = 'Dado de baja' THEN 1 END) as dados_de_baja,
                COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as creados_este_mes
            ", [$startOfMonth, $endOfMonth])
            ->first();

        $totalActivos = (int) ($inventarioStats->total ?? 0);
        $inventarioDisponibles = (int) ($inventarioStats->disponibles ?? 0);
        $inventarioAsignados = (int) ($inventarioStats->asignados ?? 0);
        $inventarioEnMantenimiento = (int) ($inventarioStats->en_mantenimiento ?? 0);
        $inventarioDanados = (int) ($inventarioStats->danados ?? 0);
        $inventarioSolicitudesBaja = (int) ($inventarioStats->solicitudes_baja ?? 0);
        $inventarioSolicitudesCambioArea = (int) ($inventarioStats->solicitudes_cambio_area ?? 0);
        $inventarioDadosDeBaja = (int) ($inventarioStats->dados_de_baja ?? 0);
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

        // 5. Opciones de filtros y registros filtrados paginados
        [$categoriasFiltro, $areasFiltro, $departamentosFiltro] = InventarioResource::obtenerOpcionesFiltrosDashboard();
        $inventariosPaginados = $this->getFilteredTableQuery()->paginate($this->perPage);

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
            'inventarioSolicitudesBaja' => $inventarioSolicitudesBaja,
            'inventarioSolicitudesCambioArea' => $inventarioSolicitudesCambioArea,
            'inventarioDadosDeBaja' => $inventarioDadosDeBaja,

            'categoriasFiltro' => $categoriasFiltro,
            'areasFiltro' => $areasFiltro,
            'departamentosFiltro' => $departamentosFiltro,
            'inventariosPaginados' => $inventariosPaginados,
            'inventariosCompletos' => $inventariosPaginados,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'categoriasTop' => InventarioResource::obtenerCategoriasTop(),
        ];
    }
}
