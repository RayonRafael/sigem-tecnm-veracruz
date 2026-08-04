<?php

namespace App\Observers;

use App\Models\Inventario;
use App\Traits\RegistraBitacora;

class InventarioObserver
{
    use RegistraBitacora;

    public function created(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->increment('stock_actual');
        }
        $this->registrarBitacora(
            'crear',
            'inventario',
            $inventario->id_inventario,
            null,
            $inventario->toArray(),
            "Registro de activo: {$inventario->num_serie}"
        );
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

        $camposRelevantes = ['estado', 'ubicacion_fisica', 'tipo_propiedad', 'id_usuario'];
        $cambiosRelevantes = false;

        foreach ($camposRelevantes as $campo) {
            if ($inventario->isDirty($campo)) {
                $cambiosRelevantes = true;
                break;
            }
        }

        if ($cambiosRelevantes) {
            $this->registrarBitacora(
                'editar',
                'inventario',
                $inventario->id_inventario,
                $inventario->getOriginal(),
                $inventario->getChanges(),
                "Actualizó activo: {$inventario->num_serie}"
            );
        }
    }

    public function deleted(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->decrement('stock_actual');
        }
        $this->registrarBitacora(
            'eliminar',
            'inventario',
            $inventario->id_inventario,
            $inventario->toArray(),
            null,
            "Eliminó activo: {$inventario->num_serie}"
        );
    }
}
