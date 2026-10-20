<?php

namespace App\Observers;

use App\Models\Mantenimiento;

class MantenimientoObserver
{
    // Cuando se CREA un nuevo mantenimiento
    public function created(Mantenimiento $mantenimiento): void
    {
        // Cambia el estado del inventario relacionado a "En Mantenimiento"
        $mantenimiento->inventario()->update(['estado' => 'En Mantenimiento']);
    }

    // Cuando se ACTUALIZA un mantenimiento
    public function updated(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->isDirty('estado')) {
            $estadoNuevo = $mantenimiento->estado;

            // Si el estado cambió y ahora es "Completado"
            if ($estadoNuevo === 'Completado') {
                $inventario = $mantenimiento->inventario;
                if ($inventario) {
                    // inventario.id_usuario es quien REGISTRO la pieza (NOT NULL), no un prestatario,
                    // por lo que no sirve para deducir asignacion. Se devuelve a Disponible siempre.
                    $inventario->update(['estado' => 'Disponible']);
                }
            }
        }
    }
}
