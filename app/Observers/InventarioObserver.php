<?php

namespace App\Observers;

use App\Models\Inventario;
use App\Traits\RegistraBitacora;

class InventarioObserver
{
    use RegistraBitacora;

    public function created(Inventario $inventario): void
    {
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
