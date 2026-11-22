<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\Resources\MantenimientoResource\Pages\ListMantenimientos as BaseListMantenimientos;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;

class ListMantenimientos extends BaseListMantenimientos
{
    protected static string $resource = MantenimientoResource::class;

    public function getTitle(): string
    {
        return '🔧 Mis Mantenimientos';
    }

    public function getSubheading(): ?string
    {
        return 'Reportes de equipo dañado que has enviado. Da clic en la fila o en el ojo 👁 para desplegar el detalle sin salir de la lista.';
    }
}
