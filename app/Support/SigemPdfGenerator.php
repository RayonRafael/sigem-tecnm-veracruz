<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class SigemPdfGenerator
{
    /**
     * Instancia y configura DomPDF con los defaults institucionales de SIGEM.
     */
    protected static function crearPdf(string $vista, array $datos): \Barryvdh\DomPDF\PDF
    {
        $config = $datos['config'] ?? self::datosInstitucionales();

        $pdf = Pdf::loadView($vista, $datos);
        $pdf->setPaper(
            $config['pdf']['tamaño'] ?? 'letter',
            $config['pdf']['orientacion'] ?? 'portrait'
        );
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('enable_font_subsetting', true);

        return $pdf;
    }

    /**
     * Genera un PDF desde una vista Blade con configuración institucional.
     */
    public static function generar(string $vista, array $datos = [], ?string $nombreArchivo = null): \Barryvdh\DomPDF\PDF
    {
        $config = self::datosInstitucionales();

        return self::crearPdf($vista, array_merge($datos, [
            'config' => $config,
        ]));
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

        $pdf = self::crearPdf($vista, [
            'registros' => $registros,
            'config' => $config,
        ]);

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
            $departamento = \App\Models\Departamento::where('nombre', 'Centro de Cómputo')->first()
                ?? \App\Models\Departamento::where('nombre', 'Centro de Computo')->first();
            if ($departamento && $departamento->encargado) {
                $config['centro_computo']['jefe'] = $departamento->encargado;
            }
        } catch (\Throwable $e) {
            // En caso de ejecutarse sin BD inicializada
        }

        return $config;
    }

    /**
     * Convierte un recurso de imagen local (en public/) en Data URI Base64 para DomPDF.
     */
    public static function logoDataUri(string $rutaRelativa): ?string
    {
        $ruta = public_path($rutaRelativa);
        if (! File::exists($ruta)) {
            return null;
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(File::get($ruta));
    }

    /**
     * Construye los datos reales para el Formato 2: Solicitud-Orden de Trabajo de Mantenimiento.
     */
    public static function datosOrdenTrabajo(\App\Models\Mantenimiento $mantenimiento): array
    {
        $mantenimiento->loadMissing(['inventario.departamento', 'inventario.area.departamento', 'usuarioSolicita']);

        $config = self::datosInstitucionales();

        // Departamento del equipo (dinámico desde BD)
        $departamento = $mantenimiento->inventario?->departamento?->nombre
            ?? $mantenimiento->inventario?->area?->departamento?->nombre
            ?? $mantenimiento->inventario?->area?->nombre
            ?? '—';

        // Solicitante (usuario que solicita o encargado del departamento)
        $solicitante = '';
        if ($mantenimiento->usuarioSolicita) {
            $solicitante = trim(collect([
                $mantenimiento->usuarioSolicita->name,
                $mantenimiento->usuarioSolicita->apellido_paterno,
                $mantenimiento->usuarioSolicita->apellido_materno,
            ])->filter()->implode(' '));
        }
        if ($solicitante === '' && $mantenimiento->inventario?->departamento?->encargado) {
            $solicitante = $mantenimiento->inventario->departamento->encargado;
        }

        return [
            'config' => $config,
            'logoItv' => self::logoDataUri($config['logos']['itv'] ?? 'images/itv-logo.png'),
            'departamento' => $departamento,
            'solicitante' => $solicitante,
            'fecha' => self::formatearFechaPdf($mantenimiento->fecha_solicitud),
            'descripcion_falla' => $mantenimiento->descripcion_falla ?: '—',
        ];
    }

    /**
     * Formatea una fecha a dd/mm/YYYY con fallback a la fecha actual.
     */
    protected static function formatearFechaPdf(mixed $fecha): string
    {
        return filled($fecha)
            ? \Carbon\Carbon::parse($fecha)->format('d/m/Y')
            : now()->format('d/m/Y');
    }

    /**
     * Genera el PDF del Formato 2: Solicitud-Orden de Trabajo de Mantenimiento.
     */
    public static function generarOrdenTrabajo(\App\Models\Mantenimiento $mantenimiento): \Barryvdh\DomPDF\PDF
    {
        return self::crearPdf('pdf.formato-orden-trabajo', self::datosOrdenTrabajo($mantenimiento));
    }

    /**
     * Descarga el Formato 2.
     */
    public static function descargarOrdenTrabajo(\App\Models\Mantenimiento $mantenimiento)
    {
        $pdf = self::generarOrdenTrabajo($mantenimiento);
        return $pdf->download('orden-trabajo-' . $mantenimiento->id_mantenimiento . '.pdf');
    }

    /**
     * Construye los datos reales para el Formato 3: Lista de Verificación de Mantenimiento.
     */
    public static function datosListaVerificacion(\App\Models\Mantenimiento $mantenimiento): array
    {
        $mantenimiento->loadMissing(['inventario.area']);

        $config = self::datosInstitucionales();

        $jefeCentroComputo = filled($config['centro_computo']['jefe'] ?? null)
            ? $config['centro_computo']['jefe']
            : '—';

        $subdireccion = filled($config['subdireccion']['nombre'] ?? null)
            ? $config['subdireccion']['nombre']
            : '—';

        $jefeSubdireccion = filled($config['subdireccion']['responsable'] ?? null)
            ? $config['subdireccion']['responsable']
            : '—';

        $fecha = self::formatearFechaPdf($mantenimiento->fecha_fin ?: $mantenimiento->fecha_solicitud);

        $espacioRevisado = filled($mantenimiento->inventario?->area?->nombre)
            ? $mantenimiento->inventario->area->nombre
            : 'Mantenimiento y Equipo';

        $hallazgo = collect([
            $mantenimiento->descripcion_trabajo,
            $mantenimiento->observaciones,
            $mantenimiento->descripcion_falla,
        ])->first(fn ($valor) => filled($valor)) ?? '—';

        $atendidoSi = $mantenimiento->estado === 'Completado';

        $nombreTecnico = filled($mantenimiento->nombre_tecnico)
            ? $mantenimiento->nombre_tecnico
            : '—';

        return [
            'config' => $config,
            'jefeCentroComputo' => $jefeCentroComputo,
            'subdireccion' => $subdireccion,
            'jefeSubdireccion' => $jefeSubdireccion,
            'fecha' => $fecha,
            'espacioRevisado' => $espacioRevisado,
            'hallazgo' => $hallazgo,
            'atendidoSi' => $atendidoSi,
            'nombreTecnico' => $nombreTecnico,
            'logoItv' => self::logoDataUri($config['logos']['itv'] ?? 'images/itv-logo.png'),
        ];
    }

    /**
     * Genera el PDF del Formato 3: Lista de Verificación de Mantenimiento.
     */
    public static function generarListaVerificacion(\App\Models\Mantenimiento $mantenimiento): \Barryvdh\DomPDF\PDF
    {
        return self::crearPdf('pdf.formato-lista-verificacion', self::datosListaVerificacion($mantenimiento));
    }

    /**
     * Descarga el Formato 3.
     */
    public static function descargarListaVerificacion(\App\Models\Mantenimiento $mantenimiento)
    {
        $pdf = self::generarListaVerificacion($mantenimiento);
        return $pdf->download('lista-verificacion-' . $mantenimiento->id_mantenimiento . '.pdf');
    }
}

