<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemExcelExporter;
use App\Support\SigemLegacyReportBuilder;
use App\Support\SigemPdfGenerator;
use App\Support\SigemReportExporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
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

    public string $pestanaPrincipal = 'principales';

    public string $subpestanaReportes = 'inventario';

    public ?string $filtroFechaDesde = null;

    public ?string $filtroFechaHasta = null;

    public ?string $filtroDepartamentoId = '';

    public ?string $nuevoReporteActivo = null;

    public ?string $formatoSeleccionadoCambioArea = '';

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

    public string $pestanaFormatoActiva = 'entrega';

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

        // Deep-link opcional desde Dashboard (ej. ?pestana=nuevos&subpestana=rentas&reporte=rent_seguimiento)
        $pestana = request()->query('pestana');
        if (is_string($pestana) && in_array($pestana, ['principales', 'formatos', 'nuevos'], true)) {
            $this->pestanaPrincipal = $pestana;
        }

        $subpestana = request()->query('subpestana');
        if (is_string($subpestana) && array_key_exists($subpestana, SigemReportExporter::getSubpestanas())) {
            $this->subpestanaReportes = $subpestana;
        }

        $reporte = request()->query('reporte');
        if (is_string($reporte) && SigemReportExporter::findReporte($reporte) !== null) {
            $this->nuevoReporteActivo = $reporte;
        }
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
     * Metadata de los Formatos Institucionales en PDF del TecNM Veracruz.
     * Retorna 4 formatos por defecto (retrocompatibilidad) o 5 si se incluye Cambio de Área.
     *
     * @return list<array{id: string, emoji: string, titulo: string, subtitulo: string, descripcion: string}>
     */
    public function getFormatosInstitucionales(bool $incluirCambioArea = false): array
    {
        $formatos = [
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

        if ($incluirCambioArea) {
            $formatos[] = [
                'id' => 'cambio_area',
                'emoji' => '🔄',
                'titulo' => 'Formato de Cambio de Área',
                'subtitulo' => 'Formato 5',
                'descripcion' => 'Constancia oficial de reubicación y traslado de bienes entre departamentos y áreas.',
            ];
        }

        return $formatos;
    }

    public function cambiarPestanaPrincipal(string $pestana): void
    {
        $map = [
            'nuevos_reportes' => 'nuevos',
            'reportes' => 'principales',
        ];
        $pestana = $map[$pestana] ?? $pestana;

        if (in_array($pestana, ['principales', 'formatos', 'nuevos'], true)) {
            $this->pestanaPrincipal = $pestana;
            $this->cerrarReporte();
            $this->cerrarNuevoReporte();
        }
    }

    public function cambiarSubpestanaReportes(string $subpestana): void
    {
        $validas = array_keys(SigemReportExporter::getSubpestanas());
        if (in_array($subpestana, $validas, true)) {
            $this->subpestanaReportes = $subpestana;
            $this->cerrarNuevoReporte();
        }
    }

    public function cambiarPestanaFormato(string $pestana): void
    {
        $validas = ['entrega', 'orden_trabajo', 'lista_verificacion', 'baja', 'cambio_area', 'filtrar'];
        if (in_array($pestana, $validas, true)) {
            $this->pestanaFormatoActiva = $pestana;
        }
    }

    public function limpiarFiltrosNuevosReportes(): void
    {
        $this->filtroFechaDesde = null;
        $this->filtroFechaHasta = null;
        $this->filtroDepartamentoId = '';
        $this->cerrarNuevoReporte();
    }

    public function verNuevoReporte(string $reportId): void
    {
        $this->nuevoReporteActivo = $reportId;
        $this->dispatch('scroll-to-report');
    }

    public function cerrarNuevoReporte(): void
    {
        $this->nuevoReporteActivo = null;
    }

    public function exportarNuevoReporte(string $reportId, string $formato = 'xlsx')
    {
        $filtros = [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'departamento_id' => $this->filtroDepartamentoId,
        ];

        return SigemReportExporter::export($reportId, $filtros, $formato);
    }

    public function getDatosNuevoReporte(string $reportId): ?array
    {
        $filtros = [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'departamento_id' => $this->filtroDepartamentoId,
        ];

        return SigemReportExporter::getReportData($reportId, $filtros);
    }

    public function getReportesSubpestanaActual(): array
    {
        $filtros = [
            'fecha_desde' => $this->filtroFechaDesde,
            'fecha_hasta' => $this->filtroFechaHasta,
            'departamento_id' => $this->filtroDepartamentoId,
        ];

        return SigemReportExporter::getReportesSubpestana($this->subpestanaReportes, $filtros);
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
        $this->formatoSeleccionadoCambioArea = '';
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

    #[Computed]
    public function departamentosParaFiltros(): array
    {
        return $this->getDepartamentosParaFiltros();
    }

    public function getDepartamentosParaFiltros(): array
    {
        $ver = SigemReportExporter::cacheVersion();

        return Cache::remember("sigem_rep_deptos_v{$ver}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            return Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray();
        });
    }

    protected function baseQuerySolicitudesParaFormato()
    {
        $query = Solicitud::query()
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

        return $query;
    }

    public function getSolicitudesParaFormato(): \Illuminate\Support\Collection
    {
        return $this->baseQuerySolicitudesParaFormato()
            ->with(['receptor.area.departamento', 'detalles.material'])
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id_solicitud')
            ->limit(100)
            ->get();
    }

    protected function baseQueryMantenimientosParaFormato(bool $withRelations = true)
    {
        $query = Mantenimiento::query()
            ->where('estado', '!=', 'Cancelado');

        if ($withRelations) {
            $query->with(['inventario.material', 'inventario.departamento', 'inventario.area.departamento', 'usuarioSolicita']);
        }

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

    protected function baseQueryInventariosBajaParaFormato()
    {
        $query = Inventario::query()
            ->whereIn('estado', SigemBajaFormatHelper::ESTADOS_BAJA);

        $this->aplicarFiltroFechaFormato($query, 'fecha_baja');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        $this->aplicarFiltroDeptoInventario($query, $deptoId);

        return $query;
    }

    public function getInventariosBajaParaFormato(): \Illuminate\Support\Collection
    {
        return $this->baseQueryInventariosBajaParaFormato()
            ->with(['material.marca', 'departamento', 'area.departamento'])
            ->orderByDesc('fecha_baja')
            ->orderByDesc('id_inventario')
            ->limit(SigemBajaFormatHelper::MAX_LOTE_BAJAS)
            ->get();
    }

    protected function baseQueryInventariosCambioAreaParaFormato()
    {
        $query = Inventario::query()
            ->where(function ($q) {
                $q->whereNotNull('fecha_cambio_area')
                    ->orWhere('estado', 'Cambio de área')
                    ->orWhereNotNull('especificaciones->cambio_origen_depto');
            });

        $this->aplicarFiltroFechaFormato($query, 'fecha_cambio_area');

        $deptoId = filled($this->formatoDepartamentoId) ? (int) $this->formatoDepartamentoId : null;
        $this->aplicarFiltroDeptoInventario($query, $deptoId);

        return $query;
    }

    public function getInventariosCambioAreaParaFormato(): \Illuminate\Support\Collection
    {
        return $this->baseQueryInventariosCambioAreaParaFormato()
            ->with(['material.marca', 'departamento', 'area.departamento', 'salon'])
            ->orderByDesc('fecha_cambio_area')
            ->orderByDesc('id_inventario')
            ->limit(100)
            ->get();
    }

    /**
     * Contadores ligeros para las pestañas de Formatos PDF (4 COUNT(*) cacheados en lugar de hidratar 5 colecciones completas).
     *
     * @return array{entrega: int, orden_trabajo: int, lista_verificacion: int, baja: int, cambio_area: int}
     */
    #[Computed]
    public function conteosFormatos(): array
    {
        $ver = SigemReportExporter::cacheVersion();
        $scope = $this->esUsuarioAdminReportes() ? 'admin' : ('u' . (auth()->id() ?? 0));
        $hash = md5(json_encode([
            $scope,
            (string) $this->formatoFechaDesde,
            (string) $this->formatoFechaHasta,
            (string) $this->formatoDepartamentoId,
        ]));

        return Cache::remember("sigem_fmt_cnt_v{$ver}_{$hash}", SigemReportExporter::CACHE_TTL_SECONDS, function () {
            $cntEntrega = min(100, (int) $this->baseQuerySolicitudesParaFormato()->count());
            $cntMant = min(100, (int) $this->baseQueryMantenimientosParaFormato(false)->count());
            $cntBaja = min(SigemBajaFormatHelper::MAX_LOTE_BAJAS, (int) $this->baseQueryInventariosBajaParaFormato()->count());
            $cntCambio = min(100, (int) $this->baseQueryInventariosCambioAreaParaFormato()->count());

            return [
                'entrega' => $cntEntrega,
                'orden_trabajo' => $cntMant,
                'lista_verificacion' => $cntMant,
                'baja' => $cntBaja,
                'cambio_area' => $cntCambio,
            ];
        });
    }

    /**
     * Filas normalizadas para la sub-pestaña "Filtrar formatos existentes" (carga solo el tipo seleccionado).
     */
    public function getFilasFiltradasFormatos(): \Illuminate\Support\Collection
    {
        return match ($this->formatoFiltroTipo) {
            'entrega' => $this->getSolicitudesParaFormato()->map(fn ($s) => [
                'tipo_nombre' => 'Entrega',
                'tipo_clave' => 'entrega',
                'id' => $s->id_solicitud,
                'codigo' => 'FOLIO-' . str_pad((string) $s->id_solicitud, 5, '0', STR_PAD_LEFT),
                'depto' => $s->receptor?->area?->departamento?->nombre ?? ($s->receptor?->area?->nombre ?? '—'),
                'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '—',
            ]),
            'orden_trabajo' => $this->getMantenimientosParaOrdenFormato()->map(fn ($m) => [
                'tipo_nombre' => 'Orden de Trabajo',
                'tipo_clave' => 'orden_trabajo',
                'id' => $m->id_mantenimiento,
                'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                'fecha' => $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—',
            ]),
            'lista_verificacion' => $this->getMantenimientosParaVerificacionFormato()->map(fn ($m) => [
                'tipo_nombre' => 'Lista Verificación',
                'tipo_clave' => 'lista_verificacion',
                'id' => $m->id_mantenimiento,
                'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                'fecha' => $m->fecha_fin ? $m->fecha_fin->format('d/m/Y') : ($m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—'),
            ]),
            'cambio_area' => $this->getInventariosCambioAreaParaFormato()->map(fn ($c) => [
                'tipo_nombre' => 'Cambio de Área',
                'tipo_clave' => 'cambio_area',
                'id' => $c->id_inventario,
                'codigo' => $c->codigo ?: ($c->num_serie ?: ('#' . $c->id_inventario)),
                'depto' => $c->departamento?->nombre ?? ($c->area?->departamento?->nombre ?? '—'),
                'fecha' => $c->fecha_cambio_area ? Carbon::parse($c->fecha_cambio_area)->format('d/m/Y') : '—',
            ]),
            default => $this->getInventariosBajaParaFormato()->map(fn ($b) => [
                'tipo_nombre' => 'Baja',
                'tipo_clave' => 'baja',
                'id' => $b->id_inventario,
                'codigo' => $b->codigo ?: ($b->num_serie ?: ('#' . $b->id_inventario)),
                'depto' => $b->departamento?->nombre ?? ($b->area?->departamento?->nombre ?? '—'),
                'fecha' => $b->fecha_baja ? Carbon::parse($b->fecha_baja)->format('d/m/Y') : '—',
            ]),
        };
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

    public function descargarFormatoInstitucional(string $formatoId, $registroId = null)
    {
        $idStr = filled($registroId) ? (string) $registroId : null;

        if ($formatoId === 'entrega') {
            $solicitud = $this->resolverRegistroFormatoAutorizado(
                $this->getSolicitudesParaFormato(),
                $idStr ?? $this->formatoSeleccionadoEntrega,
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
                $idStr ?? $this->formatoSeleccionadoOrden,
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
                $idStr ?? $this->formatoSeleccionadoVerificacion,
                'id_mantenimiento',
                Mantenimiento::class,
                'id_usuario_solicita',
                'No hay mantenimientos disponibles para Lista de Verificación.'
            );

            return $mant ? SigemPdfGenerator::descargarListaVerificacion($mant) : null;
        }

        if ($formatoId === 'baja') {
            $candidatos = $this->getInventariosBajaParaFormato();
            if ($idStr !== null) {
                $inv = $candidatos->firstWhere('id_inventario', (int) $idStr) ?? Inventario::find((int) $idStr);
                if ($inv) {
                    return SigemPdfGenerator::descargarFormatoBaja($inv);
                }
            }

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

        if ($formatoId === 'cambio_area') {
            $candidatos = $this->getInventariosCambioAreaParaFormato();
            $inv = $this->resolverRegistroFormatoAutorizado(
                $candidatos,
                $idStr ?? $this->formatoSeleccionadoCambioArea,
                'id_inventario',
                Inventario::class,
                'id_usuario',
                'No hay equipos con cambio de área que coincidan con los filtros.'
            );

            return $inv ? SigemPdfGenerator::descargarCambioArea($inv) : null;
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
        return SigemLegacyReportBuilder::generar($reportId, $formato);
    }
}
