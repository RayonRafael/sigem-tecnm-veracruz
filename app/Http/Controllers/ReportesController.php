<?php

namespace App\Http\Controllers;

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
}
