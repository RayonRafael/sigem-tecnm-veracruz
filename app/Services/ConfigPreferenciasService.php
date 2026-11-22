<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ConfigPreferenciasService
{
    public static function obtenerRegistrosPorHoja(?int $userId = null): int
    {
        $uid = $userId ?? auth()->id();

        return (int) session(
            'sigem_registros_por_hoja',
            Cache::get('sigem_registros_por_hoja_' . $uid, 10)
        );
    }

    /**
     * @return array{ok: bool, valor: int, mensaje: string}
     */
    public static function guardarRegistrosPorHoja(int $registrosPorHoja, string $otroValor = '', ?int $userId = null): array
    {
        $valorFinal = $registrosPorHoja;

        if (! empty($otroValor)) {
            $val = (int) $otroValor;
            if ($val < 5 || $val > 500) {
                return [
                    'ok' => false,
                    'valor' => $registrosPorHoja,
                    'mensaje' => 'El valor personalizado debe estar entre 5 y 500.',
                ];
            }
            $valorFinal = $val;
        }

        $uid = $userId ?? auth()->id();
        session(['sigem_registros_por_hoja' => $valorFinal]);
        Cache::forever('sigem_registros_por_hoja_' . $uid, $valorFinal);

        return [
            'ok' => true,
            'valor' => $valorFinal,
            'mensaje' => "✓ Preferencias guardadas ({$valorFinal} registros por hoja).",
        ];
    }
}
