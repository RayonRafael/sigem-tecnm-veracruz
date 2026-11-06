<?php

namespace App\Observers;

use App\Models\Mantenimiento;
use Illuminate\Support\Facades\DB;

class MantenimientoObserver
{
    // Cuando se CREA un nuevo mantenimiento
    public function created(Mantenimiento $mantenimiento): void
    {
        DB::transaction(function () use ($mantenimiento) {
            // Cambia el estado del inventario relacionado a "En Mantenimiento"
            $mantenimiento->inventario()->update(['estado' => 'En Mantenimiento']);
        });
    }

    // Cuando se ACTUALIZA un mantenimiento
    public function updated(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->isDirty('estado')) {
            DB::transaction(function () use ($mantenimiento) {
                $estadoNuevo = $mantenimiento->estado;

                if ($estadoNuevo === 'En proceso') {
                    $mantenimiento->inventario?->update(['estado' => 'En Mantenimiento']);
                } elseif ($estadoNuevo === 'Completado') {
                    $inventario = $mantenimiento->inventario;
                    if ($inventario) {
                        // inventario.id_usuario es quien REGISTRO la pieza (NOT NULL), no un prestatario,
                        // por lo que no sirve para deducir asignacion. Se devuelve a Disponible siempre.
                        $inventario->update(['estado' => 'Disponible']);
                    }
                }
            });
        }
    }
}
