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
use Filament\Forms\Components\Tabs;
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

        // 5. Opciones de filtros y registros filtrados
        [$categoriasFiltro, $areasFiltro] = InventarioResource::obtenerOpcionesFiltrosDashboard();
        $inventariosCompletos = InventarioResource::obtenerInventariosDashboard($this->tableColumnSearches);

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
            'inventariosCompletos' => $inventariosCompletos,
            'categoriasTop' => InventarioResource::obtenerCategoriasTop(),
        ];
    }
}
