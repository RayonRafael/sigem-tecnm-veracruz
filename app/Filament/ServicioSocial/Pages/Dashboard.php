<?php

namespace App\Filament\ServicioSocial\Pages;

use App\Filament\Concerns\ConAccionesMasivasInventario;
use App\Filament\Concerns\ConModalesBajaCambio;
use App\Filament\Resources\InventarioResource as AdminInventarioResource;
use App\Filament\ServicioSocial\Resources\InventarioResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Support\SigemExcelExporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;

class Dashboard extends BaseDashboard
{
    use WithPagination;
    use ConAccionesMasivasInventario;

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
        return AdminInventarioResource::obtenerInventariosDashboardQuery(
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
            ->form(AdminInventarioResource::getMockupFormSchema())
            ->modalSubmitActionLabel('Registrar')
            ->modalCancelActionLabel('Cancelar')
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
            ->using(fn (array $data): Inventario => AdminInventarioResource::registrarMultiplesEquipos($data))
            ->successNotificationTitle(fn (?Inventario $record, CreateAction $action): string => AdminInventarioResource::obtenerMensajeExito($record ?? $action->getRecord()));
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
                    ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de área'], true))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('editarEquipo', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                \Filament\Actions\Action::make('desde_detalle_cambio')
                    ->label('Cambio')
                    ->color('info')
                    ->icon('heroicon-m-arrows-right-left')
                    ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('solicitarCambioArea', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                \Filament\Actions\Action::make('desde_detalle_baja')
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('solicitarBaja', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
            ]);
    }

    public function verEquipo($id): void
    {
        $this->mountAction('verEquipo', ['id' => $id, 'record' => $id]);
    }

    public function solicitarCambioAreaAction(): Action
    {
        return Action::make('solicitarCambioArea')
            ->label('Solicitar cambio de área')
            ->modalHeading('Solicitar cambio de área')
            ->modalDescription('La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
            ->model(Inventario::class)
            ->record(fn (array $arguments) => Inventario::find($arguments['id'] ?? $arguments['record']))
            ->form(ConModalesBajaCambio::getModalCambioAreaFormSchema(false))
            ->modalSubmitActionLabel('Solicitar cambio')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) {
                $record->solicitarCambioArea(
                    $data['departamento_id'],
                    $data['area_id'],
                    $data['aula'] ?? null,
                    null,
                    $data['motivo'] ?? null,
                    auth()->id()
                );
                Notification::make()->title('Solicitud de cambio de área enviada a revisión')->warning()->send();
            });
    }

    public function solicitarCambioArea($id): void
    {
        $this->mountAction('solicitarCambioArea', ['id' => $id, 'record' => $id]);
    }

    public function solicitarBajaAction(): Action
    {
        return Action::make('solicitarBaja')
            ->label('Solicitar baja')
            ->modalHeading('Solicitar baja de equipo')
            ->modalDescription('La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
            ->model(Inventario::class)
            ->record(fn (array $arguments) => Inventario::find($arguments['id'] ?? $arguments['record']))
            ->form(ConModalesBajaCambio::getModalBajaFormSchema())
            ->modalSubmitActionLabel('Solicitar baja')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) {
                $record->solicitarBaja(
                    $data['motivo_baja'],
                    $data['observaciones_baja'],
                    auth()->id()
                );
                Notification::make()->title('Solicitud de baja enviada a revisión')->warning()->send();
            });
    }

    public function solicitarBaja($id): void
    {
        $this->mountAction('solicitarBaja', ['id' => $id, 'record' => $id]);
    }

    public function editarEquipoAction(): Action
    {
        return EditAction::make('editarEquipo')
            ->authorize(true)
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
            ->mutateRecordDataUsing(fn (array $data, ?Inventario $record = null): array => AdminInventarioResource::hidratarDatosEdicion($data, $record))
            ->using(fn (Inventario $record, array $data): Inventario => AdminInventarioResource::guardarCambiosEquipo($record, $data))
            ->successNotificationTitle('Registro actualizado correctamente');
    }

    public function editarEquipo($id): void
    {
        $this->mountAction('editarEquipo', ['id' => $id]);
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

        // 1. Estadísticas de Solicitudes del usuario (1 consulta agregada)
        $solicitudStats = Solicitud::toBase()
            ->where('id_usuario', $userId)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN estado = 'Autorizada' THEN 1 END) as autorizadas
            ")
            ->first();

        $misSolicitudes = (int) ($solicitudStats->total ?? 0);
        $misSolicitudesPendientes = (int) ($solicitudStats->pendientes ?? 0);
        $solicitudesAutorizadas = (int) ($solicitudStats->autorizadas ?? 0);

        // 2. Estadísticas de Mantenimientos del usuario (1 consulta agregada)
        $mantenimientoStats = Mantenimiento::toBase()
            ->where('id_usuario_solicita', $userId)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado IN ('Pendiente Revision Admin', 'Solicitado') THEN 1 END) as en_revision
            ")
            ->first();

        $misMantenimientos = (int) ($mantenimientoStats->total ?? 0);
        $mantenimientoEnRevision = (int) ($mantenimientoStats->en_revision ?? 0);

        // 3. Estadísticas de Inventario (1 consulta agregada)
        $inventarioStats = Inventario::toBase()
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'Disponible' AND estado_registro = 'Aprobado' THEN 1 END) as disponibles,
                COUNT(CASE WHEN estado = 'Asignado' AND estado_registro = 'Aprobado' THEN 1 END) as asignados,
                COUNT(CASE WHEN estado = 'En Mantenimiento' AND estado_registro = 'Aprobado' THEN 1 END) as en_mantenimiento,
                COUNT(CASE WHEN estado = 'Dañado' AND estado_registro = 'Aprobado' THEN 1 END) as danados,
                COUNT(CASE WHEN estado = 'Solicitud de baja' AND estado_registro = 'Aprobado' THEN 1 END) as solicitudes_baja,
                COUNT(CASE WHEN estado = 'Cambio de área' AND estado_registro = 'Aprobado' THEN 1 END) as solicitudes_cambio_area,
                COUNT(CASE WHEN estado = 'Dado de baja' AND estado_registro = 'Aprobado' THEN 1 END) as dados_de_baja
            ")
            ->first();

        $totalActivos = (int) ($inventarioStats->total ?? 0);
        $inventarioDisponibles = (int) ($inventarioStats->disponibles ?? 0);
        $inventarioAsignados = (int) ($inventarioStats->asignados ?? 0);
        $inventarioEnMantenimiento = (int) ($inventarioStats->en_mantenimiento ?? 0);
        $inventarioDanados = (int) ($inventarioStats->danados ?? 0);

        // 4. Opciones de filtros y registros filtrados paginados
        [$categoriasFiltro, $areasFiltro, $departamentosFiltro] = AdminInventarioResource::obtenerOpcionesFiltrosDashboard();
        $inventariosPaginados = $this->getFilteredTableQuery()->paginate($this->perPage);

        return [
            'misSolicitudes' => $misSolicitudes,
            'misSolicitudesPendientes' => $misSolicitudesPendientes,
            'misMantenimientos' => $misMantenimientos,
            'totalActivos' => $totalActivos,

            'inventarioDisponibles' => $inventarioDisponibles,
            'inventarioAsignados' => $inventarioAsignados,
            'inventarioEnMantenimiento' => $inventarioEnMantenimiento,
            'inventarioDanados' => $inventarioDanados,
            'inventarioSolicitudesBaja' => (int) ($inventarioStats->solicitudes_baja ?? 0),
            'inventarioSolicitudesCambioArea' => (int) ($inventarioStats->solicitudes_cambio_area ?? 0),
            'inventarioDadosDeBaja' => (int) ($inventarioStats->dados_de_baja ?? 0),

            'mantenimientoEnRevision' => $mantenimientoEnRevision,
            'solicitudesAutorizadas' => $solicitudesAutorizadas,

            'categoriasFiltro' => $categoriasFiltro,
            'areasFiltro' => $areasFiltro,
            'departamentosFiltro' => $departamentosFiltro,
            'inventariosPaginados' => $inventariosPaginados,
            'inventariosCompletos' => $inventariosPaginados,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'categoriasTop' => AdminInventarioResource::obtenerCategoriasTop(),
            'selectedRows' => $this->selectedRows,
            'selectAll' => $this->selectAll,
            'showSidePanel' => $this->showSidePanel,
            'slideOverStep' => $this->slideOverStep,
            'modificarCampo' => $this->modificarCampo,
        ];
    }
}
