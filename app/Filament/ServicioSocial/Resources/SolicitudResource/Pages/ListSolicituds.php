<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\Resources\SolicitudResource\Pages\ListSolicituds as BaseListSolicituds;
use App\Filament\ServicioSocial\Resources\SolicitudResource;

class ListSolicituds extends BaseListSolicituds
{
    protected static string $resource = SolicitudResource::class;

    public function getSubheading(): ?string
    {
        return 'Trámites que has enviado. Da clic en la fila o en el ojo 👁 para desplegar el detalle en el panel lateral.';
    }
}
