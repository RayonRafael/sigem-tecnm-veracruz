<?php

namespace App\Support\Reports;

use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Solicitud;
use App\Models\User;
use App\Support\SigemReportExporter;
use Carbon\Carbon;

class HistoricoReportsBuilder
{
    public function getDatos(string $reporteId, array $filtros = []): array
    {
        return match ($reporteId) {
            'historico_cambios_area' => $this->historicoCambiosArea($filtros),
            'historico_bajas' => $this->historicoBajas($filtros),
            'historico_entregas' => $this->historicoEntregas($filtros),
            'historico_actividad_usuario' => $this->historicoActividadUsuario(),
            'historico_general' => $this->historicoGeneral($filtros),
            default => [],
        };
    }

    protected function historicoCambiosArea(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $historial = HistorialEstado::where(function ($q) {
            $q->where('motivo_cambio', 'like', '%área%')
                ->orWhere('motivo_cambio', 'like', '%ubicación%')
                ->orWhere('motivo_cambio', 'like', '%Cambio%');
        })->with(['inventario.material', 'inventario.area', 'usuario'])->orderBy('fecha_cambio', 'desc');

        if (! empty($filtros['fecha_desde'])) {
            $historial->where('fecha_cambio', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
        }
        if (! empty($filtros['fecha_hasta'])) {
            $historial->where('fecha_cambio', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        foreach ($historial->limit(200)->get() as $h) {
            $inv = $h->inventario;
            $motivo = $h->motivo_cambio ?? '';
            $partes = explode('→', $motivo);
            $origen = isset($partes[0]) ? trim(str_replace(['Cambio de área:', 'Cambio de ubicación:'], '', $partes[0])) : '—';
            $destino = isset($partes[1]) ? trim($partes[1]) : ($h->ubicacion_fisica ?: '—');
            $dtCambio = $h->fecha_cambio ? Carbon::parse($h->fecha_cambio) : ($h->created_at ? Carbon::parse($h->created_at) : null);

            $rows[] = [
                $dtCambio ? $dtCambio->format('d/m/Y') : '—',
                $dtCambio ? $dtCambio->format('H:i') : SigemReportExporter::resolverHoraRegistro($inv),
                $inv?->codigo ?: ($inv?->material?->nombre ?? ('#' . $h->id_inventario)),
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                SigemReportExporter::resolverTipoPropiedad($inv),
                $origen ?: 'Sin origen',
                $destino ?: 'Sin destino',
                $h->usuario?->name ?? 'Sistema',
            ];
        }

        return $rows;
    }

    protected function historicoBajas(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $bajas = Inventario::whereIn('estado', ['Dado de baja', 'Baja'])->with(['material', 'area', 'usuario'])->orderBy('fecha_baja', 'desc');
        SigemReportExporter::aplicarFiltrosInventario($bajas, $filtros);

        foreach ($bajas->get() as $inv) {
            $horaBaja = ($inv->fecha_baja && $inv->fecha_baja->format('H:i') !== '00:00')
                ? $inv->fecha_baja->format('H:i')
                : SigemReportExporter::resolverHoraRegistro($inv);
            $rows[] = [
                $inv->codigo ?: ('#' . $inv->id_inventario),
                $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo',
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                $inv->fecha_baja ? Carbon::parse($inv->fecha_baja)->format('d/m/Y') : '—',
                $horaBaja,
                SigemReportExporter::resolverTipoPropiedad($inv),
                $inv->motivo_baja ?? ($inv->especificaciones['motivo_baja'] ?? 'Dictamen técnico'),
                $inv->usuario?->name ?? 'Administrador',
            ];
        }

        return $rows;
    }

    protected function historicoEntregas(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $solicitudes = Solicitud::with(['detalles.inventario.material', 'detalles.inventario.area', 'receptor.area.departamento', 'usuario'])
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
            $recNom = $sol->receptor ? trim("{$sol->receptor->nombre} {$sol->receptor->apellido_paterno}") : '—';
            $deptoRec = $sol->receptor?->area?->departamento?->nombre ?? $sol->receptor?->departamento ?? '—';
            $areaRec = $sol->receptor?->area?->nombre ?? 'Sin área';
            $fechaSol = $sol->fecha_solicitud ? Carbon::parse($sol->fecha_solicitud)->format('d/m/Y') : '—';
            $horaSol = $sol->created_at ? $sol->created_at->format('H:i') : '—';

            if ($sol->detalles->isNotEmpty()) {
                foreach ($sol->detalles as $det) {
                    $inv = $det->inventario;
                    $cod = $inv?->codigo ?: ($inv?->material?->nombre ?? ('Sol #' . $sol->id_solicitud));
                    $areaNom = $inv?->area?->nombre ?? $areaRec;
                    $deptoNom = $inv ? SigemReportExporter::resolverDeptoNombre($inv, $deptosMap) : $deptoRec;
                    $rows[] = [
                        $fechaSol,
                        $horaSol,
                        $cod,
                        $areaNom,
                        $deptoNom,
                        $recNom ?: SigemReportExporter::resolverReceptorNombre($inv),
                        SigemReportExporter::resolverTipoPropiedad($inv),
                    ];
                }
            } else {
                $rows[] = [$fechaSol, $horaSol, 'Solicitud #' . $sol->id_solicitud, $areaRec, $deptoRec, $recNom, 'Propio'];
            }
        }

        return $rows;
    }

    protected function historicoActividadUsuario(): array
    {
        $rows = [];
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

        return $rows;
    }

    protected function historicoGeneral(array $filtros): array
    {
        $rows = [];
        $deptosMap = SigemReportExporter::getDepartamentosMap();
        $historial = HistorialEstado::with(['inventario.material', 'inventario.area', 'usuario'])->orderBy('fecha_cambio', 'desc');
        if (! empty($filtros['fecha_desde'])) {
            $historial->where('fecha_cambio', '>=', Carbon::parse($filtros['fecha_desde'])->startOfDay());
        }
        if (! empty($filtros['fecha_hasta'])) {
            $historial->where('fecha_cambio', '<=', Carbon::parse($filtros['fecha_hasta'])->endOfDay());
        }

        foreach ($historial->limit(500)->get() as $h) {
            $inv = $h->inventario;
            $eq = $inv?->codigo ?: ($inv?->material?->nombre ?? ('Equipo #' . $h->id_inventario));
            $dtCambio = $h->fecha_cambio ? Carbon::parse($h->fecha_cambio) : ($h->created_at ? Carbon::parse($h->created_at) : null);
            $rows[] = [
                $dtCambio ? $dtCambio->format('d/m/Y') : '—',
                $dtCambio ? $dtCambio->format('H:i') : SigemReportExporter::resolverHoraRegistro($inv),
                $eq,
                SigemReportExporter::resolverAreaNombre($inv),
                SigemReportExporter::resolverDeptoNombre($inv, $deptosMap),
                SigemReportExporter::resolverReceptorNombre($inv),
                SigemReportExporter::resolverTipoPropiedad($inv),
                $h->estado_anterior ?? '—',
                $h->estado_nuevo ?? '—',
                $h->motivo_cambio ?? '—',
                $h->usuario?->name ?? 'Sistema',
            ];
        }

        return $rows;
    }
}
