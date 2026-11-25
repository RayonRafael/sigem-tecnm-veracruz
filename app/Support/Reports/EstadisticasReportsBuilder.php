<?php

namespace App\Support\Reports;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\User;
use App\Support\SigemReportExporter;
use Carbon\Carbon;

class EstadisticasReportsBuilder
{
    public function getDatos(string $reporteId, array $filtros = []): array
    {
        return match ($reporteId) {
            'estadisticas_antiguos' => $this->estadisticasAntiguos($filtros),
            'estadisticas_sin_asignar' => $this->estadisticasSinAsignar($filtros),
            'estadisticas_depto_mas_equipos' => $this->estadisticasDeptoMasEquipos($filtros),
            'estadisticas_mas_movidos' => $this->estadisticasMasMovidos(),
            'estadisticas_propio_vs_rentado' => $this->estadisticasPropioVsRentado($filtros),
            'estadisticas_resumen_general' => $this->estadisticasResumenGeneral($filtros),
            default => [],
        };
    }

    protected function estadisticasAntiguos(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $q = Inventario::with(['material', 'departamento', 'area.departamento']);
        SigemReportExporter::aplicarFiltrosInventario($q, $filtros);
        $q->orderByRaw('COALESCE(fecha_factura, fecha_registro, created_at) ASC')->limit(20);

        foreach ($q->get() as $inv) {
            $fecha = $inv->fecha_factura?->format('d/m/Y')
                ?? $inv->fecha_registro?->format('d/m/Y')
                ?? ($inv->created_at ? $inv->created_at->format('d/m/Y') : '—');

            $rows[] = [
                $inv->codigo ?: ('#' . $inv->id_inventario),
                $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                $fecha,
                SigemReportExporter::resolverHoraRegistro($inv),
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                SigemReportExporter::resolverTipoPropiedad($inv),
                $inv->estado ?? 'Disponible',
            ];
        }

        return $rows;
    }

    protected function estadisticasSinAsignar(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $q = Inventario::with(['material.tipo', 'area'])->where('estado', 'Disponible')
            ->where(function ($sub) {
                $sub->whereNull('departamento_id')->orWhereNull('area_id');
            });
        SigemReportExporter::aplicarFiltroFechas($q, $filtros);

        foreach ($q->get() as $inv) {
            $rows[] = [
                $inv->codigo ?: ('#' . $inv->id_inventario),
                $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                $inv->material?->tipo?->nombre ?? 'General',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                SigemReportExporter::resolverFechaRegistro($inv),
                SigemReportExporter::resolverHoraRegistro($inv),
                SigemReportExporter::resolverTipoPropiedad($inv),
            ];
        }

        return $rows;
    }

    protected function estadisticasDeptoMasEquipos(array $filtros): array
    {
        $deptoFiltro = ! empty($filtros['departamento_id']) ? (int) $filtros['departamento_id'] : null;

        $statsQ = Inventario::query()
            ->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
            ->selectRaw('COALESCE(inventario.departamento_id, area.id_departamento) as depto_id, COUNT(*) as cant')
            ->groupByRaw('COALESCE(inventario.departamento_id, area.id_departamento)');
        SigemReportExporter::aplicarFiltroFechas($statsQ, $filtros);
        if ($deptoFiltro) {
            $statsQ->whereRaw('COALESCE(inventario.departamento_id, area.id_departamento) = ?', [$deptoFiltro]);
        }

        $countsByDepto = $statsQ->get();
        $totalGeneral = (int) $countsByDepto->sum('cant');
        $countsMap = $countsByDepto
            ->filter(fn ($r) => $r->depto_id !== null)
            ->keyBy(fn ($r) => (int) $r->depto_id);

        $deptosMap = Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento');
        if ($deptoFiltro) {
            $deptosMap = $deptosMap->has($deptoFiltro)
                ? collect([$deptoFiltro => $deptosMap->get($deptoFiltro)])
                : collect();
        }

        // Incluir TODOS los departamentos (también los de 0 equipos) para visibilidad completa
        $ranking = [];
        foreach ($deptosMap as $idDepto => $nombreDepto) {
            $cant = (int) ($countsMap->get((int) $idDepto)->cant ?? 0);
            $pct = $totalGeneral > 0 ? round(($cant / $totalGeneral) * 100, 1) . '%' : '0%';
            $ranking[] = [$nombreDepto, $cant, $pct];
        }

        usort($ranking, function (array $a, array $b): int {
            if ($b[1] !== $a[1]) {
                return $b[1] <=> $a[1];
            }

            return strcasecmp((string) $a[0], (string) $b[0]);
        });

        return $ranking;
    }

    protected function estadisticasMasMovidos(): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
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
            ? Inventario::with(['material', 'area'])->whereIn('id_inventario', $ids)->get()->keyBy('id_inventario')
            : collect();

        foreach ($agg as $row) {
            $inv = $inventariosMap->get($row->id_inventario);
            if ($inv) {
                $dtUltimo = $row->ultimo_cambio ? Carbon::parse($row->ultimo_cambio) : null;
                $fechaUltimo = $dtUltimo ? $dtUltimo->format('d/m/Y') : '—';
                $horaUltimo = $dtUltimo ? $dtUltimo->format('H:i') : SigemReportExporter::resolverHoraRegistro($inv);
                $rows[] = [
                    $inv->codigo ?: ('#' . $inv->id_inventario),
                    $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                    (int) $row->cant,
                    $fechaUltimo,
                    $horaUltimo,
                    SigemReportExporter::resolverAreaNombre($inv),
                    SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                    SigemReportExporter::resolverReceptorNombre($inv),
                    SigemReportExporter::resolverTipoPropiedad($inv),
                ];
            }
        }

        return $rows;
    }

    protected function estadisticasPropioVsRentado(array $filtros): array
    {
        $baseQ = Inventario::query();
        SigemReportExporter::aplicarFiltrosInventario($baseQ, $filtros);
        $agg = $baseQ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
        ")->first();

        $totalGeneral = (int) ($agg->total ?? 0);
        $rentados = (int) ($agg->rentados ?? 0);
        $propios = $totalGeneral - $rentados;

        $pctPropios = $totalGeneral > 0 ? round(($propios / $totalGeneral) * 100, 1) . '%' : '0%';
        $pctRentados = $totalGeneral > 0 ? round(($rentados / $totalGeneral) * 100, 1) . '%' : '0%';

        return [
            ['Propio (Patrimonial)', $propios, $pctPropios],
            ['Rentado (Arrendamiento)', $rentados, $pctRentados],
        ];
    }

    protected function estadisticasResumenGeneral(array $filtros): array
    {
        $baseQ = Inventario::query();
        SigemReportExporter::aplicarFiltrosInventario($baseQ, $filtros);
        $agg = $baseQ->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN tipo_propiedad = 'Rentado' THEN 1 ELSE 0 END) as rentados
        ")->first();

        $totalEquipos = (int) ($agg->total ?? 0);
        $rentados = (int) ($agg->rentados ?? 0);
        $propios = $totalEquipos - $rentados;

        return [
            [$totalEquipos, Departamento::count(), Area::count(), User::count(), $propios, $rentados],
        ];
    }
}
