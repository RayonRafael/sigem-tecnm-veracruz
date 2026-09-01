<?php

namespace App\Observers;

use App\Models\Inventario;

class InventarioObserver
{
    public function created(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->increment('stock_actual');
        }
    }

    public function updated(Inventario $inventario): void
    {
        if ($inventario->isDirty('id_producto')) {
            $oldMaterial = \App\Models\Material::find($inventario->getOriginal('id_producto'));
            if ($oldMaterial) {
                $oldMaterial->decrement('stock_actual');
            }
            if ($inventario->material) {
                $inventario->material->increment('stock_actual');
            }
        }

        if ($inventario->isDirty('estado') && $inventario->estado === 'Baja' && empty($inventario->fecha_baja)) {
            $inventario->fecha_baja = now();
            $inventario->saveQuietly();
        }
    }

    public function deleted(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->decrement('stock_actual');
        }
    }
}
