<?php

namespace App\Filament\Pages;

use App\Models\Area;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Support\SigemExcelExporter;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $rows = [];

        if ($formato === 'csv') {
            foreach ($tipos as $tipo) {
                $inventarios = Inventario::whereHas('material', function ($q) use ($tipo) {
                    $q->where('id_tipodematerial', $tipo->id_tipodematerial);
                })->get();

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
            $inventarios = Inventario::whereHas('material', function ($q) use ($tipo) {
                $q->where('id_tipodematerial', $tipo->id_tipodematerial);
            })->get();

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
        $inventarios = Inventario::select(['id_inventario', 'ubicacion_fisica', 'especificaciones', 'tipo_propiedad', 'estado', 'estado_registro'])->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($areas as $area) {
                $areaNombre = $area->nombre;
                $areaIdStr = (string) $area->id_area;

                $areaInventarios = $inventarios->filter(function ($inv) use ($areaNombre, $areaIdStr) {
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
        $mantenimientos = Mantenimiento::with(['inventario.material.tipo'])->latest('fecha_solicitud')->get();
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
            $aula = $espec['aula'] ?? '—';
            $codigo = $inv?->num_serie ?: ('#' . $m->id_inventario);
            $equipo = $inv?->material?->nombre ?? 'N/A';
            $categoria = $inv?->material?->tipo?->nombre ?? 'General';
            $area = $inv?->ubicacion_fisica ?: 'Centro de Cómputo';

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
        $inventarios = Inventario::with('material.tipo')
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
            $codigo = $i->num_serie ?: ('#' . $i->id_inventario);
            $equipo = $i->material?->nombre ?? 'N/A';
            $categoria = $i->material?->tipo?->nombre ?? 'General';
            $area = $i->ubicacion_fisica ?: 'Centro de Cómputo';

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

    protected function renderBadge(string $estado): string
    {
        $tipo = match ($estado) {
            'Disponible', 'Autorizado', 'Terminado', 'Completado', 'Aprobado' => 'ok',
            'En proceso', 'Solicitado', 'Pendiente', 'Asignado' => 'warn',
            'En revisión', 'Pendiente Revision Admin', 'Entregado' => 'info',
            'Dañado', 'Baja', 'Rechazado', 'Cancelado' => 'danger',
            default => 'neutral',
        };

        $label = match ($estado) {
            'Pendiente Revision Admin' => 'En revisión',
            'Completado' => 'Terminado',
            'Cancelado' => 'Rechazado',
            default => $estado,
        };

        return '<span class="sigem-pill ' . $tipo . '">' . e($label) . '</span>';
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
