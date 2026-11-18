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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SigemReportExporter
{
    /**
     * Retorna el catálogo completo de los 28 reportes organizados en 5 sub-pestañas.
     */
    public static function getCatalogo(): array
    {
        return [
            'inventario' => [
                'inventario_general' => [
                    'num' => 1,
                    'id' => 'inventario_general',
                    'categoria' => 'inventario',
                    'titulo' => 'Inventario General',
                    'descripcion' => 'Listado completo de todos los equipos registrados en el inventario con sus datos técnicos y de ubicación.',
                    'icono' => '📋',
                    'columnas' => ['Código', 'Equipo', 'Tipo', 'Marca', 'Modelo', 'Estado', 'Depto', 'Área', 'Aula', 'Serie'],
                ],
                'inventario_por_departamento' => [
                    'num' => 2,
                    'id' => 'inventario_por_departamento',
                    'categoria' => 'inventario',
                    'titulo' => 'Por Departamento',
                    'descripcion' => 'Concentrado cuantitativo de equipos agrupados por departamento, desglosando bienes propios y rentados.',
                    'icono' => '🏢',
                    'columnas' => ['Depto', 'Cantidad equipos', 'Propios', 'Rentados'],
                ],
                'inventario_por_area' => [
                    'num' => 3,
                    'id' => 'inventario_por_area',
                    'categoria' => 'inventario',
                    'titulo' => 'Por Área',
                    'descripcion' => 'Distribución detallada de equipos en cada área académica y administrativa con su departamento.',
                    'icono' => '🗂️',
                    'columnas' => ['Área', 'Depto', 'Cantidad equipos'],
                ],
                'inventario_por_tipo' => [
                    'num' => 4,
                    'id' => 'inventario_por_tipo',
                    'categoria' => 'inventario',
                    'titulo' => 'Por Tipo',
                    'descripcion' => 'Clasificación de equipos según su tipo de material y régimen de propiedad patrimonial o rentado.',
                    'icono' => '💻',
                    'columnas' => ['Tipo', 'Cantidad', 'Propios', 'Rentados'],
                ],
                'inventario_por_estado' => [
                    'num' => 5,
                    'id' => 'inventario_por_estado',
                    'categoria' => 'inventario',
                    'titulo' => 'Por Estado',
                    'descripcion' => 'Volumen y porcentaje de equipos según su estado operativo (Disponible, Asignado, En mantenimiento, etc.).',
                    'icono' => '🔄',
                    'columnas' => ['Estado', 'Cantidad', 'Porcentaje'],
                ],
                'inventario_por_aula' => [
                    'num' => 6,
                    'id' => 'inventario_por_aula',
                    'categoria' => 'inventario',
                    'titulo' => 'Por Aula',
                    'descripcion' => 'Concentrado de equipos localizados físicamente por aula, área y departamento institucional.',
                    'icono' => '🏫',
                    'columnas' => ['Aula', 'Área', 'Depto', 'Cantidad'],
                ],
            ],

            'estadisticas' => [
                'estadisticas_antiguos' => [
                    'num' => 7,
                    'id' => 'estadisticas_antiguos',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Más antiguos (Top 20)',
                    'descripcion' => 'Los 20 equipos con mayor tiempo registrados en la institución según fecha de factura o registro.',
                    'icono' => '⏳',
                    'columnas' => ['Código', 'Equipo', 'Fecha compra', 'Depto', 'Estado'],
                ],
                'estadisticas_sin_asignar' => [
                    'num' => 8,
                    'id' => 'estadisticas_sin_asignar',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Sin asignar',
                    'descripcion' => 'Equipos disponibles en almacén o bodega sin departamento o área específica asignada.',
                    'icono' => '📦',
                    'columnas' => ['Código', 'Equipo', 'Tipo', 'Fecha registro'],
                ],
                'estadisticas_depto_mas_equipos' => [
                    'num' => 9,
                    'id' => 'estadisticas_depto_mas_equipos',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Depto con más equipos',
                    'descripcion' => 'Ranking departamental ordenado por volumen de inventario y porcentaje respecto al total institucional.',
                    'icono' => '🏆',
                    'columnas' => ['Depto', 'Cantidad', '% del total'],
                ],
                'estadisticas_mas_movidos' => [
                    'num' => 10,
                    'id' => 'estadisticas_mas_movidos',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Más movidos',
                    'descripcion' => 'Equipos con mayor número de reubicaciones y traslados registrados en el historial de estados.',
                    'icono' => '🚚',
                    'columnas' => ['Código', 'Equipo', 'Cantidad cambios', 'Último cambio'],
                ],
                'estadisticas_propio_vs_rentado' => [
                    'num' => 11,
                    'id' => 'estadisticas_propio_vs_rentado',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Propio vs Rentado',
                    'descripcion' => 'Comparativo cuantitativo y porcentual entre bienes patrimoniales y equipo en arrendamiento.',
                    'icono' => '⚖️',
                    'columnas' => ['Tipo', 'Cantidad', 'Porcentaje'],
                ],
                'estadisticas_resumen_general' => [
                    'num' => 12,
                    'id' => 'estadisticas_resumen_general',
                    'categoria' => 'estadisticas',
                    'titulo' => 'Resumen general',
                    'descripcion' => 'Indicadores consolidados con los totales globales de equipos, departamentos, áreas y usuarios.',
                    'icono' => '📈',
                    'columnas' => ['Equipos', 'Deptos', 'Áreas', 'Usuarios', 'Propios', 'Rentados'],
                ],
            ],

            'mantenimiento' => [
                'mantenimiento_depto_pide_mas' => [
                    'num' => 13,
                    'id' => 'mantenimiento_depto_pide_mas',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'Depto que pide más',
                    'descripcion' => 'Departamentos con mayor volumen de solicitudes de mantenimiento, clasificadas por estado.',
                    'icono' => '🛠️',
                    'columnas' => ['Depto', 'Solicitudes', 'Completadas', 'Pendientes'],
                ],
                'mantenimiento_tipo_mas_frecuente' => [
                    'num' => 14,
                    'id' => 'mantenimiento_tipo_mas_frecuente',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'Tipo con más mantenimiento',
                    'descripcion' => 'Tipos de equipo que demandan mayor frecuencia de atención y reparación técnica.',
                    'icono' => '⚠️',
                    'columnas' => ['Tipo', 'Solicitudes', 'Frecuencia'],
                ],
                'mantenimiento_por_mes' => [
                    'num' => 15,
                    'id' => 'mantenimiento_por_mes',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'Por mes',
                    'descripcion' => 'Comportamiento cronológico mensual del número de órdenes de servicio registradas.',
                    'icono' => '📅',
                    'columnas' => ['Mes', 'Año', 'Cantidad'],
                ],
                'mantenimiento_en_curso' => [
                    'num' => 16,
                    'id' => 'mantenimiento_en_curso',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'En mantenimiento',
                    'descripcion' => 'Equipos que se encuentran actualmente bajo intervención técnica o pendientes de atención.',
                    'icono' => '🔧',
                    'columnas' => ['Código', 'Equipo', 'Depto', 'Fecha entrada'],
                ],
                'mantenimiento_tiempo_promedio' => [
                    'num' => 17,
                    'id' => 'mantenimiento_tiempo_promedio',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'Tiempo promedio',
                    'descripcion' => 'Duración media, mínima y máxima en días para concluir los servicios de mantenimiento completados.',
                    'icono' => '⏱️',
                    'columnas' => ['Tipo', 'Días promedio', 'Mín', 'Máx'],
                ],
                'mantenimiento_historial_equipo' => [
                    'num' => 18,
                    'id' => 'mantenimiento_historial_equipo',
                    'categoria' => 'mantenimiento',
                    'titulo' => 'Historial por equipo',
                    'descripcion' => 'Relación cronológica de todas las intervenciones técnicas realizadas a cada equipo registrado.',
                    'icono' => '📑',
                    'columnas' => ['Código', 'Equipo', 'Fecha', 'Tipo servicio', 'Estado'],
                ],
            ],

            'rentas' => [
                'rentas_equipos' => [
                    'num' => 19,
                    'id' => 'rentas_equipos',
                    'categoria' => 'rentas',
                    'titulo' => 'Equipos en renta',
                    'descripcion' => 'Catálogo completo de equipos en esquema de arrendamiento con proveedor y vigencia contractual.',
                    'icono' => '💳',
                    'columnas' => ['Código', 'Equipo', 'Proveedor', 'Fecha inicio', 'Fecha fin'],
                ],
                'rentas_proximas_vencer' => [
                    'num' => 20,
                    'id' => 'rentas_proximas_vencer',
                    'categoria' => 'rentas',
                    'titulo' => 'Próximas a vencer',
                    'descripcion' => 'Arrendamientos con fecha de término en los próximos 90 días naturales para planeación de renovación.',
                    'icono' => '🔔',
                    'columnas' => ['Código', 'Equipo', 'Proveedor', 'Fecha fin', 'Días restantes'],
                ],
                'rentas_vencidas' => [
                    'num' => 21,
                    'id' => 'rentas_vencidas',
                    'categoria' => 'rentas',
                    'titulo' => 'Vencidas',
                    'descripcion' => 'Contratos de arrendamiento cuya fecha límite ha expirado y requieren devolución o regularización.',
                    'icono' => '🚨',
                    'columnas' => ['Código', 'Equipo', 'Proveedor', 'Fecha fin', 'Días vencida'],
                ],
                'rentas_gasto_mensual' => [
                    'num' => 22,
                    'id' => 'rentas_gasto_mensual',
                    'categoria' => 'rentas',
                    'titulo' => 'Gasto mensual',
                    'descripcion' => 'Concentrado mensual del parque vehicular de cómputo arrendado y estimación de gasto.',
                    'icono' => '💰',
                    'columnas' => ['Mes', 'Cantidad equipos', 'Costo estimado'],
                ],
                'rentas_por_proveedor' => [
                    'num' => 23,
                    'id' => 'rentas_por_proveedor',
                    'categoria' => 'rentas',
                    'titulo' => 'Por proveedor',
                    'descripcion' => 'Distribución de equipos en renta agrupados por empresa proveedora con fechas extremas.',
                    'icono' => '🤝',
                    'columnas' => ['Proveedor', 'Equipos', 'Fecha más antigua', 'Fecha más reciente'],
                ],
            ],

            'historico' => [
                'historico_cambios_area' => [
                    'num' => 24,
                    'id' => 'historico_cambios_area',
                    'categoria' => 'historico',
                    'titulo' => 'Cambios de área',
                    'descripcion' => 'Bitácora de movimientos y traslados de equipo entre diferentes áreas y departamentos.',
                    'icono' => '🔄',
                    'columnas' => ['Fecha', 'Equipo', 'Origen', 'Destino', 'Usuario'],
                ],
                'historico_bajas' => [
                    'num' => 25,
                    'id' => 'historico_bajas',
                    'categoria' => 'historico',
                    'titulo' => 'Bajas',
                    'descripcion' => 'Relación de equipos dados de baja institucionalmente con su motivo y usuario dictaminador.',
                    'icono' => '🗑️',
                    'columnas' => ['Código', 'Equipo', 'Fecha baja', 'Motivo', 'Usuario'],
                ],
                'historico_entregas' => [
                    'num' => 26,
                    'id' => 'historico_entregas',
                    'categoria' => 'historico',
                    'titulo' => 'Entregas',
                    'descripcion' => 'Historial de solicitudes de asignación y entrega autorizadas formalmente a personal receptor.',
                    'icono' => '🤝',
                    'columnas' => ['Fecha', 'Equipo', 'Receptor', 'Depto'],
                ],
                'historico_actividad_usuario' => [
                    'num' => 27,
                    'id' => 'historico_actividad_usuario',
                    'categoria' => 'historico',
                    'titulo' => 'Actividad por usuario',
                    'descripcion' => 'Conteo de acciones operativas, cambios y registros efectuados por cada cuenta de usuario.',
                    'icono' => '👥',
                    'columnas' => ['Usuario', 'Acciones', 'Última actividad'],
                ],
                'historico_general' => [
                    'num' => 28,
                    'id' => 'historico_general',
                    'categoria' => 'historico',
                    'titulo' => 'Historial general',
                    'descripcion' => 'Línea de tiempo cronológica completa de todas las transacciones de estado y ubicación del inventario.',
                    'icono' => '📜',
                    'columnas' => ['Fecha', 'Equipo', 'Estado anterior', 'Estado nuevo', 'Motivo', 'Usuario'],
                ],
            ],
        ];
    }

    /**
     * Retorna las 5 sub-pestañas disponibles para la sección de Nuevos Reportes.
     */
    public static function getSubpestanas(): array
    {
        return [
            'inventario' => '🏢 Inventario',
            'estadisticas' => '📊 Estadísticas',
            'mantenimiento' => '🔧 Mantenimiento',
            'rentas' => '💰 Rentas',
            'historico' => '📉 Histórico',
        ];
    }

    /**
     * Busca los metadatos de un reporte específico por su ID.
     */
    public static function findReporte(string $tipo): ?array
    {
        foreach (self::getCatalogo() as $subpestana => $reportes) {
            if (isset($reportes[$tipo])) {
                return $reportes[$tipo];
            }
        }

        return null;
    }

    /**
     * Ejecuta el query correspondiente al tipo de reporte y retorna su estructura con headers y rows.
     */
    public static function getReportData(string $tipo, array $filtros = []): array
    {
        $meta = self::findReporte($tipo) ?? [
            'titulo' => 'Reporte',
            'columnas' => [],
        ];

        $headers = $meta['columnas'];
        $rows = [];

        switch ($tipo) {
            // ==========================================
            // SUB-PESTAÑA 1: INVENTARIO (6 reportes)
            // ==========================================
            case 'inventario_general':
                $q = Inventario::with(['material.marca', 'material.tipo', 'departamento', 'area', 'salon'])->orderBy('codigo');
                self::aplicarFiltrosInventario($q, $filtros);

                foreach ($q->get() as $inv) {
                    $rows[] = [
                        $inv->codigo ?: ('#' . $inv->id_inventario),
                        $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                        $inv->material?->tipo?->nombre ?? $inv->tipo_equipo ?? 'General',
                        $inv->material?->marca?->nombre ?? ($inv->especificaciones['marca'] ?? '—'),
                        $inv->modelo ?? $inv->material?->modelo ?? '—',
                        $inv->estado ?? 'Disponible',
                        $inv->departamento?->nombre ?? $inv->area?->departamento?->nombre ?? 'Sin depto',
                        $inv->area?->nombre ?? 'Sin área',
                        $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? '—')),
                        $inv->num_serie ?: 'S/N',
                    ];
                }
                break;

            case 'inventario_por_departamento':
                $deptos = Departamento::with(['areas'])->orderBy('nombre')->get();
                $deptoFiltro = ! empty($filtros['departamento_id']) ? (int) $filtros['departamento_id'] : null;

                if ($deptoFiltro) {
                    $deptos = $deptos->where('id_departamento', $deptoFiltro);
                }

                foreach ($deptos as $depto) {
                    $baseQ = Inventario::where(function ($sub) use ($depto) {
                        $sub->where('departamento_id', $depto->id_departamento)
                            ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $depto->id_departamento));
                    });
                    self::aplicarFiltroFechas($baseQ, $filtros);

                    $total = (clone $baseQ)->count();
                    $rentados = (clone $baseQ)->where('tipo_propiedad', 'Rentado')->count();
                    $propios = $total - $rentados;

                    $rows[] = [
                        $depto->nombre,
                        $total,
                        $propios,
                        $rentados,
                    ];
                }

                // Sin departamento
                if (! $deptoFiltro) {
                    $sinDeptoQ = Inventario::whereNull('departamento_id')->whereNull('area_id');
                    self::aplicarFiltroFechas($sinDeptoQ, $filtros);
                    $totalSin = (clone $sinDeptoQ)->count();
                    if ($totalSin > 0) {
                        $rentadosSin = (clone $sinDeptoQ)->where('tipo_propiedad', 'Rentado')->count();
                        $rows[] = ['Sin departamento asignado', $totalSin, $totalSin - $rentadosSin, $rentadosSin];
                    }
                }
                break;

            case 'inventario_por_area':
                $areasQ = Area::with('departamento')->orderBy('nombre');
                if (! empty($filtros['departamento_id'])) {
                    $areasQ->where('id_departamento', $filtros['departamento_id']);
                }

                foreach ($areasQ->get() as $area) {
                    $baseQ = Inventario::where('area_id', $area->id_area);
                    self::aplicarFiltroFechas($baseQ, $filtros);
                    $total = $baseQ->count();

                    $rows[] = [
                        $area->nombre,
                        $area->departamento?->nombre ?? 'Sin depto',
                        $total,
                    ];
                }
                break;

            case 'inventario_por_tipo':
                $tipos = TipoMaterial::orderBy('nombre')->get();
                $deptoFiltro = ! empty($filtros['departamento_id']) ? (int) $filtros['departamento_id'] : null;

                foreach ($tipos as $tipoObj) {
                    $baseQ = Inventario::whereHas('material', fn ($mq) => $mq->where('id_tipodematerial', $tipoObj->id_tipodematerial));
                    if ($deptoFiltro) {
                        $baseQ->where(function ($sub) use ($deptoFiltro) {
                            $sub->where('departamento_id', $deptoFiltro)
                                ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoFiltro));
                        });
                    }
                    self::aplicarFiltroFechas($baseQ, $filtros);

                    $total = (clone $baseQ)->count();
                    if ($total > 0) {
                        $rentados = (clone $baseQ)->where('tipo_propiedad', 'Rentado')->count();
                        $rows[] = [
                            $tipoObj->nombre,
                            $total,
                            $total - $rentados,
                            $rentados,
                        ];
                    }
                }
                break;

            case 'inventario_por_estado':
                $baseQ = Inventario::query();
                self::aplicarFiltrosInventario($baseQ, $filtros);
                $totalGeneral = (clone $baseQ)->count();

                $estados = ['Disponible', 'Asignado', 'En mantenimiento', 'En Mantenimiento', 'Cambio de área', 'Solicitud de baja', 'Dado de baja'];
                $estadosDb = (clone $baseQ)->selectRaw('COALESCE(estado, "Disponible") as est, count(*) as cant')
                    ->groupBy('est')
                    ->pluck('cant', 'est')
                    ->toArray();

                foreach ($estadosDb as $estadoNom => $cant) {
                    $porcentaje = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
                    $rows[] = [$estadoNom, $cant, $porcentaje];
                }
                break;

            case 'inventario_por_aula':
                $baseQ = Inventario::with(['area.departamento', 'salon']);
                self::aplicarFiltrosInventario($baseQ, $filtros);

                $equipos = $baseQ->get();
                $agrupados = $equipos->groupBy(function ($inv) {
                    $aula = $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? 'Sin aula'));
                    $areaId = $inv->area_id ?? 'sin_area';
                    return "{$aula}|{$areaId}";
                });

                foreach ($agrupados as $clave => $grupo) {
                    $primer = $grupo->first();
                    $aulaNom = $primer->aula_efectiva ?? ($primer->aula ?: ($primer->salon?->nombre ?? 'Sin aula'));
                    $areaNom = $primer->area?->nombre ?? 'Sin área';
                    $deptoNom = $primer->departamento?->nombre ?? $primer->area?->departamento?->nombre ?? 'Sin depto';

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
                $q = Inventario::with(['material', 'departamento', 'area']);
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
                $baseQ = Inventario::query();
                self::aplicarFiltroFechas($baseQ, $filtros);
                $totalGeneral = (clone $baseQ)->count();

                $deptos = Departamento::all();
                $ranking = [];
                foreach ($deptos as $depto) {
                    $cant = (clone $baseQ)->where(function ($sub) use ($depto) {
                        $sub->where('departamento_id', $depto->id_departamento)
                            ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $depto->id_departamento));
                    })->count();

                    if ($cant > 0) {
                        $pct = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
                        $ranking[] = [$depto->nombre, $cant, $pct];
                    }
                }

                usort($ranking, fn ($a, $b) => $b[1] <=> $a[1]);
                $rows = $ranking;
                break;

            case 'estadisticas_mas_movidos':
                $historial = HistorialEstado::where(function ($q) {
                    $q->where('motivo_cambio', 'like', '%área%')
                        ->orWhere('motivo_cambio', 'like', '%ubicación%')
                        ->orWhere('motivo_cambio', 'like', '%Cambio de área%')
                        ->orWhere('motivo_cambio', 'like', '%Cambio%');
                })->get();

                $conteoPorEquipo = $historial->groupBy('id_inventario');
                $items = [];

                foreach ($conteoPorEquipo as $idInv => $cambios) {
                    $inv = Inventario::with('material')->find($idInv);
                    if ($inv) {
                        $ultimo = $cambios->sortByDesc('fecha_cambio')->first();
                        $fechaUltimo = $ultimo?->fecha_cambio ? Carbon::parse($ultimo->fecha_cambio)->format('d/m/Y') : '—';
                        $items[] = [
                            $inv->codigo ?: ('#' . $inv->id_inventario),
                            $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                            $cambios->count(),
                            $fechaUltimo,
                        ];
                    }
                }

                usort($items, fn ($a, $b) => $b[2] <=> $a[2]);
                $rows = array_slice($items, 0, 50);
                break;

            case 'estadisticas_propio_vs_rentado':
                $baseQ = Inventario::query();
                self::aplicarFiltrosInventario($baseQ, $filtros);
                $totalGeneral = (clone $baseQ)->count();

                $rentados = (clone $baseQ)->where('tipo_propiedad', 'Rentado')->count();
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
                $totalEquipos = (clone $baseQ)->count();
                $rentados = (clone $baseQ)->where('tipo_propiedad', 'Rentado')->count();
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
                $mants = Mantenimiento::with(['inventario.departamento', 'inventario.area.departamento'])->get();
                if (! empty($filtros['fecha_desde'])) {
                    $mants = $mants->where('fecha_solicitud', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $mants = $mants->where('fecha_solicitud', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }

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
                $mants = Mantenimiento::whereNotNull('fecha_solicitud')->get();
                if (! empty($filtros['fecha_desde'])) {
                    $mants = $mants->where('fecha_solicitud', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
                }
                if (! empty($filtros['fecha_hasta'])) {
                    $mants = $mants->where('fecha_solicitud', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
                }

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
                $actividades = HistorialEstado::with('usuario')->get()->groupBy('id_usuario');
                $datos = [];

                foreach ($actividades as $idUser => $grupo) {
                    $user = $grupo->first()?->usuario;
                    $nombre = $user?->name ?? ($idUser ? "Usuario #{$idUser}" : 'Sistema');
                    $cant = $grupo->count();
                    $ultimo = $grupo->sortByDesc('fecha_cambio')->first();
                    $fechaUlt = $ultimo?->fecha_cambio ? Carbon::parse($ultimo->fecha_cambio)->format('d/m/Y H:i') : '—';

                    $datos[] = [$nombre, $cant, $fechaUlt];
                }

                usort($datos, fn ($a, $b) => $b[1] <=> $a[1]);
                $rows = $datos;
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

        return [
            'id' => $tipo,
            'titulo' => $meta['titulo'],
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
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
        if (! empty($filtros['departamento_id'])) {
            $deptoId = (int) $filtros['departamento_id'];
            $query->where(function ($q) use ($deptoId) {
                $q->where('departamento_id', $deptoId)
                    ->orWhereHas('area', fn ($aq) => $aq->where('id_departamento', $deptoId));
            });
        }

        self::aplicarFiltroFechas($query, $filtros);
    }

    /**
     * Aplica filtro de fechas por rango.
     */
    protected static function aplicarFiltroFechas($query, array $filtros): void
    {
        if (! empty($filtros['fecha_desde'])) {
            $desde = Carbon::parse($filtros['fecha_desde'])->startOfDay();
            $query->where(function ($q) use ($desde) {
                $q->where('fecha_registro', '>=', $desde)
                    ->orWhere('created_at', '>=', $desde);
            });
        }

        if (! empty($filtros['fecha_hasta'])) {
            $hasta = Carbon::parse($filtros['fecha_hasta'])->endOfDay();
            $query->where(function ($q) use ($hasta) {
                $q->where('fecha_registro', '<=', $hasta)
                    ->orWhere('created_at', '<=', $hasta);
            });
        }
    }
}
