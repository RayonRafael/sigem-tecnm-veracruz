<?php

namespace App\Support\Reports;

use App\Models\Area;
use App\Models\Inventario;
use App\Models\TipoMaterial;
use App\Support\SigemReportExporter;

class InventarioReportsBuilder
{
    public function getDatos(string $reporteId, array $filtros = []): array
    {
        return match ($reporteId) {
            'inventario_general' => $this->inventarioGeneral($filtros),
            'inventario_por_departamento' => $this->inventarioPorDepartamento($filtros),
            'inventario_por_area' => $this->inventarioPorArea($filtros),
            'inventario_por_tipo' => $this->inventarioPorTipo($filtros),
            'inventario_por_estado' => $this->inventarioPorEstado($filtros),
            'inventario_por_aula' => $this->inventarioPorAula($filtros),
            default => [],
        };
    }

    protected function inventarioGeneral(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $q = Inventario::with(['material.marca', 'material.tipo', 'area', 'salon'])->orderBy('codigo');
        SigemReportExporter::aplicarFiltrosInventario($q, $filtros);

        foreach ($q->get() as $inv) {
            $rows[] = [
                $inv->codigo ?: ('#' . $inv->id_inventario),
                $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                $inv->material?->tipo?->nombre ?? $inv->tipo_equipo ?? 'General',
                $inv->material?->marca?->nombre ?? ($inv->especificaciones['marca'] ?? '—'),
                $inv->modelo ?? $inv->material?->modelo ?? '—',
                $inv->estado ?? 'Disponible',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? '—')),
                SigemReportExporter::resolverReceptorNombre($inv),
                SigemReportExporter::resolverFechaRegistro($inv),
                SigemReportExporter::resolverHoraRegistro($inv),
                SigemReportExporter::resolverTipoPropiedad($inv),
                $inv->num_serie ?: 'S/N',
            ];
        }

        return $rows;
    }

    protected function inventarioPorDepartamento(array $filtros): array
    {
        $rows = [];
        $deptoFiltro = ! empty($filtros['departamento_id']) ? (int) $filtros['departamento_id'] : null;
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        if ($deptoFiltro) {
            $deptosMap = isset($deptosMap[$deptoFiltro]) ? [$deptoFiltro => $deptosMap[$deptoFiltro]] : [];
        }

        $statsQ = Inventario::query()
            ->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
            ->selectRaw("
                COALESCE(inventario.departamento_id, area.id_departamento) as depto_id,
                COUNT(*) as total,
                SUM(CASE WHEN inventario.tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
            ")
            ->groupByRaw('COALESCE(inventario.departamento_id, area.id_departamento)');
        SigemReportExporter::aplicarFiltroFechas($statsQ, $filtros);
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

        return $rows;
    }

    protected function inventarioPorArea(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $areasQ = Area::orderBy('nombre');
        if (! empty($filtros['departamento_id'])) {
            $areasQ->where('id_departamento', $filtros['departamento_id']);
        }
        $areas = $areasQ->get();

        $countsQ = Inventario::query()
            ->whereNotNull('area_id')
            ->selectRaw("
                area_id,
                COUNT(*) as total,
                SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
            ")
            ->groupBy('area_id');
        SigemReportExporter::aplicarFiltroFechas($countsQ, $filtros);
        $countsByArea = $countsQ->get()->keyBy('area_id');

        foreach ($areas as $area) {
            $stat = $countsByArea->get($area->id_area);
            $total = (int) ($stat->total ?? 0);
            $rentados = (int) ($stat->rentados ?? 0);
            $rows[] = [
                $area->nombre,
                ($area->id_departamento && isset($deptosMap[$area->id_departamento])) ? $deptosMap[$area->id_departamento] : 'Sin depto',
                $total,
                $total - $rentados,
                $rentados,
            ];
        }

        return $rows;
    }

    protected function inventarioPorTipo(array $filtros): array
    {
        $rows = [];
        $tipos = TipoMaterial::orderBy('nombre')->get();

        $statsQ = Inventario::query()
            ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
            ->selectRaw("
                material.id_tipodematerial as tipo_id,
                COUNT(*) as total,
                SUM(CASE WHEN inventario.tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
            ")
            ->groupBy('material.id_tipodematerial');
        SigemReportExporter::aplicarFiltrosInventario($statsQ, $filtros);
        $statsByTipo = $statsQ->get()->keyBy('tipo_id');

        foreach ($tipos as $tipoObj) {
            $stat = $statsByTipo->get($tipoObj->id_tipodematerial);
            $total = (int) ($stat->total ?? 0);
            if ($total > 0) {
                $rentados = (int) ($stat->rentados ?? 0);
                $rows[] = [$tipoObj->nombre, $total, $total - $rentados, $rentados];
            }
        }

        return $rows;
    }

    protected function inventarioPorEstado(array $filtros): array
    {
        $rows = [];
        $baseQ = Inventario::query();
        SigemReportExporter::aplicarFiltrosInventario($baseQ, $filtros);

        $estadosDb = $baseQ->selectRaw("
                COALESCE(estado, 'Disponible') as est,
                COUNT(*) as cant,
                SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
            ")
            ->groupByRaw("COALESCE(estado, 'Disponible')")
            ->get();
        $totalGeneral = (int) $estadosDb->sum('cant');

        foreach ($estadosDb as $estRow) {
            $cant = (int) $estRow->cant;
            $rentados = (int) ($estRow->rentados ?? 0);
            $porcentaje = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
            $rows[] = [$estRow->est, $cant, $porcentaje, $cant - $rentados, $rentados];
        }

        return $rows;
    }

    protected function inventarioPorAula(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $baseQ = Inventario::with(['area', 'salon']);
        SigemReportExporter::aplicarFiltrosInventario($baseQ, $filtros);

        $equipos = $baseQ->get();
        $agrupados = $equipos->groupBy(function ($inv) {
            $aula = $inv->aula_efectiva ?? ($inv->aula ?: ($inv->salon?->nombre ?? 'Sin aula'));
            $areaId = $inv->area_id ?? 'sin_area';

            return "{$aula}|{$areaId}";
        });

        foreach ($agrupados as $grupo) {
            $primer = $grupo->first();
            $aulaNom = $primer->aula_efectiva ?? ($primer->aula ?: ($primer->salon?->nombre ?? 'Sin aula'));
            $areaNom = SigemReportExporter::resolverAreaNombre($primer);
            $deptoNom = SigemReportExporter::resolverDeptoNombre($primer, $deptosMap);
            $totalGrupo = $grupo->count();
            $rentadosGrupo = $grupo->where('tipo_propiedad', 'Rentado')->count();

            $rows[] = [
                $aulaNom,
                $areaNom,
                $deptoNom,
                $totalGrupo,
                $totalGrupo - $rentadosGrupo,
                $rentadosGrupo,
            ];
        }

        return $rows;
    }
}
