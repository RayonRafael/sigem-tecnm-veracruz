<?php

namespace App\Support\Reports;

use App\Models\Mantenimiento;
use App\Support\SigemReportExporter;
use Carbon\Carbon;

class MantenimientoReportsBuilder
{
    public function getDatos(string $reporteId, array $filtros = []): array
    {
        return match ($reporteId) {
            'mantenimiento_depto_pide_mas' => $this->mantenimientoDeptoPideMas($filtros),
            'mantenimiento_tipo_mas_frecuente' => $this->mantenimientoTipoMasFrecuente(),
            'mantenimiento_por_mes' => $this->mantenimientoPorMes($filtros),
            'mantenimiento_en_curso' => $this->mantenimientoEnCurso(),
            'mantenimiento_tiempo_promedio' => $this->mantenimientoTiempoPromedio(),
            'mantenimiento_historial_equipo' => $this->mantenimientoHistorialEquipo($filtros),
            default => [],
        };
    }

    protected function mantenimientoDeptoPideMas(array $filtros): array
    {
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

        return $datos;
    }

    protected function mantenimientoTipoMasFrecuente(): array
    {
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

        return $datos;
    }

    protected function mantenimientoPorMes(array $filtros): array
    {
        $rows = [];
        $mantQ = Mantenimiento::query()->select(['id_mantenimiento', 'fecha_solicitud'])->whereNotNull('fecha_solicitud');
        if (! empty($filtros['fecha_desde'])) {
            $mantQ->where('fecha_solicitud', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
        }
        if (! empty($filtros['fecha_hasta'])) {
            $mantQ->where('fecha_solicitud', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }
        $mants = $mantQ->get();

        $agrupados = $mants->groupBy(fn ($m) => Carbon::parse($m->fecha_solicitud)->format('Y-m'))->sortKeysDesc();

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

        return $rows;
    }

    protected function mantenimientoEnCurso(): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $mants = Mantenimiento::with(['inventario.material', 'inventario.departamento', 'inventario.area.departamento'])
            ->whereIn('estado', ['Pendiente', 'En proceso', 'En Mantenimiento'])
            ->get();

        foreach ($mants as $m) {
            $inv = $m->inventario;
            $horaMant = $m->created_at ? $m->created_at->format('H:i') : SigemReportExporter::resolverHoraRegistro($inv);
            $rows[] = [
                $inv?->codigo ?: ('#' . $inv?->id_inventario),
                $inv?->material?->nombre ?? $inv?->subtipo ?? 'Equipo',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                $m->fecha_solicitud ? Carbon::parse($m->fecha_solicitud)->format('d/m/Y') : '—',
                $horaMant,
                SigemReportExporter::resolverTipoPropiedad($inv),
            ];
        }

        return $rows;
    }

    protected function mantenimientoTiempoPromedio(): array
    {
        $rows = [];
        $mants = Mantenimiento::with(['inventario.material.tipo'])
            ->where('estado', 'Completado')
            ->whereNotNull('fecha_solicitud')
            ->whereNotNull('fecha_fin')
            ->get();

        $agrupados = $mants->groupBy(fn ($m) => $m->inventario?->material?->tipo?->nombre ?? $m->tipo_mantenimiento ?? 'General');

        foreach ($agrupados as $tipoNom => $grupo) {
            $diasArr = [];
            foreach ($grupo as $m) {
                $ini = Carbon::parse($m->fecha_solicitud);
                $fin = Carbon::parse($m->fecha_fin);
                $diasArr[] = max(0, $ini->diffInDays($fin));
            }

            if (! empty($diasArr)) {
                $promedio = round(array_sum($diasArr) / count($diasArr), 1);
                $rows[] = [$tipoNom, $promedio . ' días', min($diasArr) . ' días', max($diasArr) . ' días'];
            }
        }

        return $rows;
    }

    protected function mantenimientoHistorialEquipo(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $mants = Mantenimiento::with(['inventario.material', 'inventario.area'])->orderBy('fecha_solicitud', 'desc');
        if (! empty($filtros['fecha_desde'])) {
            $mants->where('fecha_solicitud', '>=', $filtros['fecha_desde']);
        }
        if (! empty($filtros['fecha_hasta'])) {
            $mants->where('fecha_solicitud', '<=', $filtros['fecha_hasta']);
        }

        foreach ($mants->limit(200)->get() as $m) {
            $inv = $m->inventario;
            $horaMant = $m->created_at ? $m->created_at->format('H:i') : SigemReportExporter::resolverHoraRegistro($inv);
            $rows[] = [
                $inv?->codigo ?: ('#' . $inv?->id_inventario),
                $inv?->material?->nombre ?? $inv?->subtipo ?? 'Equipo',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                $m->fecha_solicitud ? Carbon::parse($m->fecha_solicitud)->format('d/m/Y') : '—',
                $horaMant,
                SigemReportExporter::resolverTipoPropiedad($inv),
                $m->tipo_servicio ?? $m->tipo_mantenimiento ?? 'Correctivo',
                $m->estado ?? 'Pendiente',
            ];
        }

        return $rows;
    }
}
