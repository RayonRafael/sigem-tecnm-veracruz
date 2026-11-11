<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemExcelExporter;
use App\Support\SigemPdfGenerator;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reportes';

    protected static ?string $title = '📊 Reportes';

    protected static string $view = 'filament.pages.reportes';

    public function getTitle(): string
    {
        return '📊 Reportes';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public ?string $reporteActivo = null;

    public array $visibilidadServicioSocial = [];

    public ?string $formatoFechaDesde = null;

    public ?string $formatoFechaHasta = null;

    public ?string $formatoDepartamentoId = '';

    public ?string $formatoSeleccionadoEntrega = '';

    public ?string $formatoSeleccionadoOrden = '';

    public ?string $formatoSeleccionadoVerificacion = '';

    /** @var array<int|string> */
    public array $formatosSeleccionadosBaja = [];

    public string $formatoFiltroTipo = 'baja';

    public function mount(): void
    {
        $defaults = [
            'inventario_categoria'     => true,
            'equipos_area'             => true,
            'equipos_mantenimiento'    => true,
            'equipos_baja_danados'     => true,
            'solicitudes_tipo_estado'  => true,
            'movimientos_recientes'    => true,
        ];

        $cached = Cache::get('sigem_reportes_visibilidad_ss', []);
        $this->visibilidadServicioSocial = array_merge($defaults, is_array($cached) ? $cached : []);
    }

    public function toggleVisibilidad(string $reportId): void
    {
        $current = $this->visibilidadServicioSocial[$reportId] ?? false;
        $this->visibilidadServicioSocial[$reportId] = ! $current;

        Cache::forever('sigem_reportes_visibilidad_ss', $this->visibilidadServicioSocial);

        $estado = $this->visibilidadServicioSocial[$reportId] ? 'visible' : 'oculto';
        Notification::make()
            ->title("Reporte configurado como {$estado} para Servicio Social")
            ->success()
            ->send();
    }

    public function verReporte(string $reportId): void
    {
        $this->reporteActivo = $reportId;
        $this->dispatch('scrollToReport');
    }

    public function cerrarReporte(): void
    {
        $this->reporteActivo = null;
    }

    /**
     * Exporta el reporte activo a formato Excel (.xlsx).
     */
    public function exportarExcel(string $reportId): ?BinaryFileResponse
    {
        $reporte = $this->getDatosReporte($reportId, 'xlsx');
        if (! $reporte || empty($reporte['rows'])) {
            Notification::make()
                ->title('No hay registros disponibles para exportar')
                ->warning()
                ->send();

            return null;
        }

        $fileName = 'reporte-' . str_replace('_', '-', $reportId) . '-' . now()->format('Y-m-d-His') . '.xlsx';

        return SigemExcelExporter::export(
            $reporte['titulo'],
            $reporte['headers'],
            $reporte['rows'],
            $fileName
        );
    }

    /**
     * Exporta el reporte a CSV (por defecto para compatibilidad con suite de pruebas) o Excel.
     */
    public function exportarReporte(string $reportId, string $formato = 'csv')
    {
        if ($formato === 'xlsx') {
            return $this->exportarExcel($reportId);
        }

        $reporte = $this->getDatosReporte($reportId, 'csv');
        if (! $reporte || empty($reporte['rows'])) {
            Notification::make()
                ->title('No hay registros disponibles para exportar')
                ->warning()
                ->send();

            return null;
        }

        $fileName = 'reporte-' . str_replace('_', '-', $reportId) . '-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($reporte) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, $reporte['headers']);
            foreach ($reporte['rows'] as $row) {
                $cleanRow = array_map(function ($val) {
                    return trim(strip_tags((string) $val));
                }, $row);
                fputcsv($output, $cleanRow);
            }
            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    public function getReportes(): array
    {
        return [
            [
                'id' => 'inventario_categoria',
                'emoji' => '📦',
                'titulo' => 'Inventario por categoría',
                'descripcion' => 'Distribución y conteo de equipos y materiales agrupados por tipo y su estado de disponibilidad.',
            ],
            [
                'id' => 'equipos_area',
                'emoji' => '🏢',
                'titulo' => 'Equipos por área',
                'descripcion' => 'Ubicación física y asignación de activos por departamento, área y estatus de operación.',
            ],
            [
                'id' => 'equipos_mantenimiento',
                'emoji' => '🔧',
                'titulo' => 'Equipos en mantenimiento',
                'descripcion' => 'Historial y control de órdenes de servicio técnico, fallas registradas y estado de revisión.',
            ],
            [
                'id' => 'equipos_baja_danados',
                'emoji' => '⚠️',
                'titulo' => 'Equipos con solicitud de baja',
                'descripcion' => 'Listado de activos con solicitud de baja o dados de baja en el sistema, con daño severo o solicitudes rechazadas.',
            ],
            [
                'id' => 'solicitudes_tipo_estado',
                'emoji' => '📋',
                'titulo' => 'Solicitudes de material',
                'descripcion' => 'Resumen de asignaciones temporales, permanentes o rentas con solicitante y receptor.',
            ],
            [
                'id' => 'movimientos_recientes',
                'emoji' => '📝',
                'titulo' => 'Movimientos SS',
                'descripcion' => 'Registro cronológico de actividades y modificaciones realizadas en el sistema.',
            ],
        ];
    }

    /**
     * Metadata de los 4 Formatos Institucionales en PDF del TecNM Veracruz.
     *
     * @return list<array{id: string, emoji: string, titulo: string, subtitulo: string, descripcion: string}>
     */
    public function getFormatosInstitucionales(): array
    {
        return [
            [
                'id' => 'entrega',
                'emoji' => '📄',
                'titulo' => 'Entrega de Equipo',
                'subtitulo' => 'Formato 1',
                'descripcion' => 'Genera acta oficial de entrega de equipo o bien con receptor y unidad orgánica.',
            ],
            [
                'id' => 'orden_trabajo',
                'emoji' => '📄',
                'titulo' => 'Orden de Trabajo',
                'subtitulo' => 'Formato 2',
                'descripcion' => 'Genera orden de trabajo de mantenimiento y servicio técnico del Centro de Cómputo.',
            ],
            [
                'id' => 'lista_verificacion',
                'emoji' => '📄',
                'titulo' => 'Lista Verificación',
                'subtitulo' => 'Formato 3',
                'descripcion' => 'Genera lista de verificación con hallazgo, atención y validación técnica.',
            ],
            [
                'id' => 'baja',
                'emoji' => '📄',
                'titulo' => 'Trámite de Baja',
                'subtitulo' => 'Formato 4',
                'descripcion' => 'Genera trámite de baja de mobiliario y equipo individual o en lote multipágina.',
            ],
        ];
    }

    public function updatedFormatoFechaDesde(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    public function updatedFormatoFechaHasta(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    public function updatedFormatoDepartamentoId(): void
    {
        $this->reiniciarSeleccionesFormatos();
    }

    protected function reiniciarSeleccionesFormatos(): void
    {
        $this->formatoSeleccionadoEntrega = '';
        $this->formatoSeleccionadoOrden = '';
        $this->formatoSeleccionadoVerificacion = '';
        $this->formatosSeleccionadosBaja = [];
    }

    public function limpiarFiltrosFormatos(): void
    {
        $this->formatoFechaDesde = null;
        $this->formatoFechaHasta = null;
        $this->formatoDepartamentoId = '';
        $this->reiniciarSeleccionesFormatos();
    }

    protected function esUsuarioAdminReportes(): bool
    {
        $user = auth()->user();
        if (! $user || $this instanceof \App\Filament\ServicioSocial\Pages\ReportesPage) {
            return false;
        }

        return $user->hasRole(RoleEnum::ADMIN->value)
            || $user->hasRole('admin')
            || $user->can(RoleEnum::PERM_ACCESS_ADMIN);
    }

    protected function aplicarFiltroFechaFormato($query, string $columnaFecha): void
    {
        if (filled($this->formatoFechaDesde)) {
            $desde = $this->formatoFechaDesde;
            $query->where(function ($q) use ($columnaFecha, $desde): void {
                $q->whereDate($columnaFecha, '>=', $desde)
                    ->orWhere(fn ($sub) => $sub->whereNull($columnaFecha)->whereDate('created_at', '>=', $desde));
            });
        }

        if (filled($this->formatoFechaHasta)) {
            $hasta = $this->formatoFechaHasta;
            $query->where(function ($q) use ($columnaFecha, $hasta): void {
                $q->whereDate($columnaFecha, '<=', $hasta)
                    ->orWhere(fn ($sub) => $sub->whereNull($columnaFecha)->whereDate('created_at', '<=', $hasta));
            });
        }
    }

    protected function aplicarFiltroDeptoInventario($query, ?int $deptoId): void
    {
        if (! $deptoId) {
            return;
        }

        $query->where(function ($q) use ($deptoId): void {
            $q->where('departamento_id', $deptoId)
                ->orWhereHas('area', fn ($a) => $a->where('id_departamento', $deptoId));
        });
    }

    public function getDepartamentosParaFiltros(): array
    {
        return Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray();
    }

    public function getSolicitudesParaFormato(): \Illuminate\Support\Collection
    {
        $query = Solicitud::query()
            ->with(['receptor.area.departamento', 'detalles.material'])
            ->where('estado', '!=', 'Rechazado')
            ->whereIn('tipo_movimiento', [
                'Asignacion Permanente',
                'Asignacion Temporal',
                'Renta Externa',
                'Salida',
                'Traslado',
                'Préstamo',
                'Adaptación',
            ]);

        if (! $this->esUsuarioAdminReportes()) {
            $query->where('id_usuario', auth()->id() ?? 0);
        }

        $this->aplicarFiltroFechaFormato($query, 'fecha_solicitud');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        if ($deptoId) {
            $query->whereHas('receptor.area', fn ($a) => $a->where('id_departamento', $deptoId));
        }

        return $query->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_solicitud')
            ->limit(100)
            ->get();
    }

    protected function baseQueryMantenimientosParaFormato()
    {
        $query = Mantenimiento::query()
            ->with(['inventario.material', 'inventario.departamento', 'inventario.area.departamento'])
            ->where('estado', '!=', 'Cancelado');

        if (! $this->esUsuarioAdminReportes()) {
            $query->where('id_usuario_solicita', auth()->id() ?? 0);
        }

        $this->aplicarFiltroFechaFormato($query, 'fecha_solicitud');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        if ($deptoId) {
            $query->whereHas('inventario', fn ($inv) => $this->aplicarFiltroDeptoInventario($inv, $deptoId));
        }

        return $query;
    }

    public function getMantenimientosParaOrdenFormato(): \Illuminate\Support\Collection
    {
        return $this->baseQueryMantenimientosParaFormato()
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_mantenimiento')
            ->limit(100)
            ->get();
    }

    public function getMantenimientosParaVerificacionFormato(): \Illuminate\Support\Collection
    {
        return $this->baseQueryMantenimientosParaFormato()
            ->orderByRaw("CASE WHEN estado = 'Completado' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_fin')
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_mantenimiento')
            ->limit(100)
            ->get();
    }

    public function getInventariosBajaParaFormato(): \Illuminate\Support\Collection
    {
        $query = Inventario::query()
            ->with(['material.marca', 'departamento', 'area.departamento'])
            ->whereIn('estado', SigemBajaFormatHelper::ESTADOS_BAJA);

        $this->aplicarFiltroFechaFormato($query, 'fecha_baja');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        $this->aplicarFiltroDeptoInventario($query, $deptoId);

        return $query->orderByDesc('fecha_baja')
            ->orderByDesc('id_inventario')
            ->limit(SigemBajaFormatHelper::MAX_LOTE_BAJAS)
            ->get();
    }

    /**
     * Resuelve un registro de formato verificando propiedad del usuario cuando no es Administrador.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param  \Illuminate\Support\Collection<int, TModel>  $candidatos
     * @param  class-string<TModel>  $modeloClase
     * @return TModel|null
     */
    protected function resolverRegistroFormatoAutorizado(
        \Illuminate\Support\Collection $candidatos,
        ?string $seleccionId,
        string $clavePk,
        string $modeloClase,
        string $campoUsuario,
        string $mensajeSinRegistros
    ) {
        $seleccionadoId = filled($seleccionId) ? (int) $seleccionId : null;

        if ($seleccionadoId !== null && ! $this->esUsuarioAdminReportes()) {
            $registroDestino = $modeloClase::find($seleccionadoId);
            if ($registroDestino && (int) $registroDestino->{$campoUsuario} !== (int) (auth()->id() ?? 0)) {
                abort(403, 'No tienes permiso para descargar formatos de otros usuarios.');
            }
        }

        $registro = $seleccionadoId !== null
            ? $candidatos->firstWhere($clavePk, $seleccionadoId)
            : $candidatos->first();

        if (! $registro) {
            $this->notificarSinRegistrosFormato($mensajeSinRegistros);

            return null;
        }

        if (! $this->esUsuarioAdminReportes() && (int) $registro->{$campoUsuario} !== (int) (auth()->id() ?? 0)) {
            abort(403, 'No tienes permiso para descargar formatos de otros usuarios.');
        }

        return $registro;
    }

    public function descargarFormatoInstitucional(string $formatoId)
    {
        if ($formatoId === 'entrega') {
            $solicitud = $this->resolverRegistroFormatoAutorizado(
                $this->getSolicitudesParaFormato(),
                $this->formatoSeleccionadoEntrega,
                'id_solicitud',
                Solicitud::class,
                'id_usuario',
                'No hay solicitudes de entrega que coincidan con los filtros.'
            );

            return $solicitud ? SigemPdfGenerator::descargarActaEntrega($solicitud) : null;
        }

        if ($formatoId === 'orden_trabajo') {
            $mant = $this->resolverRegistroFormatoAutorizado(
                $this->getMantenimientosParaOrdenFormato(),
                $this->formatoSeleccionadoOrden,
                'id_mantenimiento',
                Mantenimiento::class,
                'id_usuario_solicita',
                'No hay mantenimientos que coincidan con los filtros.'
            );

            return $mant ? SigemPdfGenerator::descargarOrdenTrabajo($mant) : null;
        }

        if ($formatoId === 'lista_verificacion') {
            $mant = $this->resolverRegistroFormatoAutorizado(
                $this->getMantenimientosParaVerificacionFormato(),
                $this->formatoSeleccionadoVerificacion,
                'id_mantenimiento',
                Mantenimiento::class,
                'id_usuario_solicita',
                'No hay mantenimientos disponibles para Lista de Verificación.'
            );

            return $mant ? SigemPdfGenerator::descargarListaVerificacion($mant) : null;
        }

        if ($formatoId === 'baja') {
            $candidatos = $this->getInventariosBajaParaFormato();
            $ids = array_values(array_filter(array_map('intval', $this->formatosSeleccionadosBaja)));
            $bajas = ! empty($ids)
                ? $candidatos->whereIn('id_inventario', $ids)->values()
                : $candidatos;

            if ($bajas->isEmpty()) {
                $this->notificarSinRegistrosFormato('No hay equipos dados de baja o en solicitud que coincidan con los filtros.');
                return null;
            }

            if ($bajas->count() > SigemBajaFormatHelper::MAX_LOTE_BAJAS) {
                $bajas = $bajas->take(SigemBajaFormatHelper::MAX_LOTE_BAJAS)->values();
                Notification::make()
                    ->title('Lote limitado a ' . SigemBajaFormatHelper::MAX_LOTE_BAJAS . ' equipos')
                    ->body('Se generará el PDF con los primeros ' . SigemBajaFormatHelper::MAX_LOTE_BAJAS . ' equipos para optimizar el rendimiento.')
                    ->warning()
                    ->send();
            }

            if ($bajas->count() === 1) {
                return SigemPdfGenerator::descargarFormatoBaja($bajas->first());
            }

            return SigemPdfGenerator::descargarFormatosBajaLote($bajas);
        }

        return null;
    }

    public function generarFormatosFiltrados()
    {
        return $this->descargarFormatoInstitucional($this->formatoFiltroTipo);
    }

    protected function notificarSinRegistrosFormato(string $mensaje): void
    {
        Notification::make()
            ->title($mensaje)
            ->warning()
            ->send();
    }

    public function getDatosReporte(string $reportId, string $formato = 'pantalla'): ?array
    {
        $fecha = Carbon::now()->format('d/m/Y H:i');

        return match ($reportId) {
            'inventario_categoria' => $this->generarReporteInventarioCategoria($fecha, $formato),
            'equipos_area' => $this->generarReporteEquiposArea($fecha, $formato),
            'equipos_mantenimiento' => $this->generarReporteEquiposMantenimiento($fecha, $formato),
            'equipos_baja_danados' => $this->generarReporteEquiposBajaDanados($fecha, $formato),
            'solicitudes_tipo_estado' => $this->generarReporteSolicitudes($fecha, $formato),
            'movimientos_recientes' => $this->generarReporteMovimientos($fecha, $formato),
            default => null,
        };
    }

    protected function generarReporteInventarioCategoria(string $fecha, string $formato): array
    {
        $tipos = TipoMaterial::orderBy('nombre')->get();
        $inventariosPorTipo = Inventario::join('material', 'inventario.id_producto', '=', 'material.id_producto')
            ->select([
                'inventario.id_inventario',
                'inventario.tipo_propiedad',
                'inventario.estado',
                'inventario.estado_registro',
                'material.id_tipodematerial',
            ])
            ->get()
            ->groupBy('id_tipodematerial');
        $rows = [];

        if ($formato === 'csv') {
            foreach ($tipos as $tipo) {
                $inventarios = $inventariosPorTipo->get($tipo->id_tipodematerial, collect());

                $total = $inventarios->count();
                if ($total === 0) {
                    continue;
                }

                $propios = $inventarios->where('tipo_propiedad', 'Propio')->count();
                $rentados = $inventarios->where('tipo_propiedad', 'Rentado')->count();

                $rows[] = [
                    'categoria' => e($tipo->nombre),
                    'total' => (string) $total,
                    'propio' => (string) $propios,
                    'renta' => (string) $rentados,
                    'disponibles' => (string) $inventarios->where('estado', 'Disponible')->count(),
                    'asignados' => (string) $inventarios->where('estado', 'Asignado')->count(),
                    'mantenimiento' => (string) $inventarios->where('estado', 'En Mantenimiento')->count(),
                    'danados' => (string) $inventarios->whereIn('estado', ['Dañado', 'Baja'])->count(),
                ];
            }

            return [
                'id' => 'inventario_categoria',
                'emoji' => '📦',
                'titulo' => 'Inventario por categoría',
                'fecha' => $fecha,
                'headers' => ['Categoría / Tipo', 'Total', 'Propio', 'Renta', 'Disponibles', 'Asignados', 'En Mantenimiento', 'Dañados / Baja'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Categoría, Total, Disponible, En mantto, Asignado, Cambio área, Solicitud baja
        foreach ($tipos as $tipo) {
            $inventarios = $inventariosPorTipo->get($tipo->id_tipodematerial, collect());

            $total = $inventarios->count();
            if ($total === 0) {
                continue;
            }

            $disp = $inventarios->where('estado', 'Disponible')->count();
            $mantto = $inventarios->where('estado', 'En Mantenimiento')->count();
            $asig = $inventarios->where('estado', 'Asignado')->count();
            $cambio = $inventarios->where('estado', 'Reasignado')->count();
            $baja = $inventarios->where(function ($i) {
                return in_array($i->estado, ['Dañado', 'Baja']) || $i->estado_registro === 'Rechazado';
            })->count();

            if ($formato === 'xlsx') {
                $rows[] = [
                    'categoria' => $tipo->nombre,
                    'total' => $total,
                    'disponible' => $disp,
                    'en_mantto' => $mantto,
                    'asignado' => $asig,
                    'cambio_area' => $cambio,
                    'solicitud_baja' => $baja,
                ];
            } else {
                $rows[] = [
                    'categoria' => '<strong>' . e($tipo->nombre) . '</strong>',
                    'total' => '<span class="sigem-td-folio">' . $total . '</span>',
                    'disponible' => $this->renderBadgeConteo($disp, 'ok'),
                    'en_mantto' => $this->renderBadgeConteo($mantto, 'warn'),
                    'asignado' => $this->renderBadgeConteo($asig, 'info'),
                    'cambio_area' => $this->renderBadgeConteo($cambio, 'neutral'),
                    'solicitud_baja' => $this->renderBadgeConteo($baja, 'danger'),
                ];
            }
        }

        return [
            'id' => 'inventario_categoria',
            'emoji' => '📦',
            'titulo' => 'Inventario por categoría',
            'fecha' => $fecha,
            'headers' => ['Categoría', 'Total', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteEquiposArea(string $fecha, string $formato): array
    {
        $areas = Area::with('departamento')->orderBy('nombre')->get();
        $inventarios = Inventario::select(['id_inventario', 'area_id', 'ubicacion_fisica', 'especificaciones', 'tipo_propiedad', 'estado', 'estado_registro'])->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($areas as $area) {
                $areaNombre = $area->nombre;
                $areaIdStr = (string) $area->id_area;

                $areaInventarios = $inventarios->filter(function ($inv) use ($areaNombre, $areaIdStr) {
                    if ((string) ($inv->area_id ?? '') === $areaIdStr) {
                        return true;
                    }
                    if ($inv->ubicacion_fisica && stripos($inv->ubicacion_fisica, $areaNombre) !== false) {
                        return true;
                    }
                    $espec = is_array($inv->especificaciones) ? $inv->especificaciones : json_decode($inv->especificaciones ?? '[]', true);

                    return isset($espec['area_id']) && (string) $espec['area_id'] === $areaIdStr;
                });

                $total = $areaInventarios->count();
                if ($total === 0) {
                    continue;
                }

                $propios = $areaInventarios->where('tipo_propiedad', 'Propio')->count();
                $rentados = $areaInventarios->where('tipo_propiedad', 'Rentado')->count();

                $rows[] = [
                    'depto' => $area->departamento?->nombre ?? 'N/A',
                    'area' => $area->nombre,
                    'total' => (string) $total,
                    'propio' => (string) $propios,
                    'renta' => (string) $rentados,
                    'disponibles' => (string) $areaInventarios->where('estado', 'Disponible')->count(),
                    'asignados' => (string) $areaInventarios->where('estado', 'Asignado')->count(),
                ];
            }

            return [
                'id' => 'equipos_area',
                'emoji' => '🏢',
                'titulo' => 'Equipos por área',
                'fecha' => $fecha,
                'headers' => ['Departamento', 'Área', 'Total Activos', 'Propio', 'Renta', 'Disponibles', 'Asignados'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Área, Total, Disponible, En mantto, Asignado, Cambio área, Solicitud baja
        foreach ($areas as $area) {
            $areaNombre = $area->nombre;
            $areaIdStr = (string) $area->id_area;

            $areaInventarios = $inventarios->filter(function ($inv) use ($areaNombre, $areaIdStr) {
                if ((string) ($inv->area_id ?? '') === $areaIdStr) {
                    return true;
                }
                if ($inv->ubicacion_fisica && stripos($inv->ubicacion_fisica, $areaNombre) !== false) {
                    return true;
                }
                $espec = is_array($inv->especificaciones) ? $inv->especificaciones : json_decode($inv->especificaciones ?? '[]', true);

                return isset($espec['area_id']) && (string) $espec['area_id'] === $areaIdStr;
            });

            $total = $areaInventarios->count();
            if ($total === 0) {
                continue;
            }

            $disp = $areaInventarios->where('estado', 'Disponible')->count();
            $mantto = $areaInventarios->where('estado', 'En Mantenimiento')->count();
            $asig = $areaInventarios->where('estado', 'Asignado')->count();
            $cambio = $areaInventarios->where('estado', 'Reasignado')->count();
            $baja = $areaInventarios->where(function ($i) {
                return in_array($i->estado, ['Dañado', 'Baja']) || $i->estado_registro === 'Rechazado';
            })->count();

            if ($formato === 'xlsx') {
                $rows[] = [
                    'area' => $area->nombre . ($area->departamento ? ' (' . $area->departamento->nombre . ')' : ''),
                    'total' => $total,
                    'disponible' => $disp,
                    'en_mantto' => $mantto,
                    'asignado' => $asig,
                    'cambio_area' => $cambio,
                    'solicitud_baja' => $baja,
                ];
            } else {
                $rows[] = [
                    'area' => '<strong>' . e($area->nombre) . '</strong>' . ($area->departamento ? '<div style="font-size:12px;color:var(--sig-muted);">' . e($area->departamento->nombre) . '</div>' : ''),
                    'total' => '<span class="sigem-td-folio">' . $total . '</span>',
                    'disponible' => $this->renderBadgeConteo($disp, 'ok'),
                    'en_mantto' => $this->renderBadgeConteo($mantto, 'warn'),
                    'asignado' => $this->renderBadgeConteo($asig, 'info'),
                    'cambio_area' => $this->renderBadgeConteo($cambio, 'neutral'),
                    'solicitud_baja' => $this->renderBadgeConteo($baja, 'danger'),
                ];
            }
        }

        return [
            'id' => 'equipos_area',
            'emoji' => '🏢',
            'titulo' => 'Equipos por área',
            'fecha' => $fecha,
            'headers' => ['Área', 'Total', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteEquiposMantenimiento(string $fecha, string $formato): array
    {
        $mantenimientos = Mantenimiento::with(['inventario.material.tipo', 'inventario.area', 'inventario.salon'])->latest('fecha_solicitud')->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($mantenimientos as $m) {
                $esPropio = ($m->inventario?->tipo_propiedad ?? 'Propio') === 'Propio';
                $badgePropiedad = $esPropio ? 'Propio' : 'Rentado';

                $rows[] = [
                    'serie' => $m->inventario?->num_serie ?? 'S/N',
                    'equipo' => $m->inventario?->material?->nombre ?? 'N/A',
                    'propiedad' => $badgePropiedad,
                    'servicio' => $m->tipo_servicio,
                    'mantenimiento' => $m->tipo_mantenimiento,
                    'tecnico' => $m->nombre_tecnico,
                    'fecha' => $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : 'N/A',
                    'estado' => $m->estado,
                ];
            }

            return [
                'id' => 'equipos_mantenimiento',
                'emoji' => '🔧',
                'titulo' => 'Equipos en mantenimiento',
                'fecha' => $fecha,
                'headers' => ['No. Serie', 'Equipo / Material', 'Propiedad', 'Servicio', 'Tipo', 'Técnico', 'Fecha', 'Estado'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Código, Equipo, Categoría, Área, Aula
        foreach ($mantenimientos as $m) {
            $inv = $m->inventario;
            $espec = is_array($inv?->especificaciones) ? $inv->especificaciones : json_decode($inv?->especificaciones ?? '[]', true);
            $aula = $inv?->salon?->nombre ?? ($espec['aula'] ?? '—');
            $codigo = $inv?->codigo ?: ($inv?->num_serie ?: ('#' . $m->id_inventario));
            $equipo = $inv?->material?->nombre ?? $inv?->subtipo ?? 'N/A';
            $categoria = $inv?->material?->tipo?->nombre ?? $inv?->subtipo ?? 'General';
            $area = $inv?->area?->nombre ?? ($inv?->ubicacion_fisica ?: 'Centro de Cómputo');

            if ($formato === 'xlsx') {
                $rows[] = [
                    'codigo' => $codigo,
                    'equipo' => $equipo,
                    'categoria' => $categoria,
                    'area' => $area,
                    'aula' => $aula,
                ];
            } else {
                $rows[] = [
                    'codigo' => '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                    'equipo' => '<strong>' . e($equipo) . '</strong>',
                    'categoria' => '<span class="sigem-pill neutral">' . e($categoria) . '</span>',
                    'area' => e($area),
                    'aula' => '<span class="sigem-td-folio">' . e($aula) . '</span>',
                ];
            }
        }

        return [
            'id' => 'equipos_mantenimiento',
            'emoji' => '🔧',
            'titulo' => 'Equipos en mantenimiento',
            'fecha' => $fecha,
            'headers' => ['Código', 'Equipo', 'Categoría', 'Área', 'Aula'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteEquiposBajaDanados(string $fecha, string $formato): array
    {
        $inventarios = Inventario::with(['material.tipo', 'area'])
            ->where(function ($q) {
                $q->whereIn('estado', ['Dañado', 'Baja'])
                    ->orWhere('estado_registro', 'Rechazado');
            })
            ->latest('updated_at')
            ->get();

        $rows = [];

        if ($formato === 'csv') {
            foreach ($inventarios as $i) {
                $fFecha = $i->fecha_baja ? $i->fecha_baja->format('d/m/Y') : ($i->fecha_registro ? $i->fecha_registro->format('d/m/Y') : 'N/A');
                $rows[] = [
                    'serie' => $i->num_serie ?? 'S/N',
                    'material' => $i->material?->nombre ?? 'N/A',
                    'propiedad' => $i->tipo_propiedad ?? 'Propio',
                    'ubicacion' => $i->ubicacion_fisica ?? 'No especificada',
                    'estado' => $i->estado,
                    'registro' => $i->estado_registro ?? 'Pendiente',
                    'fecha' => $fFecha,
                ];
            }

            return [
                'id' => 'equipos_baja_danados',
                'emoji' => '⚠️',
                'titulo' => 'Equipos con solicitud de baja',
                'fecha' => $fecha,
                'headers' => ['No. Serie', 'Material', 'Propiedad', 'Ubicación', 'Estado', 'Registro', 'Fecha'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Código, Equipo, Categoría, Área, Último movimiento
        foreach ($inventarios as $i) {
            $fFecha = $i->fecha_baja ? $i->fecha_baja->format('d/m/Y') : ($i->updated_at ? $i->updated_at->format('d/m/Y') : 'N/A');
            $codigo = $i->codigo ?: ($i->num_serie ?: ('#' . $i->id_inventario));
            $equipo = $i->material?->nombre ?? $i->subtipo ?? 'N/A';
            $categoria = $i->material?->tipo?->nombre ?? $i->subtipo ?? 'General';
            $area = $i->area?->nombre ?? ($i->ubicacion_fisica ?: 'Centro de Cómputo');

            if ($formato === 'xlsx') {
                $rows[] = [
                    'codigo' => $codigo,
                    'equipo' => $equipo,
                    'categoria' => $categoria,
                    'area' => $area,
                    'ultimo_movimiento' => $fFecha,
                ];
            } else {
                $rows[] = [
                    'codigo' => '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                    'equipo' => '<strong>' . e($equipo) . '</strong>',
                    'categoria' => '<span class="sigem-pill neutral">' . e($categoria) . '</span>',
                    'area' => e($area),
                    'ultimo_movimiento' => '<span class="sigem-td-folio">' . e($fFecha) . '</span>',
                ];
            }
        }

        return [
            'id' => 'equipos_baja_danados',
            'emoji' => '⚠️',
            'titulo' => 'Equipos con solicitud de baja',
            'fecha' => $fecha,
            'headers' => ['Código', 'Equipo', 'Categoría', 'Área', 'Último movimiento'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteSolicitudes(string $fecha, string $formato): array
    {
        $solicitudes = Solicitud::with(['usuario', 'receptor.area.departamento'])->latest('fecha_solicitud')->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($solicitudes as $s) {
                $rows[] = [
                    'folio' => 'FOLIO-' . str_pad($s->id_solicitud, 5, '0', STR_PAD_LEFT),
                    'tipo' => (string) $s->tipo_movimiento,
                    'solicitante' => $s->usuario?->name ?? 'N/A',
                    'receptor' => $s->receptor?->nombre ?? 'N/A',
                    'departamento' => $s->receptor?->area?->departamento?->nombre ?? 'N/A',
                    'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : 'N/A',
                    'estado' => (string) $s->estado,
                ];
            }

            return [
                'id' => 'solicitudes_tipo_estado',
                'emoji' => '📋',
                'titulo' => 'Solicitudes de material',
                'fecha' => $fecha,
                'headers' => ['Folio', 'Tipo Movimiento', 'Solicitante', 'Receptor', 'Departamento', 'Fecha', 'Estado'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Tipo, Pendiente, Autorizado, Entregado, Rechazado, Total
        $tiposMov = [
            'Salida' => ['Salida', 'Asignacion Permanente'],
            'Entrada' => ['Entrada', 'Asignacion Temporal'],
            'Traslado' => ['Traslado', 'Renta Externa'],
        ];

        foreach ($tiposMov as $tipoVisual => $matchingKeys) {
            $grupo = $solicitudes->filter(fn ($s) => in_array($s->tipo_movimiento, $matchingKeys));
            $pend = $grupo->where('estado', 'Pendiente')->count();
            $aut = $grupo->where('estado', 'Autorizado')->count();
            $ent = $grupo->where('estado', 'Entregado')->count();
            $rech = $grupo->whereIn('estado', ['Rechazado', 'Cancelado'])->count();
            $tot = $grupo->count();

            if ($formato === 'xlsx') {
                $rows[] = [
                    'tipo' => $tipoVisual,
                    'pendiente' => $pend,
                    'autorizado' => $aut,
                    'entregado' => $ent,
                    'rechazado' => $rech,
                    'total' => $tot,
                ];
            } else {
                $rows[] = [
                    'tipo' => '<strong>' . e($tipoVisual) . '</strong>',
                    'pendiente' => $this->renderBadgeConteo($pend, 'warn'),
                    'autorizado' => $this->renderBadgeConteo($aut, 'ok'),
                    'entregado' => $this->renderBadgeConteo($ent, 'info'),
                    'rechazado' => $this->renderBadgeConteo($rech, 'danger'),
                    'total' => '<span class="sigem-td-folio">' . $tot . '</span>',
                ];
            }
        }

        return [
            'id' => 'solicitudes_tipo_estado',
            'emoji' => '📋',
            'titulo' => 'Solicitudes de material',
            'fecha' => $fecha,
            'headers' => ['Tipo', 'Pendiente', 'Autorizado', 'Entregado', 'Rechazado', 'Total'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteMovimientos(string $fecha, string $formato): array
    {
        $activities = Activity::with('causer')->latest()->limit(50)->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($activities as $a) {
                $rows[] = [
                    'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A',
                    'usuario' => $a->causer?->name ?? 'Sistema',
                    'accion' => $a->event ?? $a->description ?? 'evento',
                    'modulo' => class_basename($a->subject_type ?? 'Sistema'),
                    'descripcion' => $a->description ?? 'Sin detalles',
                ];
            }

            return [
                'id' => 'movimientos_recientes',
                'emoji' => '📝',
                'titulo' => 'Movimientos SS',
                'fecha' => $fecha,
                'headers' => ['Fecha y Hora', 'Usuario', 'Acción', 'Módulo', 'Descripción'],
                'rows' => $rows,
            ];
        }

        // Formato Mockup (Pantalla / Excel): Fecha, Usuario, Acción, Detalle
        foreach ($activities as $a) {
            $fFecha = $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A';
            $usuario = $a->causer?->name ?? 'Sistema';
            $accion = ucfirst($a->event ?? $a->description ?? 'Actividad');
            $detalle = $a->description ?? 'Sin detalle adicional';

            if ($formato === 'xlsx') {
                $rows[] = [
                    'fecha' => $fFecha,
                    'usuario' => $usuario,
                    'accion' => $accion,
                    'detalle' => $detalle,
                ];
            } else {
                $rows[] = [
                    'fecha' => '<span class="sigem-td-folio">' . e($fFecha) . '</span>',
                    'usuario' => '<strong>' . e($usuario) . '</strong>',
                    'accion' => $this->renderBadgeAccion($accion),
                    'detalle' => e($detalle),
                ];
            }
        }

        return [
            'id' => 'movimientos_recientes',
            'emoji' => '📝',
            'titulo' => 'Movimientos SS',
            'fecha' => $fecha,
            'headers' => ['Fecha', 'Usuario', 'Acción', 'Detalle'],
            'rows' => $rows,
        ];
    }

    protected function renderBadgeConteo(int $conteo, string $tipo): string
    {
        if ($conteo === 0) {
            return '<span style="color:var(--sig-muted)">0</span>';
        }

        return '<span class="sigem-pill ' . $tipo . '">' . $conteo . '</span>';
    }

    protected function renderBadgeAccion(string $evento): string
    {
        $tipo = match (strtolower($evento)) {
            'created', 'creado', 'alta' => 'ok',
            'updated', 'actualizado', 'modificado' => 'info',
            'deleted', 'eliminado', 'baja' => 'danger',
            default => 'neutral',
        };

        return '<span class="sigem-pill ' . $tipo . '">' . e(ucfirst($evento)) . '</span>';
    }
}
