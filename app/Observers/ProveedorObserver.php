<?php

namespace App\Observers;

use App\Models\Proveedor;
use App\Traits\RegistraBitacora;

class ProveedorObserver
{
    use RegistraBitacora;

    public function created(Proveedor $proveedor): void
    {
        $this->registrarBitacora(
            'crear',
            'proveedor',
            $proveedor->id_proveedor,
            null,
            $proveedor->toArray(),
            "Registró proveedor: {$proveedor->nombre_empresa}"
        );
    }

    public function updated(Proveedor $proveedor): void
    {
        $camposRelevantes = ['nombre_empresa', 'rfc', 'activo', 'contacto_nombre', 'contacto_telefono', 'contacto_email'];
        $cambiosRelevantes = false;

        foreach ($camposRelevantes as $campo) {
            if ($proveedor->isDirty($campo)) {
                $cambiosRelevantes = true;
                break;
            }
        }

        if ($cambiosRelevantes) {
            $this->registrarBitacora(
                'editar',
                'proveedor',
                $proveedor->id_proveedor,
                $proveedor->getOriginal(),
                $proveedor->getChanges(),
                "Actualizó proveedor: {$proveedor->nombre_empresa}"
            );
        }
    }

    public function deleted(Proveedor $proveedor): void
    {
        $this->registrarBitacora(
            'eliminar',
            'proveedor',
            $proveedor->id_proveedor,
            $proveedor->toArray(),
            null,
            "Eliminó proveedor: {$proveedor->nombre_empresa}"
        );
    }
}
