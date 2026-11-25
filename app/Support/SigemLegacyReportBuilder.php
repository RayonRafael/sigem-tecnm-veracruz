<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Genera los 6 reportes principales (legacy) del módulo de Reportes SIGEM.
 * Extraído de ReportesPage para reducir complejidad y optimizar consultas.
 */
class SigemLegacyReportBuilder
{
    public static function generar(string $reportId, string $formato = 'pantalla'): ?array
    {
        $fecha = Carbon::now()->format('d/m/Y H:i');

        return match ($reportId) {
            'inventario_categoria' => self::inventarioCategoria($fecha, $formato),
            'equipos_area' => self::equiposArea($fecha, $formato),
            'equipos_mantenimiento' => self::equiposMantenimiento($fecha, $formato),
            'equipos_baja_danados' => self::equiposBajaDanados($fecha, $formato),
            'solicitudes_tipo_estado' => self::solicitudes($fecha, $formato),
            'movimientos_recientes' => self::movimientos($fecha, $formato),
            default => null,
        };
    }

    protected static function inventarioCategoria(string $fecha, string $formato): array
    {
        $tipos = TipoMaterial::orderBy('nombre')->get();

        // Agregación SQL directa: evita hidratar todos los modelos Inventario en PHP
        $stats = Inventario::query()
            ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
            ->selectRaw("
                material.id_tipodematerial as tipo_id,
                COUNT(*) as total,
                SUM(CASE WHEN inventario.tipo_propiedad = 'Propio' THEN 1 ELSE 0 END) as propios,
                SUM(CASE WHEN inventario.tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados,
                SUM(CASE WHEN inventario.estado = 'Disponible' THEN 1 ELSE 0 END) as disponibles,
                SUM(CASE WHEN inventario.estado = 'Asignado' THEN 1 ELSE 0 END) as asignados,
                SUM(CASE WHEN inventario.estado = 'En Mantenimiento' THEN 1 ELSE 0 END) as mantenimiento,
                SUM(CASE WHEN inventario.estado = 'Reasignado' THEN 1 ELSE 0 END) as reasignados,
                SUM(CASE WHEN inventario.estado IN ('Dañado', 'Baja') THEN 1 ELSE 0 END) as danados_baja,
                SUM(CASE WHEN inventario.estado IN ('Dañado', 'Baja') OR inventario.estado_registro = 'Rechazado' THEN 1 ELSE 0 END) as solicitud_baja
            ")
            ->groupBy('material.id_tipodematerial')
            ->get()
            ->keyBy('tipo_id');

        $rows = [];

        if ($formato === 'csv') {
            foreach ($tipos as $tipo) {
                $st = $stats->get($tipo->id_tipodematerial);
                $total = (int) ($st->total ?? 0);
                if ($total === 0) {
                    continue;
                }

                $rows[] = [
                    'categoria' => e($tipo->nombre),
                    'total' => (string) $total,
                    'propio' => (string) (int) $st->propios,
                    'renta' => (string) (int) $st->rentados,
                    'disponibles' => (string) (int) $st->disponibles,
                    'asignados' => (string) (int) $st->asignados,
                    'mantenimiento' => (string) (int) $st->mantenimiento,
                    'danados' => (string) (int) $st->danados_baja,
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

        foreach ($tipos as $tipo) {
            $st = $stats->get($tipo->id_tipodematerial);
            $total = (int) ($st->total ?? 0);
            if ($total === 0) {
                continue;
            }

            $disp = (int) $st->disponibles;
            $mantto = (int) $st->mantenimiento;
            $asig = (int) $st->asignados;
            $cambio = (int) $st->reasignados;
            $baja = (int) $st->solicitud_baja;
            $propios = (int) $st->propios;
            $rentados = (int) $st->rentados;

            if ($formato === 'xlsx') {
                $rows[] = [
                    'categoria' => $tipo->nombre,
                    'total' => $total,
                    'propio' => $propios,
                    'rentado' => $rentados,
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
                    'propio' => self::renderBadgeConteo($propios, 'info'),
                    'rentado' => self::renderBadgeConteo($rentados, 'warn'),
                    'disponible' => self::renderBadgeConteo($disp, 'ok'),
                    'en_mantto' => self::renderBadgeConteo($mantto, 'warn'),
                    'asignado' => self::renderBadgeConteo($asig, 'info'),
                    'cambio_area' => self::renderBadgeConteo($cambio, 'neutral'),
                    'solicitud_baja' => self::renderBadgeConteo($baja, 'danger'),
                ];
            }
        }

        return [
            'id' => 'inventario_categoria',
            'emoji' => '📦',
            'titulo' => 'Inventario por categoría',
            'fecha' => $fecha,
            'headers' => ['Categoría', 'Total', 'Propio', 'Rentado', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            'rows' => $rows,
        ];
    }

    protected static function equiposArea(string $fecha, string $formato): array
    {
        $areas = Area::with('departamento')->orderBy('nombre')->get();
        $inventarios = Inventario::select(['id_inventario', 'area_id', 'ubicacion_fisica', 'especificaciones', 'tipo_propiedad', 'estado', 'estado_registro'])->get();

        $areasById = $areas->keyBy(fn ($a) => (string) $a->id_area);
        $asignadosPorArea = [];
        $sinAreaDirecta = [];

        // Pasada 1: asignación exclusiva 1-a-1 por area_id o especificaciones.area_id.
        // Evita contar un mismo equipo en múltiples áreas si varias comparten nombre
        // o si su ubicacion_fisica contiene como subcadena el nombre de otra área.
        foreach ($inventarios as $inv) {
            $areaIdStr = (string) ($inv->area_id ?? '');
            if ($areaIdStr !== '' && $areasById->has($areaIdStr)) {
                $asignadosPorArea[$areaIdStr][] = $inv;
                continue;
            }

            $espec = is_array($inv->especificaciones) ? $inv->especificaciones : json_decode($inv->especificaciones ?? '[]', true);
            $especAreaIdStr = isset($espec['area_id']) ? (string) $espec['area_id'] : '';
            if ($especAreaIdStr !== '' && $areasById->has($especAreaIdStr)) {
                $asignadosPorArea[$especAreaIdStr][] = $inv;
                continue;
            }

            // Solo equipos sin area_id válido pasan al fallback por texto de ubicacion_fisica
            if ($areaIdStr === '' && $especAreaIdStr === '') {
                $ubicacion = trim((string) ($inv->ubicacion_fisica ?? ''));
                if ($ubicacion !== '') {
                    $sinAreaDirecta[] = ['inv' => $inv, 'ubicacion' => $ubicacion];
                }
            }
        }

        // Pasada 2: fallback por ubicacion_fisica exclusivamente para equipos sin área asignada,
        // priorizando nombres más largos (más específicos) y asignando cada equipo a lo sumo a 1 área.
        if (! empty($sinAreaDirecta)) {
            $areasPorEspecificidad = $areas
                ->filter(fn ($a) => trim((string) $a->nombre) !== '')
                ->sortByDesc(fn ($a) => mb_strlen(trim((string) $a->nombre)))
                ->values();

            foreach ($sinAreaDirecta as $item) {
                foreach ($areasPorEspecificidad as $area) {
                    $areaNombre = trim((string) $area->nombre);
                    if (mb_stripos($item['ubicacion'], $areaNombre) !== false) {
                        $asignadosPorArea[(string) $area->id_area][] = $item['inv'];
                        break;
                    }
                }
            }
        }

        $rows = [];

        foreach ($areas as $area) {
            $areaIdStr = (string) $area->id_area;
            $areaInventarios = collect($asignadosPorArea[$areaIdStr] ?? []);

            $total = $areaInventarios->count();
            if ($total === 0) {
                continue;
            }

            if ($formato === 'csv') {
                $rows[] = [
                    'depto' => $area->departamento?->nombre ?? 'N/A',
                    'area' => $area->nombre,
                    'total' => (string) $total,
                    'propio' => (string) $areaInventarios->where('tipo_propiedad', 'Propio')->count(),
                    'renta' => (string) $areaInventarios->where('tipo_propiedad', 'Rentado')->count(),
                    'disponibles' => (string) $areaInventarios->where('estado', 'Disponible')->count(),
                    'asignados' => (string) $areaInventarios->where('estado', 'Asignado')->count(),
                ];
                continue;
            }

            $disp = $areaInventarios->where('estado', 'Disponible')->count();
            $mantto = $areaInventarios->where('estado', 'En Mantenimiento')->count();
            $asig = $areaInventarios->where('estado', 'Asignado')->count();
            $cambio = $areaInventarios->where('estado', 'Reasignado')->count();
            $baja = $areaInventarios->where(function ($i) {
                return in_array($i->estado, ['Dañado', 'Baja']) || $i->estado_registro === 'Rechazado';
            })->count();
            $propios = $areaInventarios->where('tipo_propiedad', 'Propio')->count();
            $rentados = $areaInventarios->where('tipo_propiedad', 'Rentado')->count();
            $deptoNombre = $area->departamento?->nombre ?? 'Sin depto';

            if ($formato === 'xlsx') {
                $rows[] = [
                    'area' => $area->nombre,
                    'departamento' => $deptoNombre,
                    'total' => $total,
                    'propio' => $propios,
                    'rentado' => $rentados,
                    'disponible' => $disp,
                    'en_mantto' => $mantto,
                    'asignado' => $asig,
                    'cambio_area' => $cambio,
                    'solicitud_baja' => $baja,
                ];
            } else {
                $rows[] = [
                    'area' => '<strong>' . e($area->nombre) . '</strong>',
                    'departamento' => e($deptoNombre),
                    'total' => '<span class="sigem-td-folio">' . $total . '</span>',
                    'propio' => self::renderBadgeConteo($propios, 'info'),
                    'rentado' => self::renderBadgeConteo($rentados, 'warn'),
                    'disponible' => self::renderBadgeConteo($disp, 'ok'),
                    'en_mantto' => self::renderBadgeConteo($mantto, 'warn'),
                    'asignado' => self::renderBadgeConteo($asig, 'info'),
                    'cambio_area' => self::renderBadgeConteo($cambio, 'neutral'),
                    'solicitud_baja' => self::renderBadgeConteo($baja, 'danger'),
                ];
            }
        }

        return [
            'id' => 'equipos_area',
            'emoji' => '🏢',
            'titulo' => 'Equipos por área',
            'fecha' => $fecha,
            'headers' => $formato === 'csv'
                ? ['Departamento', 'Área', 'Total Activos', 'Propio', 'Renta', 'Disponibles', 'Asignados']
                : ['Área', 'Departamento', 'Total', 'Propio', 'Rentado', 'Disponible', 'En mantto', 'Asignado', 'Cambio área', 'Solicitud baja'],
            'rows' => $rows,
        ];
    }

    protected static function equiposMantenimiento(string $fecha, string $formato): array
    {
        $mantenimientos = Mantenimiento::with(['inventario.material.tipo', 'inventario.area.departamento', 'inventario.departamento', 'inventario.salon'])
            ->latest('fecha_solicitud')
            ->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($mantenimientos as $m) {
                $inv = $m->inventario;
                $esPropio = ($inv?->tipo_propiedad ?? 'Propio') === 'Propio';
                $area = $inv?->area?->nombre ?? 'Sin área';
                $depto = $inv?->departamento?->nombre ?? $inv?->area?->departamento?->nombre ?? 'Sin depto';
                $receptor = self::extraerReceptor($inv);
                $fechaStr = $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : ($inv?->fecha_registro?->format('d/m/Y') ?? '—');
                $horaStr = $m->created_at ? $m->created_at->format('H:i') : ($inv?->created_at?->format('H:i') ?? '—');

                $rows[] = [
                    'serie' => $inv?->num_serie ?? 'S/N',
                    'equipo' => $inv?->material?->nombre ?? 'N/A',
                    'area' => $area,
                    'departamento' => $depto,
                    'receptor' => $receptor,
                    'fecha' => $fechaStr,
                    'hora' => $horaStr,
                    'propiedad' => $esPropio ? 'Propio' : 'Rentado',
                    'servicio' => $m->tipo_servicio,
                    'mantenimiento' => $m->tipo_mantenimiento,
                    'tecnico' => $m->nombre_tecnico,
                    'estado' => $m->estado,
                ];
            }

            return [
                'id' => 'equipos_mantenimiento',
                'emoji' => '🔧',
                'titulo' => 'Equipos en mantenimiento',
                'fecha' => $fecha,
                'headers' => ['No. Serie', 'Equipo / Material', 'Área', 'Departamento', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad', 'Servicio', 'Tipo', 'Técnico', 'Estado'],
                'rows' => $rows,
            ];
        }

        foreach ($mantenimientos as $m) {
            $inv = $m->inventario;
            $espec = is_array($inv?->especificaciones) ? $inv->especificaciones : json_decode($inv?->especificaciones ?? '[]', true);
            $aula = $inv?->aula ?? $inv?->salon?->nombre ?? ($espec['aula'] ?? '—');
            $codigo = $inv?->codigo ?: ($inv?->num_serie ?: ('#' . $m->id_inventario));
            $equipo = $inv?->material?->nombre ?? $inv?->subtipo ?? 'N/A';
            $categoria = $inv?->material?->tipo?->nombre ?? $inv?->subtipo ?? 'General';
            $area = $inv?->area?->nombre ?? ($inv?->ubicacion_fisica ?: 'Sin área');
            $depto = $inv?->departamento?->nombre ?? $inv?->area?->departamento?->nombre ?? 'Sin depto';
            $receptor = self::extraerReceptor($inv);
            $fechaStr = $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : ($inv?->fecha_registro?->format('d/m/Y') ?? '—');
            $horaStr = $m->created_at ? $m->created_at->format('H:i') : ($inv?->created_at?->format('H:i') ?? '—');
            $tipoProp = $inv?->tipo_propiedad ?? 'Propio';

            if ($formato === 'xlsx') {
                $rows[] = [
                    'codigo' => $codigo,
                    'equipo' => $equipo,
                    'categoria' => $categoria,
                    'area' => $area,
                    'departamento' => $depto,
                    'aula' => $aula,
                    'receptor' => $receptor,
                    'fecha' => $fechaStr,
                    'hora' => $horaStr,
                    'tipo_propiedad' => $tipoProp,
                ];
            } else {
                $rows[] = [
                    'codigo' => '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                    'equipo' => '<strong>' . e($equipo) . '</strong>',
                    'categoria' => '<span class="sigem-pill neutral">' . e($categoria) . '</span>',
                    'area' => e($area),
                    'departamento' => e($depto),
                    'aula' => '<span class="sigem-td-folio">' . e($aula) . '</span>',
                    'receptor' => e($receptor),
                    'fecha' => '<span class="sigem-td-folio">' . e($fechaStr) . '</span>',
                    'hora' => '<span class="sigem-td-folio">' . e($horaStr) . '</span>',
                    'tipo_propiedad' => '<span class="sigem-pill ' . ($tipoProp === 'Rentado' ? 'warn' : 'info') . '">' . e($tipoProp) . '</span>',
                ];
            }
        }

        return [
            'id' => 'equipos_mantenimiento',
            'emoji' => '🔧',
            'titulo' => 'Equipos en mantenimiento',
            'fecha' => $fecha,
            'headers' => ['Código', 'Equipo', 'Categoría', 'Área', 'Departamento', 'Aula', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad'],
            'rows' => $rows,
        ];
    }

    protected static function equiposBajaDanados(string $fecha, string $formato): array
    {
        $inventarios = Inventario::with(['material.tipo', 'area.departamento', 'departamento'])
            ->where(function ($q) {
                $q->whereIn('estado', ['Dañado', 'Baja'])
                    ->orWhere('estado_registro', 'Rechazado');
            })
            ->latest('updated_at')
            ->get();

        $rows = [];

        if ($formato === 'csv') {
            foreach ($inventarios as $i) {
                $fFecha = $i->fecha_baja ? $i->fecha_baja->format('d/m/Y') : ($i->fecha_registro ? $i->fecha_registro->format('d/m/Y') : '—');
                $fHora = $i->created_at ? $i->created_at->format('H:i') : '—';
                $area = $i->area?->nombre ?? 'Sin área';
                $depto = $i->departamento?->nombre ?? $i->area?->departamento?->nombre ?? 'Sin depto';
                $receptor = self::extraerReceptor($i);

                $rows[] = [
                    'serie' => $i->num_serie ?? 'S/N',
                    'material' => $i->material?->nombre ?? 'N/A',
                    'area' => $area,
                    'departamento' => $depto,
                    'receptor' => $receptor,
                    'fecha' => $fFecha,
                    'hora' => $fHora,
                    'propiedad' => $i->tipo_propiedad ?? 'Propio',
                    'ubicacion' => $i->ubicacion_fisica ?? 'No especificada',
                    'estado' => $i->estado,
                    'registro' => $i->estado_registro ?? 'Pendiente',
                ];
            }

            return [
                'id' => 'equipos_baja_danados',
                'emoji' => '⚠️',
                'titulo' => 'Equipos con solicitud de baja',
                'fecha' => $fecha,
                'headers' => ['No. Serie', 'Material', 'Área', 'Departamento', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad', 'Ubicación', 'Estado', 'Registro'],
                'rows' => $rows,
            ];
        }

        foreach ($inventarios as $i) {
            $fFecha = $i->fecha_baja ? $i->fecha_baja->format('d/m/Y') : ($i->fecha_registro ? $i->fecha_registro->format('d/m/Y') : ($i->updated_at ? $i->updated_at->format('d/m/Y') : '—'));
            $fHora = $i->created_at ? $i->created_at->format('H:i') : '—';
            $codigo = $i->codigo ?: ($i->num_serie ?: ('#' . $i->id_inventario));
            $equipo = $i->material?->nombre ?? $i->subtipo ?? 'N/A';
            $categoria = $i->material?->tipo?->nombre ?? $i->subtipo ?? 'General';
            $area = $i->area?->nombre ?? ($i->ubicacion_fisica ?: 'Sin área');
            $depto = $i->departamento?->nombre ?? $i->area?->departamento?->nombre ?? 'Sin depto';
            $receptor = self::extraerReceptor($i);
            $tipoProp = $i->tipo_propiedad ?? 'Propio';

            if ($formato === 'xlsx') {
                $rows[] = [
                    'codigo' => $codigo,
                    'equipo' => $equipo,
                    'categoria' => $categoria,
                    'area' => $area,
                    'departamento' => $depto,
                    'receptor' => $receptor,
                    'fecha' => $fFecha,
                    'hora' => $fHora,
                    'tipo_propiedad' => $tipoProp,
                ];
            } else {
                $rows[] = [
                    'codigo' => '<span class="sigem-td-folio">' . e($codigo) . '</span>',
                    'equipo' => '<strong>' . e($equipo) . '</strong>',
                    'categoria' => '<span class="sigem-pill neutral">' . e($categoria) . '</span>',
                    'area' => e($area),
                    'departamento' => e($depto),
                    'receptor' => e($receptor),
                    'fecha' => '<span class="sigem-td-folio">' . e($fFecha) . '</span>',
                    'hora' => '<span class="sigem-td-folio">' . e($fHora) . '</span>',
                    'tipo_propiedad' => '<span class="sigem-pill ' . ($tipoProp === 'Rentado' ? 'warn' : 'info') . '">' . e($tipoProp) . '</span>',
                ];
            }
        }

        return [
            'id' => 'equipos_baja_danados',
            'emoji' => '⚠️',
            'titulo' => 'Equipos con solicitud de baja',
            'fecha' => $fecha,
            'headers' => ['Código', 'Equipo', 'Categoría', 'Área', 'Departamento', 'Receptor', 'Fecha', 'Hora', 'Tipo Propiedad'],
            'rows' => $rows,
        ];
    }

    protected static function solicitudes(string $fecha, string $formato): array
    {
        if ($formato === 'csv') {
            $solicitudes = Solicitud::with(['usuario', 'receptor.area.departamento'])->latest('fecha_solicitud')->get();
            $rows = [];
            foreach ($solicitudes as $s) {
                $rows[] = [
                    'folio' => 'FOLIO-' . str_pad((string) $s->id_solicitud, 5, '0', STR_PAD_LEFT),
                    'tipo' => (string) $s->tipo_movimiento,
                    'solicitante' => $s->usuario?->name ?? 'N/A',
                    'receptor' => $s->receptor?->nombre_completo ?? $s->receptor?->nombre ?? '—',
                    'area' => $s->receptor?->area?->nombre ?? 'Sin área',
                    'departamento' => $s->receptor?->area?->departamento?->nombre ?? 'Sin depto',
                    'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '—',
                    'hora' => $s->created_at ? $s->created_at->format('H:i') : '—',
                    'estado' => (string) $s->estado,
                ];
            }

            return [
                'id' => 'solicitudes_tipo_estado',
                'emoji' => '📋',
                'titulo' => 'Solicitudes de material',
                'fecha' => $fecha,
                'headers' => ['Folio', 'Tipo Movimiento', 'Solicitante', 'Receptor', 'Área', 'Departamento', 'Fecha', 'Hora', 'Estado'],
                'rows' => $rows,
            ];
        }

        // Agregación SQL directa para pantalla / Excel (sin cargar relaciones innecesarias)
        $stats = Solicitud::query()
            ->selectRaw("
                tipo_movimiento,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendiente,
                SUM(CASE WHEN estado = 'Autorizado' THEN 1 ELSE 0 END) as autorizado,
                SUM(CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END) as entregado,
                SUM(CASE WHEN estado IN ('Rechazado', 'Cancelado') THEN 1 ELSE 0 END) as rechazado
            ")
            ->groupBy('tipo_movimiento')
            ->get()
            ->keyBy('tipo_movimiento');

        $tiposMov = [
            'Salida' => ['Salida', 'Asignacion Permanente'],
            'Entrada' => ['Entrada', 'Asignacion Temporal'],
            'Traslado' => ['Traslado', 'Renta Externa'],
        ];

        $rows = [];
        foreach ($tiposMov as $tipoVisual => $matchingKeys) {
            $pend = 0;
            $aut = 0;
            $ent = 0;
            $rech = 0;
            $tot = 0;

            foreach ($matchingKeys as $key) {
                if ($st = $stats->get($key)) {
                    $pend += (int) $st->pendiente;
                    $aut += (int) $st->autorizado;
                    $ent += (int) $st->entregado;
                    $rech += (int) $st->rechazado;
                    $tot += (int) $st->total;
                }
            }

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
                    'pendiente' => self::renderBadgeConteo($pend, 'warn'),
                    'autorizado' => self::renderBadgeConteo($aut, 'ok'),
                    'entregado' => self::renderBadgeConteo($ent, 'info'),
                    'rechazado' => self::renderBadgeConteo($rech, 'danger'),
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

    protected static function movimientos(string $fecha, string $formato): array
    {
        $activities = Activity::with('causer')->latest()->limit(50)->get();
        $rows = [];

        if ($formato === 'csv') {
            foreach ($activities as $a) {
                $rows[] = [
                    'fecha' => $a->created_at ? $a->created_at->format('d/m/Y') : '—',
                    'hora' => $a->created_at ? $a->created_at->format('H:i') : '—',
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
                'headers' => ['Fecha', 'Hora', 'Usuario', 'Acción', 'Módulo', 'Descripción'],
                'rows' => $rows,
            ];
        }

        foreach ($activities as $a) {
            $fFecha = $a->created_at ? $a->created_at->format('d/m/Y') : '—';
            $fHora = $a->created_at ? $a->created_at->format('H:i') : '—';
            $usuario = $a->causer?->name ?? 'Sistema';
            $accion = ucfirst($a->event ?? $a->description ?? 'Actividad');
            $detalle = $a->description ?? 'Sin detalle adicional';

            if ($formato === 'xlsx') {
                $rows[] = [
                    'fecha' => $fFecha,
                    'hora' => $fHora,
                    'usuario' => $usuario,
                    'accion' => $accion,
                    'detalle' => $detalle,
                ];
            } else {
                $rows[] = [
                    'fecha' => '<span class="sigem-td-folio">' . e($fFecha) . '</span>',
                    'hora' => '<span class="sigem-td-folio">' . e($fHora) . '</span>',
                    'usuario' => '<strong>' . e($usuario) . '</strong>',
                    'accion' => self::renderBadgeAccion($accion),
                    'detalle' => e($detalle),
                ];
            }
        }

        return [
            'id' => 'movimientos_recientes',
            'emoji' => '📝',
            'titulo' => 'Movimientos SS',
            'fecha' => $fecha,
            'headers' => ['Fecha', 'Hora', 'Usuario', 'Acción', 'Detalle'],
            'rows' => $rows,
        ];
    }

    /**
     * Extrae el nombre del receptor desde especificaciones JSON sin disparar consultas N+1.
     */
    protected static function extraerReceptor(?Inventario $inv): string
    {
        if (! $inv) {
            return '—';
        }

        $specs = is_array($inv->especificaciones) ? $inv->especificaciones : json_decode($inv->especificaciones ?? '[]', true);
        $nombre = trim((string) ($specs['receptor_nombre'] ?? ''));

        return $nombre !== '' ? $nombre : '—';
    }

    public static function renderBadgeConteo(int $conteo, string $tipo): string
    {
        if ($conteo === 0) {
            return '<span style="color:var(--sig-muted)">0</span>';
        }

        return '<span class="sigem-pill ' . $tipo . '">' . $conteo . '</span>';
    }

    public static function renderBadgeAccion(string $evento): string
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
