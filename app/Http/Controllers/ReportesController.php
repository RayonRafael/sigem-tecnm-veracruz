<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Support\SigemPdfGenerator;
use App\Support\SigemReportExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportesController
{
    /**
     * Exporta cualquiera de los 28 nuevos reportes del sistema en formato Excel (.xlsx) o CSV.
     */
    public function export(Request $request, string $tipo): Response
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $formato = strtolower((string) $request->input('formato', 'xlsx'));
        if (! in_array($formato, ['xlsx', 'csv'], true)) {
            $formato = 'xlsx';
        }

        $filtros = [
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'departamento_id' => $request->input('departamento_id'),
        ];

        return SigemReportExporter::export($tipo, $filtros, $formato);
    }

    /**
     * Permite abrir (?preview=1) o descargar (?download=1) cualquiera de los formatos oficiales en PDF.
     */
    public function pdf(Request $request, string $tipo, int $id): Response
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $preview = $request->boolean('preview') || (int) $request->input('preview', 0) === 1;
        $disposition = $preview ? 'inline' : 'attachment';

        $filename = 'formato.pdf';
        $pdf = null;

        $esAdmin = $user->hasRole(RoleEnum::ADMIN->value)
            || $user->hasRole('admin')
            || $user->can(RoleEnum::PERM_ACCESS_ADMIN);

        switch ($tipo) {
            case 'entrega':
                $solicitud = Solicitud::findOrFail($id);
                if (! $esAdmin && (int) $solicitud->id_usuario !== (int) $user->id) {
                    abort(403, 'No tienes permiso para ver este formato.');
                }
                $pdf = SigemPdfGenerator::generarActaEntrega($solicitud);
                $filename = 'acta-entrega-' . $solicitud->id_solicitud . '.pdf';
                break;

            case 'orden_trabajo':
                $mantenimiento = Mantenimiento::findOrFail($id);
                if (! $esAdmin && (int) $mantenimiento->id_usuario_solicita !== (int) $user->id) {
                    abort(403, 'No tienes permiso para ver este formato.');
                }
                $pdf = SigemPdfGenerator::generarOrdenTrabajo($mantenimiento);
                $filename = 'orden-trabajo-' . $mantenimiento->id_mantenimiento . '.pdf';
                break;

            case 'lista_verificacion':
                $mantenimiento = Mantenimiento::findOrFail($id);
                if (! $esAdmin && (int) $mantenimiento->id_usuario_solicita !== (int) $user->id) {
                    abort(403, 'No tienes permiso para ver este formato.');
                }
                $pdf = SigemPdfGenerator::generarListaVerificacion($mantenimiento);
                $filename = 'lista-verificacion-' . $mantenimiento->id_mantenimiento . '.pdf';
                break;

            case 'baja':
                $inventario = Inventario::findOrFail($id);
                $pdf = SigemPdfGenerator::generarFormatoBaja($inventario);
                $codigo = $inventario->codigo ?: $inventario->id_inventario;
                $filename = 'tramite-baja-' . $codigo . '.pdf';
                break;

            case 'cambio_area':
                $inventario = Inventario::findOrFail($id);
                $pdf = SigemPdfGenerator::generarCambioArea($inventario);
                $codigo = $inventario->codigo ?: $inventario->id_inventario;
                $filename = 'formato-cambio-area-' . $codigo . '.pdf';
                break;

            default:
                abort(404, 'Tipo de formato no válido');
        }

        $contenido = $pdf->output();

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}

