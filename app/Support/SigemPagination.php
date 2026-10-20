<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class SigemPagination
{
    /**
     * Retorna el número de registros por hoja preferido por el usuario activo,
     * leyendo primero la sesión y luego la caché persistente (fallback 10).
     */
    public static function getPageOption(): int
    {
        $userId = auth()->id() ?? 'guest';

        return (int) session(
            'sigem_registros_por_hoja',
            Cache::get('sigem_registros_por_hoja_' . $userId, 10)
        );
    }

    /**
     * Retorna el array de opciones disponibles para el selector de paginado,
     * asegurando que la opción activa esté presente.
     */
    public static function getPageOptions(): array
    {
        $current = self::getPageOption();
        $options = [10, 25, 50, 100];
        if (! in_array($current, $options, true)) {
            $options[] = $current;
            sort($options);
        }

        return $options;
    }
}
