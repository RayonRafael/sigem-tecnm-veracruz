<?php

namespace App\Support;

use App\Enums\EstadoInventario;
use App\Enums\EstadoSolicitud;

class EstadoBadgeHelper
{
    /**
     * Devuelve el color de Filament para un estado de inventario dado.
     */
    public static function colorInventario(?string $estado): string
    {
        if (! $estado) {
            return 'gray';
        }

        $enum = EstadoInventario::tryFrom($estado);
        if ($enum) {
            return $enum->color();
        }

        return match ($estado) {
            'Baja' => 'danger',
            'En mantenimiento' => 'warning',
            default => 'gray',
        };
    }

    /**
     * Devuelve el icono para un estado de inventario dado.
     */
    public static function iconoInventario(?string $estado): ?string
    {
        if (! $estado) {
            return null;
        }

        $enum = EstadoInventario::tryFrom($estado);
        return $enum?->icono();
    }

    /**
     * Devuelve el color de Filament para un estado de solicitud.
     */
    public static function colorSolicitud(?string $estado): string
    {
        if (! $estado) {
            return 'gray';
        }

        $enum = EstadoSolicitud::tryFrom($estado);
        return $enum ? $enum->color() : 'gray';
    }
}
