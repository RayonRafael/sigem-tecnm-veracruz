<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\InventarioResource;
use App\Filament\ServicioSocial\Resources\InventarioResource as SSInventarioResource;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Solicitud;
use App\Support\SigemExcelExporter;
use App\Support\SigemPdfGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Livewire\WithPagination;

trait ConDashboardComun
{
    use WithPagination;
    use ConAccionesMasivasInventario;

    public array $tableColumnSearches = [];
    public int $perPage = 10;
    public string $sortColumn = 'created_at';
    public string $sortDirection = 'desc';

    protected function esDashboardServicioSocial(): bool
    {
        return false;
    }

    public function updatedTableColumnSearches($value = null, $key = null): void
    {
        if ($key === 'departamento') {
            $this->tableColumnSearches['area'] = '';
            $this->tableColumnSearches['aula'] = '';
        } elseif ($key === 'area') {
            $this->tableColumnSearches['aula'] = '';
        }

        $this->limpiarSeleccionActiva();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->limpiarSeleccionActiva();
        $this->resetPage();
    }

    public function resetTableColumnSearches(): void
    {
        $this->tableColumnSearches = [];
        $this->limpiarSeleccionActiva();
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

    public function registrarEquipoAction(): Action
    {
        $action = CreateAction::make('registrarEquipo')
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

        if ($this->esDashboardServicioSocial()) {
            $action->mutateFormDataUsing(function (array $data): array {
                $data['aprobado'] = false;
                $data['estado_registro'] = 'Pendiente';
                $data['aprobado_por'] = null;
                $data['id_usuario'] = auth()->id();

                return $data;
            });
        }

        return $action;
    }

    public function verEquipoAction(): Action
    {
        $esSS = $this->esDashboardServicioSocial();

        return ViewAction::make('verEquipo')
            ->label('Ver equipo')
            ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
            ->modalDescription(fn (?Inventario $record) => new HtmlString(
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
            ->infolist(fn (Infolist $infolist): Infolist => $esSS ? SSInventarioResource::infolist($infolist) : InventarioResource::infolist($infolist))
            ->modalCancelActionLabel('Cerrar')
            ->extraModalFooterActions([
                Action::make('desde_detalle_editar')
                    ->label('Modificar')
                    ->color('warning')
                    ->icon('heroicon-m-pencil-square')
                    ->visible(fn (?Inventario $record) => $esSS
                        ? ($record && ! in_array($record->estado, ['Dado de baja', 'Cambio de área'], true))
                        : ($record && auth()->user()?->can('update', $record)))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('editarEquipo', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                Action::make('desde_detalle_cambio')
                    ->label('Cambio')
                    ->color('info')
                    ->icon('heroicon-m-arrows-right-left')
                    ->visible(fn (?Inventario $record) => $esSS
                        ? ($record && ! in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true))
                        : ($record && ! in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true) && auth()->user()?->can('update', $record)))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('" . ($esSS ? 'solicitarCambioArea' : 'cambiarArea') . "', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
                Action::make('desde_detalle_baja')
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-m-arrow-down-circle')
                    ->visible(fn (?Inventario $record) => $esSS
                        ? ($record && ! in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true))
                        : ($record && ! in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true) && auth()->user()?->can('delete', $record)))
                    ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedAction('" . ($esSS ? 'solicitarBaja' : 'darBaja') . "', { id: '{$record?->getKey()}', record: '{$record?->getKey()}' })"),
            ]);
    }

    public function verEquipo($id): void
    {
        $this->mountAction('verEquipo', ['id' => $id, 'record' => $id]);
    }

    public function checklistCambioAreaAction(): Action
    {
        return Action::make('checklistCambioArea')
            ->label('Generar Formato PDF')
            ->slideOver()
            ->modalHeading('Cambio registrado')
            ->modalDescription('Selecciona qué datos mostrar en el formato de Cambio de Área.')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;

                return $id ? Inventario::find($id) : null;
            })
            ->form(ConModalesBajaCambio::getChecklistCambioAreaSchema())
            ->modalSubmitActionLabel('Generar Formato PDF')
            ->modalCancelActionLabel('Cerrar')
            ->action(function (Inventario $record, array $data) {
                $campos = $data['campos_pdf'] ?? [];

                return SigemPdfGenerator::descargarCambioArea(
                    $record,
                    $record->especificaciones['cambio_motivo'] ?? null,
                    $record->especificaciones['jefe_destino'] ?? null,
                    null,
                    $campos
                );
            });
    }

    public function editarEquipoAction(): Action
    {
        $esSS = $this->esDashboardServicioSocial();

        $action = EditAction::make('editarEquipo')
            ->label('Editar equipo')
            ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
            ->modalDescription(fn (?Inventario $record) => new HtmlString(
                'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
            ))
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;

                return $id ? Inventario::with(['material.marca', 'material.tipo', 'area', 'salon'])->find($id) : null;
            })
            ->form($esSS ? SSInventarioResource::getEditFormTabs() : InventarioResource::getEditFormTabs())
            ->slideOver()
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->mutateRecordDataUsing(fn (array $data, ?Inventario $record = null): array => InventarioResource::hidratarDatosEdicion($data, $record))
            ->using(fn (Inventario $record, array $data): Inventario => InventarioResource::guardarCambiosEquipo($record, $data))
            ->successNotificationTitle('Registro actualizado correctamente');

        if ($esSS) {
            $action->authorize(true);
        }

        return $action;
    }

    public function editarEquipo($id): void
    {
        $this->mountAction('editarEquipo', ['id' => $id, 'record' => $id]);
    }

    protected function construirCambioAreaAction(bool $esDirecto): Action
    {
        return Action::make($esDirecto ? 'cambiarArea' : 'solicitarCambioArea')
            ->label($esDirecto ? 'Cambiar área' : 'Solicitar cambio de área')
            ->slideOver()
            ->modalHeading($esDirecto ? 'Cambiar área de equipo' : 'Solicitar cambio de área')
            ->modalDescription($esDirecto
                ? 'Se actualizará la ubicación física del equipo de forma inmediata.'
                : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;

                return $id ? Inventario::find($id) : null;
            })
            ->form(ConModalesBajaCambio::getModalCambioAreaFormSchema($esDirecto))
            ->modalSubmitActionLabel($esDirecto ? 'Confirmar cambio' : 'Solicitar cambio')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) use ($esDirecto) {
                $origenDeptoObj = $record->departamento ?? $record->area?->departamento;
                $origenAreaObj = $record->area;

                if ($esDirecto) {
                    $record->cambiarAreaDirecto($data['departamento_id'], $data['area_id'], $data['aula'] ?? null, null, $data['motivo'] ?? null, auth()->id());
                    ConModalesBajaCambio::guardarMetadatosCambioArea($record, $data, $origenDeptoObj, $origenAreaObj);
                    Notification::make()->title('Cambio registrado')->body('Área del equipo actualizada correctamente')->success()->send();
                } else {
                    $record->solicitarCambioArea($data['departamento_id'], $data['area_id'], $data['aula'] ?? null, null, $data['motivo'] ?? null, auth()->id());
                    ConModalesBajaCambio::guardarMetadatosCambioArea($record, $data, $origenDeptoObj, $origenAreaObj);
                    Notification::make()->title('Cambio registrado')->body('Solicitud de cambio de área enviada a revisión')->warning()->send();
                }

                $this->replaceMountedAction('checklistCambioArea', ['id' => $record->getKey(), 'record' => $record->getKey()]);
            });
    }

    protected function construirBajaAction(bool $esDirecto): Action
    {
        return Action::make($esDirecto ? 'darBaja' : 'solicitarBaja')
            ->label($esDirecto ? 'Dar de baja' : 'Solicitar baja')
            ->slideOver()
            ->modalHeading($esDirecto ? 'Confirmar baja de equipo' : 'Solicitar baja de equipo')
            ->modalDescription($esDirecto
                ? 'El equipo pasará a estado Dado de baja de manera inmediata.'
                : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
            ->model(Inventario::class)
            ->record(function (array $arguments) {
                $id = $arguments['id'] ?? $arguments['record'] ?? null;

                return $id ? Inventario::find($id) : null;
            })
            ->form(ConModalesBajaCambio::getModalBajaFormSchema())
            ->modalSubmitActionLabel($esDirecto ? 'Confirmar baja' : 'Solicitar baja')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Inventario $record, array $data) use ($esDirecto) {
                $datosAdicionales = ConModalesBajaCambio::extraerDatosAdicionalesBaja($data);
                if ($esDirecto) {
                    $record->darBajaDirecta($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                    Notification::make()->title('Equipo dado de baja correctamente')->success()->send();
                } else {
                    $record->solicitarBaja($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                    Notification::make()->title('Solicitud de baja enviada a revisión')->warning()->send();
                }
            });
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

    protected function obtenerDatosTablaYEstadoComun(): array
    {
        [$categoriasFiltro, $areasFiltroBase, $departamentosFiltro] = InventarioResource::obtenerOpcionesFiltrosDashboard();
        $deptoActivo = trim((string) ($this->tableColumnSearches['departamento'] ?? ''));
        $areasFiltro = ($deptoActivo !== '' && strtoupper($deptoActivo) !== 'TODOS')
            ? $this->areasFiltradas
            : $areasFiltroBase;
        $aulasFiltro = $this->aulasFiltradas;

        $inventariosPaginados = $this->getFilteredTableQuery()->paginate($this->perPage);
        $this->paginaActualIds = $inventariosPaginados
            ->getCollection()
            ->pluck('id_inventario')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();

        return [
            'categoriasFiltro' => $categoriasFiltro,
            'areasFiltro' => $areasFiltro,
            'aulasFiltro' => $aulasFiltro,
            'departamentosFiltro' => $departamentosFiltro,
            'inventariosPaginados' => $inventariosPaginados,
            'inventariosCompletos' => $inventariosPaginados,
            'paginaActualIds' => $this->paginaActualIds,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage,
            'categoriasTop' => InventarioResource::obtenerCategoriasTop(),
            'resumenRentas' => Inventario::resumenRentas(),
            'selectedRows' => $this->selectedRows,
            'selectAll' => $this->selectAll,
            'showSidePanel' => $this->showSidePanel,
            'slideOverStep' => $this->slideOverStep,
            'modificarCampo' => $this->modificarCampo,
        ];
    }

    protected function obtenerEstadisticasAdmin(): array
    {
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

        $pendientesStats = DB::selectOne("
            SELECT
                (SELECT COUNT(*) FROM mantenimiento WHERE estado IN ('Pendiente Revision Admin', 'Solicitado')) as mant_pendientes,
                (SELECT COUNT(*) FROM solicitud WHERE estado = 'Pendiente') as sol_pendientes
        ");

        return [
            'totalActivos' => (int) ($inventarioStats->total ?? 0),
            'mantenimientosPendientes' => (int) ($pendientesStats->mant_pendientes ?? 0),
            'materialesStockBajoCount' => Material::stockBajo()->count(),
            'solicitudesPendientes' => (int) ($pendientesStats->sol_pendientes ?? 0),
            'creadosEsteMes' => (int) ($inventarioStats->creados_este_mes ?? 0),
            'inventarioDisponibles' => (int) ($inventarioStats->disponibles ?? 0),
            'inventarioAsignados' => (int) ($inventarioStats->asignados ?? 0),
            'inventarioEnMantenimiento' => (int) ($inventarioStats->en_mantenimiento ?? 0),
            'inventarioDanados' => (int) ($inventarioStats->danados ?? 0),
            'inventarioSolicitudesBaja' => (int) ($inventarioStats->solicitudes_baja ?? 0),
            'inventarioSolicitudesCambioArea' => (int) ($inventarioStats->solicitudes_cambio_area ?? 0),
            'inventarioDadosDeBaja' => (int) ($inventarioStats->dados_de_baja ?? 0),
        ];
    }

    protected function obtenerEstadisticasServicioSocial(): array
    {
        $userId = auth()->id();

        $solicitudStats = Solicitud::toBase()
            ->where('id_usuario', $userId)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado = 'Pendiente' THEN 1 END) as pendientes,
                COUNT(CASE WHEN estado = 'Autorizada' THEN 1 END) as autorizadas
            ")
            ->first();

        $mantenimientoStats = Mantenimiento::toBase()
            ->where('id_usuario_solicita', $userId)
            ->selectRaw("
                COUNT(*) as total,
                COUNT(CASE WHEN estado IN ('Pendiente Revision Admin', 'Solicitado') THEN 1 END) as en_revision
            ")
            ->first();

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

        return [
            'misSolicitudes' => (int) ($solicitudStats->total ?? 0),
            'misSolicitudesPendientes' => (int) ($solicitudStats->pendientes ?? 0),
            'solicitudesAutorizadas' => (int) ($solicitudStats->autorizadas ?? 0),
            'misMantenimientos' => (int) ($mantenimientoStats->total ?? 0),
            'mantenimientoEnRevision' => (int) ($mantenimientoStats->en_revision ?? 0),
            'totalActivos' => (int) ($inventarioStats->total ?? 0),
            'inventarioDisponibles' => (int) ($inventarioStats->disponibles ?? 0),
            'inventarioAsignados' => (int) ($inventarioStats->asignados ?? 0),
            'inventarioEnMantenimiento' => (int) ($inventarioStats->en_mantenimiento ?? 0),
            'inventarioDanados' => (int) ($inventarioStats->danados ?? 0),
            'inventarioSolicitudesBaja' => (int) ($inventarioStats->solicitudes_baja ?? 0),
            'inventarioSolicitudesCambioArea' => (int) ($inventarioStats->solicitudes_cambio_area ?? 0),
            'inventarioDadosDeBaja' => (int) ($inventarioStats->dados_de_baja ?? 0),
        ];
    }
}
