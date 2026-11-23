<?php

namespace App\Filament\Pages;

use App\Enums\RoleEnum;
use App\Filament\Concerns\ConReportesFiltros;
use App\Filament\Concerns\ConReportesFormatos;
use App\Support\SigemExcelExporter;
use App\Support\SigemLegacyReportBuilder;
use App\Support\SigemReportExporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportesPage extends Page
{
    use ConReportesFiltros;
    use ConReportesFormatos;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'reportes';

    protected static ?string $title = '📊 Reportes';

    protected static string $view = 'filament.pages.reportes';

    public const REPORTES_PRINCIPALES_POR_PAGINA = 10;

    public const NUEVOS_REPORTES_POR_PAGINA = 10;

    public string $pestanaPrincipal = 'principales';

    public string $subpestanaReportes = 'graficas';

    /** Opcional: reporte inicial a abrir por deep-link (ej. ?reporte=rent_seguimiento). */
    public ?string $nuevoReporteActivo = null;

    public array $visibilidadServicioSocial = [];

    /** @var array<string, int> */
    public array $paginasReportesPrincipales = [];

    /** @var array<string, int> */
    public array $paginasNuevosReportes = [];

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

    public function mount(): void
    {
        $defaults = [
            'inventario_categoria' => true,
            'equipos_area' => true,
            'equipos_mantenimiento' => true,
            'equipos_baja_danados' => true,
            'solicitudes_tipo_estado' => true,
            'movimientos_recientes' => true,
        ];

        foreach (SigemReportExporter::getCatalogo() as $subReportes) {
            foreach (array_keys($subReportes) as $repId) {
                $defaults[$repId] = true;
            }
        }

        $cached = Cache::get('sigem_reportes_visibilidad_ss', []);
        $this->visibilidadServicioSocial = array_merge($defaults, is_array($cached) ? $cached : []);

        // Deep-link opcional desde Dashboard (ej. ?pestana=nuevos&subpestana=rentas&reporte=rent_seguimiento)
        $pestana = request()->query('pestana');
        if (is_string($pestana) && in_array($pestana, ['principales', 'formatos', 'nuevos'], true)) {
            $this->pestanaPrincipal = $pestana;
        }

        $subpestana = request()->query('subpestana');
        if (is_string($subpestana) && array_key_exists($subpestana, $this->getSubpestanasUi())) {
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

    public function cambiarPaginaReportePrincipal(string $reportId, int $pagina): void
    {
        $this->paginasReportesPrincipales[$reportId] = max(1, $pagina);
    }

    public function getPaginaReportePrincipal(string $reportId): int
    {
        return max(1, (int) ($this->paginasReportesPrincipales[$reportId] ?? 1));
    }

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
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $reporte['headers']);
            foreach ($reporte['rows'] as $row) {
                $cleanRow = array_map(fn ($val) => trim(strip_tags((string) $val)), $row);
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

    public function cambiarPestanaPrincipal(string $pestana): void
    {
        $map = [
            'nuevos_reportes' => 'nuevos',
            'reportes' => 'principales',
        ];
        $pestana = $map[$pestana] ?? $pestana;

        if (in_array($pestana, ['principales', 'formatos', 'nuevos'], true)) {
            $this->pestanaPrincipal = $pestana;
        }
    }

    public function cambiarSubpestanaReportes(string $subpestana): void
    {
        $validas = array_keys($this->getSubpestanasUi());
        if (in_array($subpestana, $validas, true)) {
            $this->subpestanaReportes = $subpestana;
            $this->nuevoReporteActivo = null;
            $this->reiniciarPaginacionNuevosReportes();
        }
    }

    /**
     * Retorna las 6 sub-pestañas de UI para Nuevos Reportes:
     * Gráficas (12) + 5 sub-pestañas de solo tablas (3, 3, 4, 4, 3 = 17). Total: 29.
     *
     * @return array<string, array{label: string, count: int}>
     */
    public function getSubpestanasUi(): array
    {
        $catalogo = SigemReportExporter::getCatalogo();
        $labelsBase = SigemReportExporter::getSubpestanas();

        $totalGraficas = 0;
        $conteosTablas = [];

        foreach ($catalogo as $claveSub => $reportes) {
            $soloTablas = 0;
            foreach ($reportes as $meta) {
                if (! empty($meta['has_chart'])) {
                    $totalGraficas++;
                } else {
                    $soloTablas++;
                }
            }
            $conteosTablas[$claveSub] = $soloTablas;
        }

        $tabs = [
            'graficas' => [
                'label' => '📊 Gráficas',
                'count' => $totalGraficas,
            ],
        ];

        foreach ($labelsBase as $claveSub => $nombreSub) {
            $tabs[$claveSub] = [
                'label' => $nombreSub,
                'count' => $conteosTablas[$claveSub] ?? 0,
            ];
        }

        return $tabs;
    }

    public function cambiarPaginaNuevoReporte(string $reportId, int $pagina): void
    {
        $this->paginasNuevosReportes[$reportId] = max(1, $pagina);
    }

    public function getPaginaNuevoReporte(string $reportId): int
    {
        return max(1, (int) ($this->paginasNuevosReportes[$reportId] ?? 1));
    }

    public function reiniciarPaginacionNuevosReportes(): void
    {
        $this->paginasNuevosReportes = [];
    }

    public function exportarNuevoReporte(string $reportId, string $formato = 'xlsx')
    {
        return SigemReportExporter::export($reportId, $this->obtenerFiltrosNuevosReportes(), $formato);
    }

    public function getDatosNuevoReporte(string $reportId): ?array
    {
        return SigemReportExporter::getReportData($reportId, $this->obtenerFiltrosNuevosReportes());
    }

    public function getReportesSubpestanaActual(): array
    {
        $filtros = $this->obtenerFiltrosNuevosReportes();
        $catalogo = SigemReportExporter::getCatalogo();
        $resultado = [];

        if ($this->subpestanaReportes === 'graficas') {
            foreach ($catalogo as $reportes) {
                foreach ($reportes as $key => $meta) {
                    if (! empty($meta['has_chart'])) {
                        $resultado[] = SigemReportExporter::getReportData($key, $filtros);
                    }
                }
            }

            return $resultado;
        }

        foreach ($catalogo[$this->subpestanaReportes] ?? [] as $key => $meta) {
            if (empty($meta['has_chart'])) {
                $resultado[] = SigemReportExporter::getReportData($key, $filtros);
            }
        }

        return $resultado;
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

    public function getDatosReporte(string $reportId, string $formato = 'pantalla'): ?array
    {
        return SigemLegacyReportBuilder::generar($reportId, $formato);
    }
}
