<?php

namespace App\Observers;

use App\Models\Inventario;

class InventarioObserver
{
    // NOTA: el stock de Material ya NO se incrementa/decrementa aquí.
    // Es un valor derivado (ver Material::stockActual()).

    public function updating(Inventario $inventario): void
    {
        if ($inventario->isDirty('estado') && $inventario->estado === 'Baja' && empty($inventario->fecha_baja)) {
            $inventario->fecha_baja = now();
        }
    }
}