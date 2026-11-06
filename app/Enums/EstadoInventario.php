<?php

namespace App\Enums;

enum EstadoInventario: string
{
    case Disponible = 'Disponible';
    case Asignado = 'Asignado';
    case Reasignado = 'Reasignado';
    case EnMantenimiento = 'En Mantenimiento';
    case DadoDeBaja = 'Dado de baja';
    case SolicitudDeBaja = 'Solicitud de baja';
    case CambioDeArea = 'Cambio de área';
    case Danado = 'Dañado';
    case DevueltoAProveedor = 'Devuelto a Proveedor';

    public function color(): string
    {
        return match ($this) {
            self::Disponible => 'success',
            self::Asignado, self::Reasignado => 'info',
            self::EnMantenimiento => 'warning',
            self::DadoDeBaja, self::Danado => 'danger',
            self::SolicitudDeBaja, self::CambioDeArea => 'gray',
            self::DevueltoAProveedor => 'warning',
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Disponible => 'heroicon-m-check-circle',
            self::Asignado => 'heroicon-m-user',
            self::Reasignado => 'heroicon-m-arrow-path',
            self::EnMantenimiento => 'heroicon-m-wrench-screwdriver',
            self::DadoDeBaja => 'heroicon-m-x-circle',
            self::SolicitudDeBaja => 'heroicon-m-clock',
            self::CambioDeArea => 'heroicon-m-arrow-right-circle',
            self::Danado => 'heroicon-m-exclamation-triangle',
            self::DevueltoAProveedor => 'heroicon-m-arrow-uturn-left',
        };
    }

    /**
     * Devuelve las opciones como array asociativo [valor => label].
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->value;
        }
        return $options;
    }
}
