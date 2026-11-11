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
            $solicitante = self::unirNombre(
                $mantenimiento->usuarioSolicita->name,
                $mantenimiento->usuarioSolicita->apellido_paterno,
                $mantenimiento->usuarioSolicita->apellido_materno
            );
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
     * Une partes no vacías de un nombre propio en una sola cadena.
     */
    protected static function unirNombre(?string ...$partes): string
    {
        return trim(collect($partes)->filter(fn ($v) => filled($v))->implode(' '));
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

    /**
     * Construye los datos reales para el Formato 1: Entrega de Equipo/Bien.
     */
    public static function datosActaEntrega(
        \App\Models\Solicitud $solicitud,
        ?\App\Models\DetalleSolicitud $detalle = null
    ): array {
        $solicitud->loadMissing([
            'receptor',
            'usuario',
            'autorizadoPor',
            'detalles.inventario.material.marca',
            'detalles.material.marca',
        ]);

        $config = self::datosInstitucionales();
        $detalle = $detalle ?? $solicitud->detalles->first();
        $inventario = $detalle?->inventario;
        $material = $inventario?->material ?? $detalle?->material;

        $bienEntrega = self::unirNombre($material?->nombre, $inventario?->codigo) ?: '—';

        $receptor = $solicitud->receptor;
        $unidadOrganica = $receptor
            ? (self::unirNombre($receptor->nombre, $receptor->apellido_paterno, $receptor->apellido_materno) ?: '—')
            : '—';

        $fecha = self::formatearFechaPdf($solicitud->fecha_solicitud ?: $inventario?->fecha_asignacion);
        $hora = $solicitud->created_at
            ? $solicitud->created_at->format('H:i')
            : now()->format('H:i');

        $descripcion = collect([
            $inventario?->especificacion_texto,
            $material?->descripcion,
            $inventario?->subtipo,
        ])->first(fn ($valor) => filled($valor)) ?? '—';

        $marca = $material?->marca?->nombre
            ?? (is_array($inventario?->especificaciones) ? ($inventario->especificaciones['marca'] ?? null) : null);
        $modelo = $inventario?->modelo ?: $material?->modelo;
        $numSerie = $inventario?->num_serie ?: 'S/N';
        $marcaModeloSerie = self::unirNombre($marca, $modelo, $numSerie) ?: '—';

        $observaciones = collect([
            $solicitud->observaciones,
            $inventario?->observaciones_generales,
        ])->first(fn ($valor) => filled($valor)) ?? '—';

        $usuarioEntrega = auth()->user() ?? $solicitud->autorizadoPor ?? $solicitud->usuario;
        $nombreEntrega = $usuarioEntrega
            ? (self::unirNombre($usuarioEntrega->name, $usuarioEntrega->apellido_paterno, $usuarioEntrega->apellido_materno) ?: '—')
            : '—';

        return array_merge([
            'config' => $config,
            'bienEntrega' => $bienEntrega,
            'unidadOrganica' => $unidadOrganica,
            'fecha' => $fecha,
            'hora' => $hora,
            'descripcion' => $descripcion,
            'marcaModeloSerie' => $marcaModeloSerie,
            'observaciones' => $observaciones,
            'nombreEntrega' => $nombreEntrega,
            'nombreRecepcion' => $unidadOrganica,
        ], self::logosInstitucionales($config));
    }

    /**
     * Resuelve los Data URIs de los logos institucionales para formatos PDF.
     */
    protected static function logosInstitucionales(array $config): array
    {
        return [
            'logoSep' => self::logoDataUri($config['logos']['sep'] ?? 'images/sep-logo.png'),
            'logoTecnm' => self::logoDataUri($config['logos']['tecnm'] ?? 'images/tecnm-logo.png'),
            'logoItv' => self::logoDataUri($config['logos']['itv'] ?? 'images/itv-logo.png'),
            'logoIso9001' => self::logoDataUri($config['logos']['iso_9001'] ?? 'images/iso-9001.png'),
            'logoIso14001' => self::logoDataUri($config['logos']['iso_14001'] ?? 'images/iso-14001.png'),
        ];
    }

    /**
     * Genera el PDF del Formato 1: Entrega de Equipo/Bien.
     */
    public static function generarActaEntrega(
        \App\Models\Solicitud $solicitud,
        ?\App\Models\DetalleSolicitud $detalle = null
    ): \Barryvdh\DomPDF\PDF {
        return self::crearPdf('pdf.formato-entrega', self::datosActaEntrega($solicitud, $detalle));
    }

    /**
     * Descarga el Formato 1.
     */
    public static function descargarActaEntrega(
        \App\Models\Solicitud $solicitud,
        ?\App\Models\DetalleSolicitud $detalle = null
    ) {
        $pdf = self::generarActaEntrega($solicitud, $detalle);
        return $pdf->download('acta-entrega-' . $solicitud->id_solicitud . '.pdf');
    }

    /**
     * Construye los datos reales para el Formato 4: Trámite de Baja (lote o individual).
     *
     * @param  iterable<\App\Models\Inventario>  $inventarios
     */
    public static function datosFormatosBaja($inventarios): array
    {
        $registros = $inventarios instanceof \Illuminate\Support\Collection
            ? $inventarios
            : collect($inventarios);

        if ($registros instanceof \Illuminate\Database\Eloquent\Collection) {
            $registros->loadMissing(['departamento', 'material.marca']);
        } else {
            $registros->each(function ($item): void {
                if ($item instanceof \App\Models\Inventario) {
                    $item->loadMissing(['departamento', 'material.marca']);
                }
            });
        }

        $config = self::datosInstitucionales();

        return array_merge([
            'config' => $config,
            'registros' => $registros,
        ], self::logosInstitucionales($config));
    }

    /**
     * Genera el PDF del Formato 4: Trámite de Baja (individual — 1 página).
     */
    public static function generarFormatoBaja(\App\Models\Inventario $inventario): \Barryvdh\DomPDF\PDF
    {
        return self::generarFormatosBaja(collect([$inventario]));
    }

    /**
     * Genera el PDF del Formato 4: Trámite de Baja (lote multipágina: 1 página por bien).
     *
     * @param  iterable<\App\Models\Inventario>  $inventarios
     */
    public static function generarFormatosBaja($inventarios): \Barryvdh\DomPDF\PDF
    {
        return self::crearPdf('pdf.formato-baja', self::datosFormatosBaja($inventarios));
    }

    /**
     * Descarga el Formato 4 (individual).
     */
    public static function descargarFormatoBaja(\App\Models\Inventario $inventario)
    {
        $pdf = self::generarFormatoBaja($inventario);
        $codigo = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($inventario->codigo ?: $inventario->num_serie ?: $inventario->id_inventario));

        return $pdf->download('tramite-baja-' . trim((string) $codigo, '-') . '.pdf');
    }

    /**
     * Descarga el Formato 4 (lote multipágina).
     *
     * @param  iterable<\App\Models\Inventario>  $inventarios
     */
    public static function descargarFormatosBajaLote($inventarios)
    {
        $pdf = self::generarFormatosBaja($inventarios);
        return $pdf->download('tramites-baja-lote.pdf');
    }
}

