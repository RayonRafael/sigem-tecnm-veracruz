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
        // Si el estado cambió y ahora es "Completado"
        if ($mantenimiento->isDirty('estado') && $mantenimiento->estado === 'Completado') {
            // Devuelve el inventario a "Disponible"
            $mantenimiento->inventario()->update(['estado' => 'Disponible']);
        }
    }
}