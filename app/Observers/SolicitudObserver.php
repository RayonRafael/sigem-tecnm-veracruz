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

            if ($solicitud->tipo_movimiento === 'Asignacion Permanente') {
                if ($estadoAnterior !== 'Autorizado' && $estadoNuevo === 'Autorizado') {
                    // Decrement stock
                    foreach ($solicitud->detalles as $detalle) {
                        if ($detalle->material) {
                            $detalle->material->decrement('stock_actual', $detalle->cantidad);
                        }
                    }
                } elseif ($estadoAnterior === 'Autorizado' && $estadoNuevo === 'Rechazado') {
                    // Restore stock
                    foreach ($solicitud->detalles as $detalle) {
                        if ($detalle->material) {
                            $detalle->material->increment('stock_actual', $detalle->cantidad);
                        }
                    }
                }
            }

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