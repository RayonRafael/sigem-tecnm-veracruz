<?php

namespace App\Observers;

use App\Models\Solicitud;
use Illuminate\Support\Facades\Log;

class SolicitudObserver
{
    public function updated(Solicitud $solicitud): void
    {
        // Si la solicitud cambia a "Autorizado"
        if ($solicitud->isDirty('estado') && $solicitud->estado === 'Autorizado') {
            // Registramos en la consola/logs que la magia funcionó
            Log::info(' Observer activado: Solicitud autorizada ID: ' . $solicitud->id_solicitud);
        }
    }
}