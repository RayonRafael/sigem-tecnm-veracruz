<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class SigemPdfGenerator
{
    /**
     * Genera un PDF desde una vista Blade con configuración institucional.
     */
    public static function generar(string $vista, array $datos = [], ?string $nombreArchivo = null): \Barryvdh\DomPDF\PDF
    {
        $config = self::datosInstitucionales();

        $pdf = Pdf::loadView($vista, array_merge($datos, [
            'config' => $config,
        ]));

        // Configuración de página
        $pdf->setPaper(
            $config['pdf']['tamaño'] ?? 'letter',
            $config['pdf']['orientacion'] ?? 'portrait'
        );
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf;
    }

    /**
     * Genera y descarga un PDF.
     */
    public static function descargar(string $vista, array $datos, string $nombreArchivo)
    {
        $pdf = self::generar($vista, $datos, $nombreArchivo);
        return $pdf->download($nombreArchivo . '.pdf');
    }

    /**
     * Genera un PDF multipágina (1 página por cada registro).
     */
    public static function generarMultipagina(string $vista, array $registros, string $nombreArchivo)
    {
        $config = self::datosInstitucionales();

        $pdf = Pdf::loadView($vista, [
            'registros' => $registros,
            'config' => $config,
        ]);

        $pdf->setPaper(
            $config['pdf']['tamaño'] ?? 'letter',
            $config['pdf']['orientacion'] ?? 'portrait'
        );
        $pdf->setOption('isRemoteEnabled', true);

        return $pdf->download($nombreArchivo . '.pdf');
    }

    /**
     * Obtiene los datos institucionales con jefe de departamento desde BD.
     */
    public static function datosInstitucionales(): array
    {
        $config = config('sigem');

        // Buscar jefe del Centro de Cómputo desde BD
        try {
            $departamento = \App\Models\Departamento::where('nombre', 'Centro de Cómputo')->first();
            if ($departamento && $departamento->encargado) {
                $config['centro_computo']['jefe'] = $departamento->encargado;
            }
        } catch (\Throwable $e) {
            // En caso de ejecutarse sin BD inicializada
        }

        return $config;
    }
}

