<?php

namespace App\Observers;

use App\Models\Mantenimiento;
use App\Traits\RegistraBitacora;

class MantenimientoObserver
{
    use RegistraBitacora;

    // Cuando se CREA un nuevo mantenimiento
    public function created(Mantenimiento $mantenimiento): void
    {
        // Cambia el estado del inventario relacionado a "En Mantenimiento"
        $mantenimiento->inventario()->update(['estado' => 'En Mantenimiento']);

        $numSerie = $mantenimiento->inventario->num_serie ?? 'N/A';
        $this->registrarBitacora(
            'crear',
            'mantenimiento',
            $mantenimiento->id_mantenimiento,
            null,
            $mantenimiento->toArray(),
            "Solicitó mantenimiento {$mantenimiento->tipo_mantenimiento} para {$numSerie}"
        );
    }

    // Cuando se ACTUALIZA un mantenimiento
    public function updated(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->isDirty('estado')) {
            $estadoAnterior = $mantenimiento->getOriginal('estado');
            $estadoNuevo = $mantenimiento->estado;

            // Si el estado cambió y ahora es "Completado"
            if ($estadoNuevo === 'Completado') {
                // Devuelve el inventario a "Disponible"
                $mantenimiento->inventario()->update(['estado' => 'Disponible']);
            }

            $this->registrarBitacora(
                'editar',
                'mantenimiento',
                $mantenimiento->id_mantenimiento,
                $mantenimiento->getOriginal(),
                $mantenimiento->getChanges(),
                "Cambió mantenimiento #{$mantenimiento->id_mantenimiento} de '{$estadoAnterior}' a '{$estadoNuevo}'"
            );
        }
    }
}