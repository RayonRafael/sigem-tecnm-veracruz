<?php

namespace App\Filament\Pages;

use App\Models\Area;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
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
        // TODO [Fase 10 - Documentado]: No existe tabla de settings en la base de datos actual.
        // La visibilidad persiste en Cache::forever con fallback a los 3 reportes base del cliente.
        // Si se requiere persistencia permanente tras 'cache:clear', crear tabla 'settings' en fase futura.
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

    public function exportarReporte(string $reportId): ?StreamedResponse
    {
        // TODO [Fase 10 - Documentado]: Exportación actual genera CSV estándar nativo sin dependencias externas. Integrar PDF o Excel nativo con paquete dedicado en fase futura.
        $reporte = $this->getDatosReporte($reportId);
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
            // BOM UTF-8 para compatibilidad universal con Excel
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
                'titulo' => 'Equipos con solicitud de baja / Equipos dados de baja',
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
                'titulo' => 'Bitácora de movimientos',
                'descripcion' => 'Registro cronológico de actividades y modificaciones auditables realizadas en el sistema.',
            ],
        ];
    }

    public function getDatosReporte(string $reportId): ?array
    {
        $fecha = Carbon::now()->format('d/m/Y H:i');

        return match ($reportId) {
            'inventario_categoria' => $this->generarReporteInventarioCategoria($fecha),
            'equipos_area' => $this->generarReporteEquiposArea($fecha),
            'equipos_mantenimiento' => $this->generarReporteEquiposMantenimiento($fecha),
            'equipos_baja_danados' => $this->generarReporteEquiposBajaDanados($fecha),
            'solicitudes_tipo_estado' => $this->generarReporteSolicitudes($fecha),
            'movimientos_recientes' => $this->generarReporteMovimientos($fecha),
            default => null,
        };
    }

    protected function generarReporteInventarioCategoria(string $fecha): array
    {
        $tipos = TipoMaterial::orderBy('nombre')->get();
        $rows = [];

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
                'categoria' => '<strong>' . e($tipo->nombre) . '</strong>',
                'total' => '<span class="sigem-td-folio">' . $total . '</span>',
                'propio' => $this->renderBadgeConteo($propios, 'ok'),
                'renta' => $this->renderBadgeConteo($rentados, 'info'),
                'disponibles' => $this->renderBadgeConteo($inventarios->where('estado', 'Disponible')->count(), 'ok'),
                'asignados' => $this->renderBadgeConteo($inventarios->where('estado', 'Asignado')->count(), 'warn'),
                'mantenimiento' => $this->renderBadgeConteo($inventarios->where('estado', 'En Mantenimiento')->count(), 'info'),
                'danados' => $this->renderBadgeConteo($inventarios->whereIn('estado', ['Dañado', 'Baja'])->count(), 'danger'),
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

    protected function generarReporteEquiposArea(string $fecha): array
    {
        $areas = Area::with('departamento')->orderBy('nombre')->get();
        $rows = [];

        foreach ($areas as $area) {
            $inventarios = Inventario::where(function ($q) use ($area) {
                $q->where('ubicacion_fisica', 'like', '%' . $area->nombre . '%')
                    ->orWhere('especificaciones->area_id', (string) $area->id_area);
            })->get();

            $total = $inventarios->count();
            if ($total === 0) {
                continue;
            }

            $propios = $inventarios->where('tipo_propiedad', 'Propio')->count();
            $rentados = $inventarios->where('tipo_propiedad', 'Rentado')->count();

            $rows[] = [
                'depto' => e($area->departamento?->nombre ?? 'N/A'),
                'area' => '<strong>' . e($area->nombre) . '</strong>',
                'total' => '<span class="sigem-td-folio">' . $total . '</span>',
                'propio' => $this->renderBadgeConteo($propios, 'ok'),
                'renta' => $this->renderBadgeConteo($rentados, 'info'),
                'disponibles' => $this->renderBadgeConteo($inventarios->where('estado', 'Disponible')->count(), 'ok'),
                'asignados' => $this->renderBadgeConteo($inventarios->where('estado', 'Asignado')->count(), 'warn'),
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

    protected function generarReporteEquiposMantenimiento(string $fecha): array
    {
        $mantenimientos = Mantenimiento::with(['inventario.material'])->latest('fecha_solicitud')->get();
        $rows = [];

        foreach ($mantenimientos as $m) {
            $esPropio = ($m->inventario?->tipo_propiedad ?? 'Propio') === 'Propio';
            $badgePropiedad = $esPropio
                ? '<span class="sigem-pill ok">● Propio</span>'
                : '<span class="sigem-pill info">● Rentado</span>';

            $rows[] = [
                'serie' => '<span class="sigem-td-folio">' . e($m->inventario?->num_serie ?? 'S/N') . '</span>',
                'equipo' => e($m->inventario?->material?->nombre ?? 'N/A'),
                'propiedad' => $badgePropiedad,
                'servicio' => e($m->tipo_servicio),
                'mantenimiento' => e($m->tipo_mantenimiento),
                'tecnico' => e($m->nombre_tecnico),
                'fecha' => $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : 'N/A',
                'estado' => $this->renderBadge($m->estado),
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

    protected function generarReporteEquiposBajaDanados(string $fecha): array
    {
        $inventarios = Inventario::with('material')
            ->where(function ($q) {
                $q->whereIn('estado', ['Dañado', 'Baja'])
                    ->orWhere('estado_registro', 'Rechazado');
            })
            ->latest('updated_at')
            ->get();

        $rows = [];
        foreach ($inventarios as $i) {
            $fFecha = $i->fecha_baja ? $i->fecha_baja->format('d/m/Y') : ($i->fecha_registro ? $i->fecha_registro->format('d/m/Y') : 'N/A');

            $esPropio = ($i->tipo_propiedad ?? 'Propio') === 'Propio';
            $badgePropiedad = $esPropio
                ? '<span class="sigem-pill ok">● Propio</span>'
                : '<span class="sigem-pill info">● Rentado</span>';

            $rows[] = [
                'serie' => '<span class="sigem-td-folio">' . e($i->num_serie ?? 'S/N') . '</span>',
                'material' => e($i->material?->nombre ?? 'N/A'),
                'propiedad' => $badgePropiedad,
                'ubicacion' => e($i->ubicacion_fisica ?? 'No especificada'),
                'estado' => $this->renderBadge($i->estado),
                'registro' => $this->renderBadge($i->estado_registro ?? 'Pendiente'),
                'fecha' => $fFecha,
            ];
        }

        return [
            'id' => 'equipos_baja_danados',
            'emoji' => '⚠️',
            'titulo' => 'Equipos con solicitud de baja / Equipos dados de baja',
            'fecha' => $fecha,
            'headers' => ['No. Serie', 'Material', 'Propiedad', 'Ubicación', 'Estado', 'Registro', 'Fecha'],
            'rows' => $rows,
        ];
    }

    protected function generarReporteSolicitudes(string $fecha): array
    {
        $solicitudes = Solicitud::with(['usuario', 'receptor.area.departamento'])->latest('fecha_solicitud')->get();
        $rows = [];

        foreach ($solicitudes as $s) {
            $rows[] = [
                'folio' => '<span class="sigem-td-folio">FOLIO-' . str_pad($s->id_solicitud, 5, '0', STR_PAD_LEFT) . '</span>',
                'tipo' => '<span class="sigem-pill info">● ' . e($s->tipo_movimiento) . '</span>',
                'solicitante' => e($s->usuario?->name ?? 'N/A'),
                'receptor' => e($s->receptor?->nombre ?? 'N/A'),
                'departamento' => e($s->receptor?->area?->departamento?->nombre ?? 'N/A'),
                'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : 'N/A',
                'estado' => $this->renderBadge($s->estado),
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

    protected function generarReporteMovimientos(string $fecha): array
    {
        $activities = Activity::with('causer')->latest()->limit(50)->get();
        $rows = [];

        foreach ($activities as $a) {
            $rows[] = [
                'fecha' => $a->created_at ? $a->created_at->format('d/m/Y H:i') : 'N/A',
                'usuario' => '<strong>' . e($a->causer?->name ?? 'Sistema') . '</strong>',
                'accion' => $this->renderBadgeAccion($a->event ?? $a->description ?? 'evento'),
                'modulo' => '<span class="sigem-td-folio">' . e(class_basename($a->subject_type ?? 'Sistema')) . '</span>',
                'descripcion' => e($a->description ?? 'Sin detalles'),
            ];
        }

        return [
            'id' => 'movimientos_recientes',
            'emoji' => '📝',
            'titulo' => 'Bitácora de movimientos',
            'fecha' => $fecha,
            'headers' => ['Fecha y Hora', 'Usuario', 'Acción', 'Módulo', 'Descripción'],
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
