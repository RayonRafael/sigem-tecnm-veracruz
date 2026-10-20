<?php

namespace App\Observers;

/**
 * El stock de Material es un valor derivado (ver Material::stockActual()),
 * por lo que las solicitudes ya no descuentan ni restituyen stock manualmente.
 * El observer se conserva registrado para futuras reglas de negocio.
 */
class SolicitudObserver
{
}