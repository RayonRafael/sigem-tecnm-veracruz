<?php

namespace App\Observers;

use App\Models\Solicitud;
use App\Traits\RegistraBitacora;

class SolicitudObserver
{
    use RegistraBitacora;

    public function created(Solicitud $solicitud): void
    {
        $this->registrarBitacora(
            'crear',
            'solicitud',
            $solicitud->id_solicitud,
            null,
            $solicitud->toArray(),
            "Creó solicitud de {$solicitud->tipo_movimiento}"
        );
    }

    public function updated(Solicitud $solicitud): void
    {
        if ($solicitud->isDirty('estado')) {
            $estadoAnterior = $solicitud->getOriginal('estado');
            $estadoNuevo = $solicitud->estado;

            $this->registrarBitacora(
                'editar',
                'solicitud',
                $solicitud->id_solicitud,
                $solicitud->getOriginal(),
                $solicitud->getChanges(),
                "Cambió solicitud #{$solicitud->id_solicitud} de '{$estadoAnterior}' a '{$estadoNuevo}'"
            );
        }
    }
}