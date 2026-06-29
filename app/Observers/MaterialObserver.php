<?php

namespace App\Observers;

use App\Models\Material;
use App\Traits\RegistraBitacora;

class MaterialObserver
{
    use RegistraBitacora;

    public function created(Material $material): void
    {
        $this->registrarBitacora(
            'crear',
            'material',
            $material->id_producto,
            null,
            $material->toArray(),
            "Registró material: {$material->nombre}"
        );
    }

    public function updated(Material $material): void
    {
        $camposRelevantes = ['nombre', 'stock_actual', 'stock_minimo', 'id_marca', 'id_tipodematerial', 'id_unidad', 'requiere_control_individual'];
        $cambiosRelevantes = false;

        foreach ($camposRelevantes as $campo) {
            if ($material->isDirty($campo)) {
                $cambiosRelevantes = true;
                break;
            }
        }

        if ($cambiosRelevantes) {
            $this->registrarBitacora(
                'editar',
                'material',
                $material->id_producto,
                $material->getOriginal(),
                $material->getChanges(),
                "Actualizó material: {$material->nombre}"
            );
        }
    }

    public function deleted(Material $material): void
    {
        $this->registrarBitacora(
            'eliminar',
            'material',
            $material->id_producto,
            $material->toArray(),
            null,
            "Eliminó material: {$material->nombre}"
        );
    }
}
