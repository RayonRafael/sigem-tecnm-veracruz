<?php

namespace App\Support;

use App\Models\Departamento;
use App\Models\Inventario;
use App\Support\Reports\EstadisticasReportsBuilder;
use App\Support\Reports\HistoricoReportsBuilder;
use App\Support\Reports\InventarioReportsBuilder;
use App\Support\Reports\MantenimientoReportsBuilder;
use App\Support\Reports\RentasReportsBuilder;
use App\Support\Reports\SigemReportChartBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class SigemReportExporter
{
    public const CACHE_VERSION_KEY = 'sigem_reportes_cache_ver';
    public const CACHE_TTL_SECONDS = 300; // 5 minutos

    /**
     * Versión actual del caché de reportes para invalidación O(1).
     */
    public static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 1);
    }

    /**
     * Invalida inmediatamente el caché de reportes incrementando la versión.
     */
    public static function clearCache(): void
    {
        $next = self::cacheVersion() + 1;
        Cache::forever(self::CACHE_VERSION_KEY, $next);
    }

    /**
     * Mapeo de alias cortos hacia IDs canónicos.
     */
    public static array $aliasMap = [
        'inv_general' => 'inventario_general',
        'inv_depto' => 'inventario_por_departamento',
        'inv_area' => 'inventario_por_area',
        'inv_tipo' => 'inventario_por_tipo',
        'inv_estado' => 'inventario_por_estado',
        'inv_aula' => 'inventario_por_aula',

        'est_antiguos' => 'estadisticas_antiguos',
        'est_sin_asignar' => 'estadisticas_sin_asignar',
        'est_top_deptos' => 'estadisticas_depto_mas_equipos',
        'est_movidos' => 'estadisticas_mas_movidos',
        'est_propiedad' => 'estadisticas_propio_vs_rentado',
        'est_resumen' => 'estadisticas_resumen_general',

        'mant_top_deptos' => 'mantenimiento_depto_pide_mas',
        'mant_top_tipos' => 'mantenimiento_tipo_mas_frecuente',
        'mant_por_mes' => 'mantenimiento_por_mes',
        'mant_activos' => 'mantenimiento_en_curso',
        'mant_tiempos' => 'mantenimiento_tiempo_promedio',
        'mant_historial' => 'mantenimiento_historial',

        'rent_activas' => 'rentas_equipos',
        'rent_por_vencer' => 'rentas_proximas_vencer',
        'rent_vencidas' => 'rentas_vencidas',
        'rent_gasto_mensual' => 'rentas_gasto_mensual',
        'rent_proveedores' => 'rentas_por_proveedor',
        'rent_seguimiento' => 'rent_seguimiento',

        'hist_cambios_area' => 'historico_cambios_area',
        'hist_bajas' => 'historico_bajas',
        'hist_entregas' => 'historico_entregas',
        'hist_actividad_usuario' => 'historico_actividad_usuario',
        'hist_general' => 'historico_general',
    ];

    /**
     * Retorna el catálogo completo de reportes organizados en 5 sub-pestañas.
     */
    public static function getCatalogo(): array
    {
        return SigemReportCatalog::getCatalogo();
    }

    /**
     * Retorna las 5 sub-pestañas disponibles para la sección de Nuevos Reportes.
     */
    public static function getSubpestanas(): array
    {
        return SigemReportCatalog::getSubpestanas();
    }

    /**
     * Busca los metadatos de un reporte específico por su ID o alias.
     */
    public static function findReporte(string $tipo): ?array
    {
        $canonical = self::$aliasMap[$tipo] ?? $tipo;

        foreach (self::getCatalogo() as $reportes) {
            if (isset($reportes[$canonical])) {
                return $reportes[$canonical];
            }
            if (isset($reportes[$tipo])) {
                return $reportes[$tipo];
            }
        }

        return null;
    }

    /**
     * Retorna los reportes listos para visualizar de una sub-pestaña dada.
     */
    public static function getReportesSubpestana(string $subpestana, array $filtros = []): array
    {
        $catalogo = self::getCatalogo();
        $lista = $catalogo[$subpestana] ?? [];
        $resultado = [];

        foreach ($lista as $key => $meta) {
            $resultado[] = self::getReportData($key, $filtros);
        }

        return $resultado;
    }

    /**
     * Ejecuta el query correspondiente al tipo de reporte (con caché de 5 min) y retorna su estructura.
     */
    public static function getReportData(string $tipo, array $filtros = []): array
    {
        $canonical = self::$aliasMap[$tipo] ?? $tipo;
        $filtrosNorm = [
            'fecha_desde' => ! empty($filtros['fecha_desde']) ? (string) $filtros['fecha_desde'] : '',
            'fecha_hasta' => ! empty($filtros['fecha_hasta']) ? (string) $filtros['fecha_hasta'] : '',
            'departamento_id' => ! empty($filtros['departamento_id']) ? (string) $filtros['departamento_id'] : '',
            'area_id' => ! empty($filtros['area_id']) ? (string) $filtros['area_id'] : '',
            'aula' => ! empty($filtros['aula']) ? (string) $filtros['aula'] : '',
        ];

        $ver = self::cacheVersion();
        $cacheKey = 'sigem_rep_v' . $ver . '_' . $canonical . '_' . md5(json_encode($filtrosNorm));

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($canonical, $filtrosNorm) {
            return self::computeReportData($canonical, $filtrosNorm);
        });
    }

    /**
     * Catálogo de departamentos ordenado por nombre (cacheado con versión de reportes).
     *
     * @return array<int, string>
     */
    public static function getDepartamentosMap(): array
    {
        $ver = self::cacheVersion();

        return Cache::remember("sigem_rep_deptos_v{$ver}", self::CACHE_TTL_SECONDS, function () {
            return Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray();
        });
    }

    /**
     * Construye el dataset de un reporte delegando al builder de su sub-pestaña.
     */
    protected static function computeReportData(string $canonical, array $filtros = []): array
    {
        $meta = self::findReporte($canonical) ?? [
            'id' => $canonical,
            'titulo' => 'Reporte',
            'columnas' => [],
            'has_chart' => false,
        ];

        $headers = $meta['columnas'] ?? [];
        $rows = match (true) {
            str_starts_with($canonical, 'inventario_') => (new InventarioReportsBuilder())->getDatos($canonical, $filtros),
            str_starts_with($canonical, 'estadisticas_') => (new EstadisticasReportsBuilder())->getDatos($canonical, $filtros),
            str_starts_with($canonical, 'mantenimiento_') || str_starts_with($canonical, 'mant_') => (new MantenimientoReportsBuilder())->getDatos($canonical, $filtros),
            str_starts_with($canonical, 'rent') => (new RentasReportsBuilder())->getDatos($canonical, $filtros),
            str_starts_with($canonical, 'historico_') => (new HistoricoReportsBuilder())->getDatos($canonical, $filtros),
            default => [],
        };

        $hasChart = ! empty($meta['has_chart']);
        $chartConfig = $hasChart ? self::buildChartConfig($meta, $headers, $rows) : null;
        $reportId = $meta['id'] ?? $canonical;
        $totalesLabel = self::computeTotalesLabel($reportId, $rows);
        $resumenInline = self::computeResumenInline($reportId, $headers, $rows);

        $top5Headers = array_slice($headers, 0, min(3, count($headers)));
        $top5Rows = [];
        foreach (array_slice($rows, 0, 5) as $r) {
            $top5Rows[] = array_slice((array) $r, 0, min(3, count($headers)));
        }

        return [
            'id' => $reportId,
            'alias' => $meta['alias'] ?? $reportId,
            'subpestana' => $meta['categoria'] ?? 'inventario',
            'titulo' => $meta['titulo'] ?? 'Reporte',
            'titulo_ui' => $meta['titulo_ui'] ?? ($meta['titulo'] ?? 'Reporte'),
            'chart_title' => $meta['chart_title'] ?? ($meta['titulo_ui'] ?? ($meta['titulo'] ?? 'Reporte')),
            'chart_unit' => $meta['chart_unit'] ?? 'registros',
            'emoji' => $meta['icono'] ?? '📊',
            'descripcion_corta' => $meta['descripcion_corta'] ?? ($meta['descripcion'] ?? ''),
            'descripcion' => $meta['descripcion'] ?? '',
            'nota_grafica' => $meta['nota_grafica'] ?? null,
            'usa_fechas' => (bool) ($meta['usa_fechas'] ?? false),
            'resumen_inline' => $resumenInline,
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
            'total_registros' => count($rows),
            'totales_label' => $totalesLabel,
            'has_chart' => $hasChart,
            'chart' => $chartConfig,
            'top5_headers' => $top5Headers,
            'top5_rows' => $top5Rows,
        ];
    }

    /**
     * Construye la estructura de datos compatible con Chart.js para los reportes visuales.
     */
    public static function buildChartConfig(array $meta, array $headers, array $rows): ?array
    {
        return SigemReportChartBuilder::build($meta, $headers, $rows);
    }

    /**
     * Genera el resumen inline conciso para el encabezado del acordeón (ej. "· 14 deptos · 472 equipos").
     */
    public static function computeResumenInline(string $id, array $headers, array $rows): string
    {
        $count = count($rows);

        return match ($id) {
            'inventario_por_departamento', 'inv_depto' => '· ' . $count . ' deptos · ' . array_sum(array_map('intval', array_column($rows, 1))) . ' equipos',
            'inventario_por_tipo', 'inv_tipo' => "· {$count} tipos",
            'inventario_por_estado', 'inv_estado' => "· {$count} estados",
            'estadisticas_depto_mas_equipos', 'est_top_deptos' => "· {$count} deptos",
            'estadisticas_mas_movidos', 'est_movidos' => "· {$count} equipos",
            'estadisticas_propio_vs_rentado', 'est_propiedad' => '· ' . array_sum(array_map('intval', array_column($rows, 1))) . ' equipos',
            'mantenimiento_depto_pide_mas', 'mant_top_deptos' => "· {$count} deptos",
            'mantenimiento_por_mes', 'mant_por_mes' => "· {$count} meses",
            'rentas_proximas_vencer', 'rent_por_vencer' => "· {$count} equipos",
            'rentas_gasto_mensual', 'rent_gasto_mensual' => '· $' . number_format(array_sum(array_map(
                fn ($r) => (float) preg_replace('/[^0-9.]/', '', (string) ($r[2] ?? '0')),
                $rows
            )), 2) . ' MXN total',
            'historico_cambios_area', 'hist_cambios_area' => "· {$count} cambios",
            'historico_actividad_usuario', 'hist_actividad_usuario' => "· {$count} usuarios",
            'inventario_general', 'inv_general' => "· {$count} registros · " . count($headers) . ' columnas',
            'inventario_por_area', 'inv_area' => "· {$count} áreas",
            'inventario_por_aula', 'inv_aula' => "· {$count} aulas",
            'estadisticas_antiguos', 'est_antiguos' => '· Top 20',
            'estadisticas_sin_asignar', 'est_sin_asignar' => "· {$count} equipos",
            'estadisticas_resumen_general', 'est_resumen' => '· ' . count($headers) . ' métricas',
            'mantenimiento_tipo_mas_frecuente', 'mant_top_tipos' => "· {$count} tipos",
            'mantenimiento_en_curso', 'mant_activos' => "· {$count} equipos",
            'mantenimiento_tiempo_promedio', 'mant_tiempos' => (function () use ($rows): string {
                $vals = array_values(array_filter(array_map(
                    fn ($r) => (float) preg_replace('/[^0-9.]/', '', (string) ($r[1] ?? '0')),
                    $rows
                ), fn ($v) => $v >= 0));
                $prom = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : 0;

                return "· {$prom} días prom";
            })(),
            'mantenimiento_historial_equipo', 'mant_historial' => "· {$count} registros",
            'rentas_equipos', 'rent_activas' => "· {$count} equipos",
            'rentas_vencidas', 'rent_vencidas' => "· {$count} equipos",
            'rentas_por_proveedor', 'rent_proveedores' => "· {$count} proveedores",
            'rent_seguimiento' => "· {$count} registros",
            'historico_bajas', 'hist_bajas' => "· {$count} registros",
            'historico_entregas', 'hist_entregas' => "· {$count} registros",
            'historico_general', 'hist_general' => "· {$count} registros",
            default => "· {$count} registros",
        };
    }

    /**
     * Calcula la etiqueta resumen de totales para encabezado de tarjeta.
     */
    public static function computeTotalesLabel(string $id, array $rows): string
    {
        $count = count($rows);
        switch ($id) {
            case 'estadisticas_propio_vs_rentado':
            case 'est_propiedad':
                $propios = 0;
                $rentados = 0;
                foreach ($rows as $r) {
                    if (stripos((string) ($r[0] ?? ''), 'propio') !== false) {
                        $propios = (int) ($r[1] ?? 0);
                    }
                    if (stripos((string) ($r[0] ?? ''), 'rent') !== false) {
                        $rentados = (int) ($r[1] ?? 0);
                    }
                }
                $total = $propios + $rentados;

                return "Totales: {$total} equipos | Propios: {$propios} · Rentados: {$rentados}";

            case 'inventario_por_departamento':
            case 'inv_depto':
            case 'estadisticas_depto_mas_equipos':
            case 'est_top_deptos':
                $total = array_sum(array_map('intval', array_column($rows, 1)));

                return "Totales: {$total} equipos | {$count} departamentos";

            case 'inventario_por_estado':
            case 'inv_estado':
                $total = array_sum(array_map('intval', array_column($rows, 1)));

                return "Totales: {$total} equipos | {$count} estados";

            case 'inventario_por_tipo':
            case 'inv_tipo':
                $total = array_sum(array_map('intval', array_column($rows, 1)));

                return "Totales: {$total} equipos | {$count} tipos";

            case 'mantenimiento_por_mes':
            case 'mant_por_mes':
                $total = array_sum(array_map('intval', array_column($rows, 2)));

                return "Totales: {$total} servicios en {$count} meses";

            case 'mantenimiento_depto_pide_mas':
            case 'mant_top_deptos':
                $total = array_sum(array_map('intval', array_column($rows, 1)));

                return "Totales: {$total} solicitudes | {$count} departamentos";

            case 'rentas_proximas_vencer':
            case 'rent_por_vencer':
                return "Totales: {$count} equipos con renta próxima a vencer";

            case 'rentas_gasto_mensual':
            case 'rent_gasto_mensual':
                return "Totales: {$count} periodos proyectados";

            case 'rent_seguimiento':
                $vencidas = 0;
                $porVencer = 0;
                $vigentes = 0;
                foreach ($rows as $r) {
                    $est = (string) ($r[6] ?? '');
                    if (str_contains($est, 'Vencida')) {
                        $vencidas++;
                    } elseif (str_contains($est, 'Por vencer')) {
                        $porVencer++;
                    } else {
                        $vigentes++;
                    }
                }

                return "Totales: {$count} rentados | 🔴 {$vencidas} · 🟡 {$porVencer} · 🟢 {$vigentes}";

            case 'estadisticas_mas_movidos':
            case 'est_movidos':
                return "Top {$count} equipos con más movimientos";

            case 'historico_cambios_area':
            case 'hist_cambios_area':
                return "Totales: {$count} traslados y reubicaciones";

            case 'historico_actividad_usuario':
            case 'hist_actividad_usuario':
                $total = array_sum(array_map('intval', array_column($rows, 1)));

                return "Totales: {$total} acciones registradas";

            default:
                return "Total: {$count} registros";
        }
    }

    /**
     * Exporta el reporte solicitado al formato especificado (xlsx o csv).
     */
    public static function export(string $tipo, array $filtros = [], string $formato = 'xlsx'): Response
    {
        $data = self::getReportData($tipo, $filtros);
        $fecha = date('Y-m-d_His');
        $slug = str_replace('_', '-', $tipo);
        $fileName = "reporte-{$slug}-{$fecha}." . ($formato === 'csv' ? 'csv' : 'xlsx');

        if ($formato === 'csv') {
            return response()->streamDownload(function () use ($data) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $data['headers']);

                foreach ($data['rows'] as $row) {
                    $cleanRow = array_map(fn ($val) => strip_tags((string) $val), (array) $row);
                    fputcsv($handle, $cleanRow);
                }

                fclose($handle);
            }, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        }

        return SigemExcelExporter::export(
            $data['titulo'],
            $data['headers'],
            $data['rows'],
            $fileName
        );
    }

    /**
     * Aplica filtros de departamento, área, aula y fechas a una consulta base de inventario.
     */
    public static function aplicarFiltrosInventario($query, array $filtros): void
    {
        if (! empty($filtros['departamento_id']) && strtoupper((string) $filtros['departamento_id']) !== 'TODOS') {
            $deptoId = (int) $filtros['departamento_id'];
            $query->where(function ($q) use ($deptoId) {
                $q->where('inventario.departamento_id', $deptoId)
                    ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoId));
            });
        }

        if (! empty($filtros['area_id']) && strtoupper((string) $filtros['area_id']) !== 'TODAS') {
            $query->where('inventario.area_id', (int) $filtros['area_id']);
        }

        if (! empty($filtros['aula']) && strtoupper((string) $filtros['aula']) !== 'TODAS') {
            $aulaVal = trim((string) $filtros['aula']);
            $query->where(function ($q) use ($aulaVal) {
                $q->where('inventario.aula', $aulaVal)
                    ->orWhereHas('salon', fn ($sq) => $sq->where('nombre', $aulaVal));
            });
        }

        self::aplicarFiltroFechas($query, $filtros);
    }

    /**
     * Aplica filtro de fechas por rango (calificado con prefijo de tabla para compatibilidad con JOINs).
     */
    public static function aplicarFiltroFechas($query, array $filtros, string $tablePrefix = 'inventario'): void
    {
        if (! empty($filtros['fecha_desde'])) {
            $desde = Carbon::parse($filtros['fecha_desde'])->startOfDay();
            $query->where(function ($q) use ($desde, $tablePrefix) {
                $q->where("{$tablePrefix}.fecha_registro", '>=', $desde)
                    ->orWhere("{$tablePrefix}.created_at", '>=', $desde);
            });
        }

        if (! empty($filtros['fecha_hasta'])) {
            $hasta = Carbon::parse($filtros['fecha_hasta'])->endOfDay();
            $query->where(function ($q) use ($hasta, $tablePrefix) {
                $q->where("{$tablePrefix}.fecha_registro", '<=', $hasta)
                    ->orWhere("{$tablePrefix}.created_at", '<=', $hasta);
            });
        }
    }

    public static function resolverAreaNombre(?Inventario $inv): string
    {
        return $inv?->area?->nombre ?? 'Sin área';
    }

    public static function resolverDeptoNombre(?Inventario $inv, array $deptosMap = []): string
    {
        if (! $inv) {
            return 'Sin depto';
        }

        $deptoId = $inv->departamento_id ?? $inv->area?->id_departamento;
        if ($deptoId && isset($deptosMap[$deptoId])) {
            return $deptosMap[$deptoId];
        }

        if ($inv->relationLoaded('departamento') && $inv->departamento) {
            return $inv->departamento->nombre;
        }

        if ($inv->relationLoaded('area') && $inv->area && $inv->area->relationLoaded('departamento') && $inv->area->departamento) {
            return $inv->area->departamento->nombre;
        }

        return 'Sin depto';
    }

    public static function resolverReceptorNombre(?Inventario $inv): string
    {
        if (! $inv) {
            return '—';
        }

        $specs = is_array($inv->especificaciones) ? $inv->especificaciones : [];
        $nombre = trim((string) ($specs['receptor_nombre'] ?? ''));

        if ($nombre !== '') {
            return $nombre;
        }

        if ($inv->relationLoaded('receptor') && $inv->receptor) {
            $completo = trim((string) $inv->receptor->nombre_completo);
            if ($completo !== '') {
                return $completo;
            }
        }

        return '—';
    }

    public static function resolverFechaRegistro(?Inventario $inv): string
    {
        if (! $inv) {
            return '—';
        }

        if ($inv->fecha_registro) {
            return $inv->fecha_registro->format('d/m/Y');
        }

        return $inv->created_at ? $inv->created_at->format('d/m/Y') : '—';
    }

    public static function resolverHoraRegistro(?Inventario $inv): string
    {
        return ($inv && $inv->created_at) ? $inv->created_at->format('H:i') : '—';
    }

    public static function resolverTipoPropiedad(?Inventario $inv): string
    {
        return $inv?->tipo_propiedad ?: 'Propio';
    }
}
