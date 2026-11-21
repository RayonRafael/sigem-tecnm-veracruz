<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Retorna el catálogo completo de los 28 reportes organizados en 5 sub-pestañas.
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

        foreach (self::getCatalogo() as $subpestana => $reportes) {
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
     * Construye el dataset de un reporte consultando la BD de forma optimizada (agregaciones SQL y eager loading).
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
        $rows = [];

        switch ($canonical) {
            // ==========================================
            // SUB-PESTAÑA 1: INVENTARIO (6 reportes)
            // ==========================================
            case 'inventario_general':
                $deptosMap = self::getDepartamentosMap();
                $q = Inventario::with(['material.marca', 'material.tipo', 'area', 'salon'])->orderBy('codigo');
                self::aplicarFiltrosInventario($q, $filtros);

                foreach ($q->get() as $inv) {
                    $deptoId = $inv->departamento_id ?? $inv->area?->id_departamento;
                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $inv->material?->tipo?->nombre ?? $inv->tipo_equipo ?? 'General',
                        $inv->material?->marca?->nombre ?? ($inv->especificaciones['marca'] ?? '—'),
                        $inv->modelo ?? $inv->material?->modelo ?? '—',
                        $inv->estado ?? 'Disponible',
                        ($deptoId && isset($deptosMap[$deptoId])) ? $deptosMap[$deptoId] : 'Sin depto',
                        $inv->area?->nombre ?? 'Sin área',
                        $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? '—')),
                        $inv->num_serie ?: 'S/N',
                    ];
                }
                break;

            case 'inventario_por_departamento':
                $deptoFiltro = ! empty($filtros['departamento_id']) ? (int) $filtros['departamento_id'] : null;
                $deptosMap = self::getDepartamentosMap();
                if ($deptoFiltro) {
                    $deptosMap = isset($deptosMap[$deptoFiltro]) ? [$deptoFiltro => $deptosMap[$deptoFiltro]] : [];
                }

                // 1 sola consulta agregada en lugar de 2N queries en bucle
                $statsQ = Inventario::query()
                    ->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                    ->selectRaw("
                        COALESCE(inventario.departamento_id, area.id_departamento) as depto_id,
                        COUNT(*) as total,
                        SUM(CASE WHEN inventario.tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
                    ")
                    ->groupByRaw('COALESCE(inventario.departamento_id, area.id_departamento)');
                self::aplicarFiltroFechas($statsQ, $filtros);
                if ($deptoFiltro) {
                    $statsQ->whereRaw('COALESCE(inventario.departamento_id, area.id_departamento) = ?', [$deptoFiltro]);
                }

                $statsByDepto = $statsQ->get()->keyBy(fn ($r) => $r->depto_id === null ? '__null__' : (int) $r->depto_id);

                foreach ($deptosMap as $idDepto => $nombreDepto) {
                    $stat = $statsByDepto->get((int) $idDepto);
                    $total = (int) ($stat->total ?? 0);
                    $rentados = (int) ($stat->rentados ?? 0);
                    $rows[] = [$nombreDepto, $total, $total - $rentados, $rentados];
                }

                if (! $deptoFiltro && $statsByDepto->has('__null__')) {
                    $statSin = $statsByDepto->get('__null__');
                    $totalSin = (int) ($statSin->total ?? 0);
                    if ($totalSin > 0) {
                        $rentadosSin = (int) ($statSin->rentados ?? 0);
                        $rows[] = ['Sin departamento asignado', $totalSin, $totalSin - $rentadosSin, $rentadosSin];
                    }
                }
                break;

            case 'inventario_por_area':
                $deptosMap = self::getDepartamentosMap();
                $areasQ = Area::orderBy('nombre');
                if (! empty($filtros['departamento_id'])) {
                    $areasQ->where('id_departamento', $filtros['departamento_id']);
                }
                $areas = $areasQ->get();

                // 1 sola consulta GROUP BY en lugar de N queries por área
                $countsQ = Inventario::query()
                    ->whereNotNull('area_id')
                    ->selectRaw('area_id, COUNT(*) as total')
                    ->groupBy('area_id');
                self::aplicarFiltroFechas($countsQ, $filtros);
                $countsByArea = $countsQ->pluck('total', 'area_id');

                foreach ($areas as $area) {
                    $rows[] = [
                        $area->nombre,
                        ($area->id_departamento && isset($deptosMap[$area->id_departamento])) ? $deptosMap[$area->id_departamento] : 'Sin depto',
                        (int) ($countsByArea[$area->id_area] ?? 0),
                    ];
                }
                break;

            case 'inventario_por_tipo':
                $tipos = TipoMaterial::orderBy('nombre')->get();

                // 1 sola consulta agregada con JOIN material en lugar de 2N queries whereHas
                $statsQ = Inventario::query()
                    ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
                    ->selectRaw("
                        material.id_tipodematerial as tipo_id,
                        COUNT(*) as total,
                        SUM(CASE WHEN inventario.tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
                    ")
                    ->groupBy('material.id_tipodematerial');
                self::aplicarFiltrosInventario($statsQ, $filtros);
                $statsByTipo = $statsQ->get()->keyBy('tipo_id');

                foreach ($tipos as $tipoObj) {
                    $stat = $statsByTipo->get($tipoObj->id_tipodematerial);
                    $total = (int) ($stat->total ?? 0);
                    if ($total > 0) {
                        $rentados = (int) ($stat->rentados ?? 0);
                        $rows[] = [$tipoObj->nombre, $total, $total - $rentados, $rentados];
                    }
                }
                break;

            case 'inventario_por_estado':
                $baseQ = Inventario::query();
                self::aplicarFiltrosInventario($baseQ, $filtros);

                $estadosDb = $baseQ->selectRaw("COALESCE(estado, 'Disponible') as est, COUNT(*) as cant")
                    ->groupByRaw("COALESCE(estado, 'Disponible')")
                    ->pluck('cant', 'est')
                    ->toArray();
                $totalGeneral = array_sum($estadosDb);

                foreach ($estadosDb as $estadoNom => $cant) {
                    $porcentaje = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
                    $rows[] = [$estadoNom, (int) $cant, $porcentaje];
                }
                break;

            case 'inventario_por_aula':
                $deptosMap = self::getDepartamentosMap();
                $baseQ = Inventario::with(['area', 'salon']);
                self::aplicarFiltrosInventario($baseQ, $filtros);

                $equipos = $baseQ->get();
                $agrupados = $equipos->groupBy(function ($inv) {
                    $aula = $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? 'Sin aula'));
                    $areaId = $inv->area_id ?? 'sin_area';
                    return "{$aula}|{$areaId}";
                });

                foreach ($agrupados as $grupo) {
                    $primer = $grupo->first();
                    $aulaNom = $primer->aula_efectiva ?? ($primer->aula ?: ($primer->salon?->nombre ?? 'Sin aula'));
                    $areaNom = $primer->area?->nombre ?? 'Sin área';
                    $deptoId = $primer->departamento_id ?? $primer->area?->id_departamento;
                    $deptoNom = ($deptoId && isset($deptosMap[$deptoId])) ? $deptosMap[$deptoId] : 'Sin depto';

                    $rows[] = [
                        $aulaNom,
                        $areaNom,
                        $deptoNom,
                        $grupo->count(),
                    ];
                }
                break;

            // ==========================================
            // SUB-PESTAÑA 2: ESTADÍSTICAS (6 reportes)
            // ==========================================
            case 'estadisticas_antiguos':
                $q = Inventario::with(['material', 'departamento', 'area.departamento']);
                self::aplicarFiltrosInventario($q, $filtros);
                $q->orderByRaw('COALESCE(fecha_factura, fecha_registro, created_at) ASC')->limit(20);

                foreach ($q->get() as $inv) {
                    $fecha = $inv->fecha_factura?->format('d/m/Y')
                        ?? $inv->fecha_registro?->format('d/m/Y')
                        ?? ($inv->created_at ? $inv->created_at->format('d/m/Y') : '—');

                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $fecha,
                        $inv->departamento?->nombre ?? $inv->area?->departamento?->nombre ?? 'Sin depto',
                        $inv->estado ?? 'Disponible',
                    ];
                }
                break;

            case 'estadisticas_sin_asignar':
                $q = Inventario::with(['material.tipo'])->where('estado', 'Disponible')
                    ->where(function ($sub) {
                        $sub->whereNull('departamento_id')->orWhereNull('area_id');
                    });
                self::aplicarFiltroFechas($q, $filtros);

                foreach ($q->get() as $inv) {
                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $inv->material?->tipo?->nombre ?? 'General',
                        $inv->fecha_registro ? $inv->fecha_registro->format('d/m/Y') : '—',
                    ];
                }
                break;

            case 'estadisticas_depto_mas_equipos':
                // 1 sola consulta agregada en lugar de N queries por departamento
                $statsQ = Inventario::query()
                    ->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                    ->selectRaw('COALESCE(inventario.departamento_id, area.id_departamento) as depto_id, COUNT(*) as cant')
                    ->groupByRaw('COALESCE(inventario.departamento_id, area.id_departamento)');
                self::aplicarFiltroFechas($statsQ, $filtros);

                $countsByDepto = $statsQ->get();
                $totalGeneral = (int) $countsByDepto->sum('cant');
                $deptosMap = Departamento::pluck('nombre', 'id_departamento');

                $ranking = [];
                foreach ($countsByDepto as $row) {
                    if ($row->depto_id !== null && isset($deptosMap[$row->depto_id]) && (int) $row->cant > 0) {
                        $cant = (int) $row->cant;
                        $pct = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
                        $ranking[] = [$deptosMap[$row->depto_id], $cant, $pct];
                    }
                }

                usort($ranking, fn ($a, $b) => $b[1] <=> $a[1]);
                $rows = $ranking;
                break;

            case 'estadisticas_mas_movidos':
                // Agregación SQL + carga en bloque whereIn (elimina N+1 de find() en bucle)
                $agg = HistorialEstado::query()
                    ->where(function ($q) {
                        $q->where('motivo_cambio', 'like', '%área%')
                            ->orWhere('motivo_cambio', 'like', '%ubicación%')
                            ->orWhere('motivo_cambio', 'like', '%Cambio de área%')
                            ->orWhere('motivo_cambio', 'like', '%Cambio%');
                    })
                    ->selectRaw('id_inventario, COUNT(*) as cant, MAX(fecha_cambio) as ultimo_cambio')
                    ->groupBy('id_inventario')
                    ->orderByDesc('cant')
                    ->limit(50)
                    ->get();

                $ids = $agg->pluck('id_inventario')->filter()->all();
                $inventariosMap = ! empty($ids)
                    ? Inventario::with('material')->whereIn('id_inventario', $ids)->get()->keyBy('id_inventario')
                    : collect();

                foreach ($agg as $row) {
                    $inv = $inventariosMap->get($row->id_inventario);
                    if ($inv) {
                        $fechaUltimo = $row->ultimo_cambio ? Carbon::parse($row->ultimo_cambio)->format('d/m/Y') : '—';
                        $rows[] = [
                            $inv->codigo ?: ('#' . $inv->id_inventario),
                            $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                            (int) $row->cant,
                            $fechaUltimo,
                        ];
                    }
                }
                break;

            case 'estadisticas_propio_vs_rentado':
                $baseQ = Inventario::query();
                self::aplicarFiltrosInventario($baseQ, $filtros);
                $agg = $baseQ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
                ")->first();

                $totalGeneral = (int) ($agg->total ?? 0);
                $rentados = (int) ($agg->rentados ?? 0);
                $propios = $totalGeneral - $rentados;

                $pctPropios = $totalGeneral > 0 ? round(($propios / $totalGeneral) * 100, 1) . '%' : '0%';
                $pctRentados = $totalGeneral > 0 ? round(($rentados / $totalGeneral) * 100, 1) . '%' : '0%';

                $rows = [
                    ['Propio (Patrimonial)', $propios, $pctPropios],
                    ['Rentado (Arrendamiento)', $rentados, $pctRentados],
                ];
                break;

            case 'estadisticas_resumen_general':
                $baseQ = Inventario::query();
                self::aplicarFiltrosInventario($baseQ, $filtros);
                $agg = $baseQ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
                ")->first();

                $totalEquipos = (int) ($agg->total ?? 0);
                $rentados = (int) ($agg->rentados ?? 0);
                $propios = $totalEquipos - $rentados;

                $totalDeptos = Departamento::count();
                $totalAreas = Area::count();
                $totalUsuarios = User::count();

                $rows = [
                    [$totalEquipos, $totalDeptos, $totalAreas, $totalUsuarios, $propios, $rentados],
                ];
                break;

            // ==========================================
            // SUB-PESTAÑA 3: MANTENIMIENTO (6 reportes)
            // ==========================================
            case 'mantenimiento_depto_pide_mas':
                $mantQ = Mantenimiento::with(['inventario.departamento', 'inventario.area.departamento']);
                if (! empty($filtros['fecha_desde'])) {
                    $mantQ->where('fecha_solicitud', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $mantQ->where('fecha_solicitud', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }
                $mants = $mantQ->get();

                $agrupados = $mants->groupBy(function ($m) {
                    return $m->inventario?->departamento?->nombre
                        ?? $m->inventario?->area?->departamento?->nombre
                        ?? 'Sin depto';
                });

                $datos = [];
                foreach ($agrupados as $deptoNom => $grupo) {
                    $completadas = $grupo->where('estado', 'Completado')->count();
                    $pendientes = $grupo->count() - $completadas;
                    $datos[] = [$deptoNom, $grupo->count(), $completadas, $pendientes];
                }

                usort($datos, fn ($a, $b) => $b[1] <=> $a[1]);
                $rows = $datos;
                break;

            case 'mantenimiento_tipo_mas_frecuente':
                $mants = Mantenimiento::with(['inventario.material.tipo'])->get();
                $totalMants = $mants->count();

                $agrupados = $mants->groupBy(function ($m) {
                    return $m->inventario?->material?->tipo?->nombre
                        ?? $m->inventario?->subtipo
                        ?? 'General';
                });

                $datos = [];
                foreach ($agrupados as $tipoNom => $grupo) {
                    $cant = $grupo->count();
                    $frecuencia = $totalMants > 0 ? round(($cant / $totalMants) * 100, 1) . '%' : '0%';
                    $datos[] = [$tipoNom, $cant, $frecuencia];
                }

                usort($datos, fn ($a, $b) => $b[1] <=> $a[1]);
                $rows = $datos;
                break;

            case 'mantenimiento_por_mes':
                $mantQ = Mantenimiento::query()->select(['id_mantenimiento', 'fecha_solicitud'])->whereNotNull('fecha_solicitud');
                if (! empty($filtros['fecha_desde'])) {
                    $mantQ->where('fecha_solicitud', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $mantQ->where('fecha_solicitud', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }
                $mants = $mantQ->get();

                $agrupados = $mants->groupBy(function ($m) {
                    $dt = Carbon::parse($m->fecha_solicitud);
                    return $dt->format('Y-m');
                })->sortKeysDesc();

                $mesesNombres = [
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
                ];

                foreach ($agrupados as $ym => $grupo) {
                    [$anio, $mesNum] = explode('-', $ym);
                    $mesTexto = $mesesNombres[$mesNum] ?? $mesNum;
                    $rows[] = [$mesTexto, $anio, $grupo->count()];
                }
                break;

            case 'mantenimiento_en_curso':
                $mants = Mantenimiento::with(['inventario.material', 'inventario.departamento', 'inventario.area.departamento'])
                    ->whereIn('estado', ['Pendiente', 'En proceso', 'En Mantenimiento'])
                    ->get();

                foreach ($mants as $m) {
                    $inv = $m->inventario;
                    $rows[] = [
                        $inv?->codigo ?: ('#' . $inv?->id_inventario),
                        $inv?->material?->nombre ?? $inv?->subtipo ?? 'Equipo',
                        $inv?->departamento?->nombre ?? $inv?->area?->departamento?->nombre ?? 'Sin depto',
                        $m->fecha_solicitud ? Carbon::parse($m->fecha_solicitud)->format('d/m/Y') : '—',
                    ];
                }
                break;

            case 'mantenimiento_tiempo_promedio':
                $mants = Mantenimiento::with(['inventario.material.tipo'])
                    ->where('estado', 'Completado')
                    ->whereNotNull('fecha_solicitud')
                    ->whereNotNull('fecha_fin')
                    ->get();

                $agrupados = $mants->groupBy(function ($m) {
                    return $m->inventario?->material?->tipo?->nombre ?? $m->tipo_mantenimiento ?? 'General';
                });

                foreach ($agrupados as $tipoNom => $grupo) {
                    $diasArr = [];
                    foreach ($grupo as $m) {
                        $ini = Carbon::parse($m->fecha_solicitud);
                        $fin = Carbon::parse($m->fecha_fin);
                        $diasArr[] = max(0, $ini->diffInDays($fin));
                    }

                    if (! empty($diasArr)) {
                        $promedio = round(array_sum($diasArr) / count($diasArr), 1);
                        $min = min($diasArr);
                        $max = max($diasArr);
                        $rows[] = [$tipoNom, $promedio . ' días', $min . ' días', $max . ' días'];
                    }
                }
                break;

            case 'mantenimiento_historial_equipo':
                $mants = Mantenimiento::with(['inventario.material'])->orderBy('fecha_solicitud', 'desc');
                if (! empty($filtros['fecha_desde'])) {
                    $mants->where('fecha_solicitud', '>=', $filtros['fecha_desde']);
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $mants->where('fecha_solicitud', '<=', $filtros['fecha_hasta']);
                }

                foreach ($mants->limit(200)->get() as $m) {
                    $inv = $m->inventario;
                    $rows[] = [
                        $inv?->codigo ?: ('#' . $inv?->id_inventario),
                        $inv?->material?->nombre ?? $inv?->subtipo ?? 'Equipo',
                        $m->fecha_solicitud ? Carbon::parse($m->fecha_solicitud)->format('d/m/Y') : '—',
                        $m->tipo_servicio ?? $m->tipo_mantenimiento ?? 'Correctivo',
                        $m->estado ?? 'Pendiente',
                    ];
                }
                break;

            // ==========================================
            // SUB-PESTAÑA 4: RENTAS (5 reportes)
            // ==========================================
            case 'rentas_equipos':
                $q = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor']);
                self::aplicarFiltrosInventario($q, $filtros);

                foreach ($q->get() as $inv) {
                    $ini = $inv->fecha_inicio_renta?->format('d/m/Y') ?? ($inv->especificaciones['fecha_inicio_renta'] ?? '—');
                    $fin = $inv->fecha_fin_renta?->format('d/m/Y') ?? ($inv->especificaciones['fecha_fin_renta'] ?? '—');

                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                        $ini,
                        $fin,
                    ];
                }
                break;

            case 'rentas_proximas_vencer':
                $hoy = Carbon::now()->startOfDay();
                $limite = Carbon::now()->addDays(90)->endOfDay();

                $rentados = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor'])->get();
                foreach ($rentados as $inv) {
                    $fechaFin = $inv->fecha_fin_renta ?? (! empty($inv->especificaciones['fecha_fin_renta']) ? Carbon::parse($inv->especificaciones['fecha_fin_renta']) : null);
                    if ($fechaFin && $fechaFin->gte($hoy) && $fechaFin->lte($limite)) {
                        $diasRestantes = (int) $hoy->diffInDays($fechaFin, false);
                        $rows[] = [
                            $inv->codigo ?: ('#' . $inv->id_inventario),
                            $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                            $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                            $fechaFin->format('d/m/Y'),
                            max(0, $diasRestantes) . ' días',
                        ];
                    }
                }
                break;

            case 'rentas_vencidas':
                $hoy = Carbon::now()->startOfDay();
                $rentados = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor'])->get();

                foreach ($rentados as $inv) {
                    $fechaFin = $inv->fecha_fin_renta ?? (! empty($inv->especificaciones['fecha_fin_renta']) ? Carbon::parse($inv->especificaciones['fecha_fin_renta']) : null);
                    if ($fechaFin && $fechaFin->lt($hoy)) {
                        $diasVencida = (int) $fechaFin->diffInDays($hoy);
                        $rows[] = [
                            $inv->codigo ?: ('#' . $inv->id_inventario),
                            $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                            $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                            $fechaFin->format('d/m/Y'),
                            $diasVencida . ' días vencida',
                        ];
                    }
                }
                break;

            case 'rentas_gasto_mensual':
                $rentados = Inventario::where('tipo_propiedad', 'Rentado')->get();
                $agrupados = $rentados->groupBy(function ($inv) {
                    $fecha = $inv->fecha_inicio_renta ?? $inv->created_at ?? Carbon::now();
                    return Carbon::parse($fecha)->format('Y-m');
                })->sortKeysDesc();

                $mesesNombres = [
                    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
                    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
                    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
                ];

                foreach ($agrupados as $ym => $grupo) {
                    [$anio, $mesNum] = explode('-', $ym);
                    $mesTexto = ($mesesNombres[$mesNum] ?? $mesNum) . ' ' . $anio;
                    $cant = $grupo->count();
                    // Costo estimado ($1,200 MXN mensuales promedio institucional por equipo rentado si no especifica)
                    $costoTotal = 0;
                    foreach ($grupo as $item) {
                        $costoItem = isset($item->especificaciones['costo_renta']) && is_numeric($item->especificaciones['costo_renta'])
                            ? (float) $item->especificaciones['costo_renta']
                            : 1200.00;
                        $costoTotal += $costoItem;
                    }

                    $rows[] = [
                        $mesTexto,
                        $cant,
                        '$' . number_format($costoTotal, 2) . ' MXN',
                    ];
                }
                break;

            case 'rentas_por_proveedor':
                $proveedores = Proveedor::with(['inventarios' => fn ($iq) => $iq->where('tipo_propiedad', 'Rentado')])->get();
                foreach ($proveedores as $prov) {
                    $cant = $prov->inventarios->count();
                    if ($cant > 0) {
                        $fechas = $prov->inventarios->map(fn ($i) => $i->fecha_inicio_renta ?? $i->fecha_registro)->filter();
                        $minFecha = $fechas->min();
                        $maxFecha = $fechas->max();

                        $rows[] = [
                            $prov->nombre_empresa,
                            $cant,
                            $minFecha ? Carbon::parse($minFecha)->format('d/m/Y') : '—',
                            $maxFecha ? Carbon::parse($maxFecha)->format('d/m/Y') : '—',
                        ];
                    }
                }
                break;

            case 'rent_seguimiento':
                $q = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor']);
                self::aplicarFiltrosInventario($q, $filtros);

                $items = [];
                foreach ($q->get() as $inv) {
                    $estado = $inv->renta_estado ?? 'Vigente';
                    $dias = $inv->renta_dias_restantes;
                    $prioridad = match ($estado) {
                        'Vencida' => 0,
                        'Por vencer' => 1,
                        default => 2,
                    };
                    $badgeTexto = match ($estado) {
                        'Vencida' => '🔴 Vencida',
                        'Por vencer' => '🟡 Por vencer',
                        default => '🟢 Vigente',
                    };

                    $items[] = [
                        'prioridad' => $prioridad,
                        'dias_sort' => $dias ?? PHP_INT_MAX,
                        'row' => [
                            $inv->codigo ?: ('#' . $inv->id_inventario),
                            $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                            $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                            $inv->fecha_registro ? $inv->fecha_registro->format('d/m/Y') : '—',
                            $inv->renta_fecha_fin ? $inv->renta_fecha_fin->format('d/m/Y') : '—',
                            $dias !== null ? ($dias . ' días') : '—',
                            $badgeTexto,
                        ],
                    ];
                }

                // Orden estricto: 1. 🔴 Vencidas -> 2. 🟡 Por vencer -> 3. 🟢 Vigentes (y por días restantes asc)
                usort($items, function (array $a, array $b): int {
                    if ($a['prioridad'] !== $b['prioridad']) {
                        return $a['prioridad'] <=> $b['prioridad'];
                    }

                    return $a['dias_sort'] <=> $b['dias_sort'];
                });

                $rows = array_column($items, 'row');
                break;

            // ==========================================
            // SUB-PESTAÑA 5: HISTÓRICO (5 reportes)
            // ==========================================
            case 'historico_cambios_area':
                $historial = HistorialEstado::where(function ($q) {
                    $q->where('motivo_cambio', 'like', '%área%')
                        ->orWhere('motivo_cambio', 'like', '%ubicación%')
                        ->orWhere('motivo_cambio', 'like', '%Cambio%');
                })->with(['inventario.material', 'usuario'])->orderBy('fecha_cambio', 'desc');

                if (! empty($filtros['fecha_desde'])) {
                    $historial->where('fecha_cambio', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $historial->where('fecha_cambio', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }

                foreach ($historial->limit(200)->get() as $h) {
                    $motivo = $h->motivo_cambio ?? '';
                    $partes = explode('→', $motivo);
                    $origen = isset($partes[0]) ? trim(str_replace(['Cambio de área:', 'Cambio de ubicación:'], '', $partes[0])) : '—';
                    $destino = isset($partes[1]) ? trim($partes[1]) : ($h->ubicacion_fisica ?: '—');

                    $rows[] = [
                        $h->fecha_cambio ? Carbon::parse($h->fecha_cambio)->format('d/m/Y H:i') : '—',
                        $h->inventario?->codigo ?: ($h->inventario?->material?->nombre ?? ('#' . $h->id_inventario)),
                        $origen ?: 'Sin origen',
                        $destino ?: 'Sin destino',
                        $h->usuario?->name ?? 'Sistema',
                    ];
                }
                break;

            case 'historico_bajas':
                $bajas = Inventario::whereIn('estado', ['Dado de baja', 'Baja'])->with(['material', 'usuario'])->orderBy('fecha_baja', 'desc');
                self::aplicarFiltrosInventario($bajas, $filtros);

                foreach ($bajas->get() as $inv) {
                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $inv->fecha_baja ? Carbon::parse($inv->fecha_baja)->format('d/m/Y') : '—',
                        $inv->motivo_baja ?? ($inv->especificaciones['motivo_baja'] ?? 'Dictamen técnico'),
                        $inv->usuario?->name ?? 'Administrador',
                    ];
                }
                break;

            case 'historico_entregas':
                $solicitudes = Solicitud::with(['detalles.inventario.material', 'receptor', 'usuario'])
                    ->where(function ($q) {
                        $q->where('tipo_movimiento', 'like', '%entrega%')
                            ->orWhere('tipo_movimiento', 'like', '%asignac%')
                            ->orWhere('estado', 'Autorizado');
                    })
                    ->orderBy('fecha_solicitud', 'desc');

                if (! empty($filtros['fecha_desde'])) {
                    $solicitudes->where('fecha_solicitud', '>=', $filtros['fecha_desde']);
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $solicitudes->where('fecha_solicitud', '<=', $filtros['fecha_hasta']);
                }

                foreach ($solicitudes->limit(200)->get() as $sol) {
                    $recNom = $sol->receptor ? trim("{$sol->receptor->nombre} {$sol->receptor->apellido_paterno}") : 'Sin receptor';
                    $deptoRec = $sol->receptor?->departamento ?? '—';
                    $fechaSol = $sol->fecha_solicitud ? Carbon::parse($sol->fecha_solicitud)->format('d/m/Y') : '—';

                    if ($sol->detalles->isNotEmpty()) {
                        foreach ($sol->detalles as $det) {
                            $cod = $det->inventario?->codigo ?: ($det->inventario?->material?->nombre ?? ('Sol #' . $sol->id_solicitud));
                            $rows[] = [$fechaSol, $cod, $recNom, $deptoRec];
                        }
                    } else {
                        $rows[] = [$fechaSol, 'Solicitud #' . $sol->id_solicitud, $recNom, $deptoRec];
                    }
                }
                break;

            case 'historico_actividad_usuario':
                // Agregación SQL directa: evita cargar todos los registros HistorialEstado en memoria
                $agg = HistorialEstado::query()
                    ->selectRaw('id_usuario, COUNT(*) as cant, MAX(fecha_cambio) as ultima_fecha')
                    ->groupBy('id_usuario')
                    ->orderByDesc('cant')
                    ->get();

                $userIds = $agg->pluck('id_usuario')->filter()->unique()->all();
                $usersMap = ! empty($userIds)
                    ? User::whereIn('id', $userIds)->pluck('name', 'id')
                    : collect();

                foreach ($agg as $row) {
                    $idUser = $row->id_usuario;
                    $nombre = ($idUser && isset($usersMap[$idUser]))
                        ? $usersMap[$idUser]
                        : ($idUser ? "Usuario #{$idUser}" : 'Sistema');
                    $fechaUlt = $row->ultima_fecha ? Carbon::parse($row->ultima_fecha)->format('d/m/Y H:i') : '—';
                    $rows[] = [$nombre, (int) $row->cant, $fechaUlt];
                }
                break;

            case 'historico_general':
                $historial = HistorialEstado::with(['inventario.material', 'usuario'])->orderBy('fecha_cambio', 'desc');
                if (! empty($filtros['fecha_desde'])) {
                    $historial->where('fecha_cambio', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $historial->where('fecha_cambio', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }

                foreach ($historial->limit(500)->get() as $h) {
                    $eq = $h->inventario?->codigo ?: ($h->inventario?->material?->nombre ?? ('Equipo #' . $h->id_inventario));
                    $rows[] = [
                        $h->fecha_cambio ? Carbon::parse($h->fecha_cambio)->format('d/m/Y H:i') : '—',
                        $eq,
                        $h->estado_anterior ?? '—',
                        $h->estado_nuevo ?? '—',
                        $h->motivo_cambio ?? '—',
                        $h->usuario?->name ?? 'Sistema',
                    ];
                }
                break;

            default:
                $rows = [];
                break;
        }

        $hasChart = ! empty($meta['has_chart']);
        $chartConfig = $hasChart ? self::buildChartConfig($meta, $headers, $rows) : null;
        $totalesLabel = self::computeTotalesLabel($meta['id'] ?? $canonical, $rows);

        // Preview de columnas (primeras 3) y primeras 5 filas
        $top5Headers = array_slice($headers, 0, min(3, count($headers)));
        $top5Rows = [];
        foreach (array_slice($rows, 0, 5) as $r) {
            $top5Rows[] = array_slice((array) $r, 0, min(3, count($headers)));
        }

        return [
            'id' => $meta['id'] ?? $canonical,
            'alias' => $meta['alias'] ?? ($meta['id'] ?? $canonical),
            'subpestana' => $meta['categoria'] ?? 'inventario',
            'titulo' => $meta['titulo'] ?? 'Reporte',
            'emoji' => $meta['icono'] ?? '📊',
            'descripcion' => $meta['descripcion'] ?? '',
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
     * Construye la estructura de datos compatible con Chart.js para los 12 reportes visuales.
     */
    public static function buildChartConfig(array $meta, array $headers, array $rows): ?array
    {
        if (empty($meta['has_chart'])) {
            return null;
        }

        $type = $meta['chart_type'] ?? 'bar';
        $labels = [];
        $data = [];
        $id = $meta['id'] ?? '';

        switch ($id) {
            case 'estadisticas_propio_vs_rentado':
                foreach ($rows as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'inventario_por_estado':
                foreach (array_slice($rows, 0, 7) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'inventario_por_departamento':
            case 'estadisticas_depto_mas_equipos':
                foreach (array_slice($rows, 0, 8) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'inventario_por_tipo':
                foreach (array_slice($rows, 0, 7) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'mantenimiento_por_mes':
                foreach (array_slice($rows, 0, 12) as $r) {
                    $labels[] = trim(($r[0] ?? '') . ' ' . ($r[1] ?? ''));
                    $data[] = (int) ($r[2] ?? 0);
                }
                break;

            case 'mantenimiento_depto_pide_mas':
                foreach (array_slice($rows, 0, 8) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            case 'rentas_proximas_vencer':
                $r15 = 0; $r30 = 0; $r60 = 0;
                foreach ($rows as $r) {
                    $dias = (int) preg_replace('/[^0-9]/', '', (string) ($r[4] ?? '0'));
                    if ($dias <= 15) {
                        $r15++;
                    } elseif ($dias <= 30) {
                        $r30++;
                    } else {
                        $r60++;
                    }
                }
                $labels = ['1 a 15 días', '16 a 30 días', '31 a 90 días'];
                $data = [$r15, $r30, $r60];
                break;

            case 'rentas_gasto_mensual':
                foreach (array_slice($rows, 0, 12) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $val = preg_replace('/[^0-9.]/', '', (string) ($r[2] ?? ($r[1] ?? 0)));
                    $data[] = (float) $val;
                }
                break;

            case 'estadisticas_mas_movidos':
                foreach (array_slice($rows, 0, 8) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[2] ?? 0);
                }
                break;

            case 'historico_cambios_area':
                $porMes = [];
                foreach ($rows as $r) {
                    $f = (string) ($r[0] ?? '');
                    $mesKey = strlen($f) >= 10 ? substr($f, 3, 7) : 'General';
                    $porMes[$mesKey] = ($porMes[$mesKey] ?? 0) + 1;
                }
                if (empty($porMes)) {
                    $labels = ['Sin datos'];
                    $data = [0];
                } else {
                    $labels = array_keys($porMes);
                    $data = array_values($porMes);
                }
                break;

            case 'historico_actividad_usuario':
                foreach (array_slice($rows, 0, 6) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = (int) ($r[1] ?? 0);
                }
                break;

            default:
                foreach (array_slice($rows, 0, 6) as $r) {
                    $labels[] = (string) ($r[0] ?? '');
                    $data[] = isset($r[1]) && is_numeric($r[1]) ? (int) $r[1] : 1;
                }
                break;
        }

        if (empty($labels)) {
            $labels = ['Sin registros'];
            $data = [0];
        }

        $isPieOrDonut = in_array($type, ['pie', 'doughnut'], true);
        if ($isPieOrDonut) {
            $palette = $meta['chart_colors'] ?? ['#1b65d4', '#059669', '#d97706', '#dc2626', '#8b5cf6', '#0891b2', '#64748b'];
            $bgColors = [];
            foreach ($labels as $idx => $lbl) {
                $bgColors[] = $palette[$idx % count($palette)];
            }
        } else {
            $bgColors = $meta['chart_color'] ?? '#1b65d4';
        }

        return [
            'type' => $type,
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $meta['titulo'] ?? 'Reporte',
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                    'borderColor' => $isPieOrDonut ? '#ffffff' : ($meta['chart_color'] ?? '#1b65d4'),
                    'borderWidth' => $isPieOrDonut ? 2 : 1.5,
                    'fill' => $type === 'line' ? false : true,
                    'tension' => 0.35,
                ],
            ],
            'options' => [
                'indexAxis' => ! empty($meta['chart_horizontal']) ? 'y' : 'x',
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => $isPieOrDonut,
                        'position' => 'bottom',
                        'labels' => ['boxWidth' => 10, 'font' => ['size' => 11]],
                    ],
                    'tooltip' => [
                        'enabled' => true,
                    ],
                ],
                'scales' => $isPieOrDonut ? (object) [] : [
                    'y' => ['beginAtZero' => true, 'grid' => ['color' => '#f1f5f9']],
                    'x' => ['grid' => ['display' => false]],
                ],
            ],
        ];
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
                $propios = 0; $rentados = 0;
                foreach ($rows as $r) {
                    if (stripos((string) ($r[0] ?? ''), 'propio') !== false) $propios = (int) ($r[1] ?? 0);
                    if (stripos((string) ($r[0] ?? ''), 'rent') !== false) $rentados = (int) ($r[1] ?? 0);
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
                // UTF-8 BOM para que Excel en Windows reconozca acentos y caracteres especiales
                fwrite($handle, "\xEF\xBB\xBF");

                // Headers
                fputcsv($handle, $data['headers']);

                // Rows
                foreach ($data['rows'] as $row) {
                    $cleanRow = array_map(function ($val) {
                        return strip_tags((string) $val);
                    }, (array) $row);
                    fputcsv($handle, $cleanRow);
                }

                fclose($handle);
            }, $fileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]);
        }

        // Formato XLSX nativo TecNM
        return SigemExcelExporter::export(
            $data['titulo'],
            $data['headers'],
            $data['rows'],
            $fileName
        );
    }

    /**
     * Aplica filtros de departamento y fechas a una consulta base de inventario.
     */
    protected static function aplicarFiltrosInventario($query, array $filtros): void
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
    protected static function aplicarFiltroFechas($query, array $filtros, string $tablePrefix = 'inventario'): void
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
}

