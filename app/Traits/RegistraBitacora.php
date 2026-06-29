<?php

namespace App\Traits;

use App\Models\BitacoraSistema;

trait RegistraBitacora
{
    protected function registrarBitacora(
        string $accion,
        string $tablaAfectada,
        int $registroId,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?string $detalles = null
    ): void {
        BitacoraSistema::create([
            'id_usuario' => auth()->id(),
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'detalles' => $detalles,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
        ]);
    }
}
