<?php

namespace App\Support;

class SigemBajaFormatHelper
{
    /**
     * Límite máximo de equipos por generación de PDF multipágina en lote (Formato 4).
     */
    public const MAX_LOTE_BAJAS = 50;

    /**
     * Estados del inventario elegibles para el Formato 4 (Trámite de Baja).
     *
     * @var list<string>
     */
    public const ESTADOS_BAJA = [
        'Dado de baja',
        'Solicitud de baja',
        'Baja',
    ];

    /**
     * Indica si un estado de inventario corresponde a baja o solicitud de baja.
     */
    public static function esEstadoBaja(?string $estado): bool
    {
        return $estado !== null && in_array($estado, self::ESTADOS_BAJA, true);
    }

    /**
     * Filtra una colección o iterable de inventarios quedándose con los de baja / solicitud de baja.
     *
     * @param  iterable<\App\Models\Inventario>  $inventarios
     * @return \Illuminate\Support\Collection<int, \App\Models\Inventario>
     */
    public static function filtrarInventariosEnBaja(iterable $inventarios): \Illuminate\Support\Collection
    {
        $coleccion = $inventarios instanceof \Illuminate\Support\Collection
            ? $inventarios
            : collect($inventarios);

        return $coleccion->filter(fn ($item): bool => self::esEstadoBaja($item?->estado))->values();
    }

    /**
     * Valida que el lote de bajas no esté vacío ni supere el máximo permitido.
     */
    public static function mensajeErrorLote(\Illuminate\Support\Collection $bajas): ?string
    {
        if ($bajas->isEmpty()) {
            return 'No hay equipos dados de baja o con solicitud de baja en la selección';
        }

        if ($bajas->count() > self::MAX_LOTE_BAJAS) {
            return 'Máximo ' . self::MAX_LOTE_BAJAS . " equipos por lote PDF (seleccionaste {$bajas->count()}). Reduce la selección e intenta de nuevo.";
        }

        return null;
    }

    /**
     * Opciones oficiales de motivo_baja en formularios de SIGEM (8 opciones).
     * Mantiene intactos los 5 valores históricos e incorpora los 3 nuevos del formato TecNM.
     *
     * @return array<string, string>
     */
    public static function motivosBajaOptions(): array
    {
        return [
            'Daño irreparable' => 'Daño irreparable',
            'Obsolescencia' => 'Obsolescencia',
            'Pérdida' => 'Pérdida',
            'Robo' => 'Robo',
            'Destrucción' => 'Destrucción',
            'Reaprovechamiento' => 'Reaprovechamiento',
            'Desalojo' => 'Desalojo',
            'Otro' => 'Otro',
        ];
    }

    /**
     * Retorna un array con las 7 causas del formato oficial
     * y cuál está marcada con [X] según el motivo_baja de SIGEM.
     */
    public static function mapearCausas(?string $motivoBaja): array
    {
        $causas = [
            'asignar_a_otra_area' => ['label' => 'ASIGNAR A OTRA ÁREA, ESPECIFICAR:', 'marcada' => false],
            'destruccion' => ['label' => 'DESTRUCCIÓN', 'marcada' => false],
            'inutilidad' => ['label' => 'INUTILIDAD EN EL SERVICIO (FUNCIONA, PERO NO SE UTILIZA)', 'marcada' => false],
            'robo_extravio' => ['label' => 'ROBO O EXTRAVÍO (INCLUIR ACTA LEVANTADA EN EL MINISTERIO PÚBLICO)', 'marcada' => false],
            'reaprovechamiento' => ['label' => 'REAPROVECHAMIENTO (INSERVIBLE O DAÑADO SUS PARTES SE UTILIZAN)', 'marcada' => false],
            'danado_inservible' => ['label' => 'DAÑADO E INSERVIBLE', 'marcada' => false],
            'desalojo' => ['label' => 'DESALOJO', 'marcada' => false],
        ];

        $mapa = [
            'Daño irreparable' => 'danado_inservible',
            'Obsolescencia' => 'inutilidad',
            'Robo' => 'robo_extravio',
            'Pérdida' => 'robo_extravio',
            'Destrucción' => 'destruccion',
            'Reaprovechamiento' => 'reaprovechamiento',
            'Desalojo' => 'desalojo',
        ];

        if ($motivoBaja !== null && isset($mapa[$motivoBaja])) {
            $causas[$mapa[$motivoBaja]]['marcada'] = true;
        }

        return $causas;
    }

    /**
     * Retorna el nombre de la causa para mostrar en la UI.
     */
    public static function etiquetaCausa(string $causa): string
    {
        $etiquetas = [
            'asignar_a_otra_area' => 'Asignar a otra área',
            'destruccion' => 'Destrucción',
            'inutilidad' => 'Inutilidad en el servicio',
            'robo_extravio' => 'Robo o extravío',
            'reaprovechamiento' => 'Reaprovechamiento',
            'danado_inservible' => 'Dañado e inservible',
            'desalojo' => 'Desalojo',
        ];

        return $etiquetas[$causa] ?? $causa;
    }
}

