<?php

namespace App\Enums;

enum EstadoSolicitud: string
{
    case Pendiente = 'Pendiente';
    case Autorizado = 'Autorizado';
    case Rechazado = 'Rechazado';
    case Completado = 'Completado';
    case Cancelado = 'Cancelado';

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Autorizado => 'success',
            self::Rechazado, self::Cancelado => 'danger',
            self::Completado => 'info',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->value;
        }
        return $options;
    }
}
