<?php

namespace App\Support\Reports;

use App\Models\Inventario;
use App\Models\Proveedor;
use App\Support\SigemReportExporter;
use Carbon\Carbon;

class RentasReportsBuilder
{
    public function getDatos(string $reporteId, array $filtros = []): array
    {
        return match ($reporteId) {
            'rentas_equipos' => $this->rentasEquipos($filtros),
            'rentas_proximas_vencer' => $this->rentasProximasVencer(),
            'rentas_vencidas' => $this->rentasVencidas(),
            'rentas_gasto_mensual' => $this->rentasGastoMensual(),
            'rentas_por_proveedor' => $this->rentasPorProveedor(),
            'rent_seguimiento' => $this->rentSeguimiento($filtros),
            default => [],
        };
    }

    protected function rentasEquipos(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $q = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor', 'area']);
        SigemReportExporter::aplicarFiltrosInventario($q, $filtros);

        foreach ($q->get() as $inv) {
            $ini = $inv->fecha_inicio_renta?->format('d/m/Y')
                ?? ($inv->especificaciones['fecha_inicio_renta'] ?? SigemReportExporter::resolverFechaRegistro($inv));
            $fin = $inv->fecha_fin_renta?->format('d/m/Y')
                ?? $inv->renta_fecha_fin?->format('d/m/Y')
                ?? ($inv->especificaciones['fecha_fin_renta'] ?? '—');

            $rows[] = [
                $inv->codigo ?: ('#' . $inv->id_inventario),
                $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                $ini,
                SigemReportExporter::resolverHoraRegistro($inv),
                $fin,
                SigemReportExporter::resolverTipoPropiedad($inv),
            ];
        }

        return $rows;
    }

    protected function rentasProximasVencer(): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $hoy = Carbon::now()->startOfDay();
        $limite = Carbon::now()->addDays(90)->endOfDay();

        $rentados = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor', 'area'])->get();
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
                    SigemReportExporter::resolverAreaNombre($inv),
                    SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                    SigemReportExporter::resolverReceptorNombre($inv),
                    SigemReportExporter::resolverFechaRegistro($inv),
                    SigemReportExporter::resolverHoraRegistro($inv),
                    SigemReportExporter::resolverTipoPropiedad($inv),
                ];
            }
        }

        return $rows;
    }

    protected function rentasVencidas(): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $hoy = Carbon::now()->startOfDay();
        $rentados = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor', 'area'])->get();

        foreach ($rentados as $inv) {
            $fechaFin = $inv->fecha_fin_renta ?? (! empty($inv->especificaciones['fecha_fin_renta']) ? Carbon::parse($inv->especificaciones['fecha_fin_renta']) : null);
            if ($fechaFin && $fechaFin->lt($hoy)) {
                $diasVencida = (int) $fechaFin->diffInDays($hoy);
                $rows[] = [
                    $inv->codigo ?: ('#' . $inv->id_inventario),
                    $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                    $inv->proveedor?->nombre_empresa ?? 'Sin proveedor',
                    SigemReportExporter::resolverAreaNombre($inv),
                    SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                    SigemReportExporter::resolverReceptorNombre($inv),
                    SigemReportExporter::resolverFechaRegistro($inv),
                    SigemReportExporter::resolverHoraRegistro($inv),
                    $fechaFin->format('d/m/Y'),
                    $diasVencida . ' días vencida',
                    SigemReportExporter::resolverTipoPropiedad($inv),
                ];
            }
        }

        return $rows;
    }

    protected function rentasGastoMensual(): array
    {
        $rows = [];
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

        return $rows;
    }

    protected function rentasPorProveedor(): array
    {
        $rows = [];
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

        return $rows;
    }

    protected function rentSeguimiento(array $filtros): array
    {
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $q = Inventario::where('tipo_propiedad', 'Rentado')->with(['material', 'proveedor', 'area']);
        SigemReportExporter::aplicarFiltrosInventario($q, $filtros);

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
                    SigemReportExporter::resolverAreaNombre($inv),
                    SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                    SigemReportExporter::resolverReceptorNombre($inv),
                    SigemReportExporter::resolverHoraRegistro($inv),
                    SigemReportExporter::resolverTipoPropiedad($inv),
                ],
            ];
        }

        usort($items, function (array $a, array $b): int {
            if ($a['prioridad'] !== $b['prioridad']) {
                return $a['prioridad'] <=> $b['prioridad'];
            }

            return $a['dias_sort'] <=> $b['dias_sort'];
        });

        return array_column($items, 'row');
    }
}
