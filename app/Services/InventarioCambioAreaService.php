<?php

namespace App\Services;

use App\Enums\EstadoInventario;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Salon;
use App\Support\UbicacionHelper;
use Illuminate\Support\Facades\DB;

class InventarioCambioAreaService
{
    public static function cambiarAreaDirecto(
        Inventario $inventario,
        $departamentoId,
        $areaId,
        $salonId = null,
        $estacion = null,
        ?string $motivo = null,
        ?int $usuarioId = null
    ): void {
        DB::transaction(function () use ($inventario, $departamentoId, $areaId, $salonId, $estacion, $motivo, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoAnterior = $inventario->estado ?? EstadoInventario::Disponible->value;

            [$salonIdFinal, $aulaTexto] = self::resolverSalonYAula($salonId, $areaId);

            $ubicacionAnterior = $inventario->ubicacion_fisica
                ?: (UbicacionHelper::construir($inventario->departamento_id, $inventario->area_id, $inventario->aula ?: $inventario->salon_id, $inventario->estacion) ?: 'Sin ubicación previa');

            $ubicacionNueva = UbicacionHelper::construir($departamentoId, $areaId, $aulaTexto ?: $salonIdFinal, $estacion);

            $inventario->skipHistorialObserver = true;

            $inventario->departamento_id = $departamentoId;
            $inventario->area_id = $areaId;
            $inventario->salon_id = self::validarSalonEnArea($salonIdFinal, $areaId);
            $inventario->aula = $aulaTexto;
            $inventario->estacion = $estacion;
            $inventario->fecha_cambio_area = now();

            $specs = is_array($inventario->especificaciones) ? $inventario->especificaciones : [];
            if ($aulaTexto !== null) {
                $specs['aula'] = $aulaTexto;
            } else {
                unset($specs['aula']);
            }
            $inventario->especificaciones = $specs;

            if (! empty($ubicacionNueva)) {
                $inventario->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
            }
            $inventario->save();

            $textoAnterior = $ubicacionAnterior ?: 'Sin ubicación previa';
            $textoNuevo = $inventario->ubicacion_fisica ?: ($ubicacionNueva ?: 'Sin ubicación asignada');
            $motivoTexto = "Cambio de área: {$textoAnterior} → {$textoNuevo}";

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $inventario->estado ?? EstadoInventario::Disponible->value,
                'motivo_cambio' => $motivoTexto,
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function solicitarCambioArea(
        Inventario $inventario,
        $departamentoId,
        $areaId,
        $salonId = null,
        $estacion = null,
        ?string $motivo = null,
        ?int $usuarioId = null
    ): void {
        DB::transaction(function () use ($inventario, $departamentoId, $areaId, $salonId, $estacion, $motivo, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoAnterior = $inventario->estado ?? EstadoInventario::Disponible->value;

            [$salonIdFinal, $aulaTexto] = self::resolverSalonYAula($salonId, $areaId);
            $salonIdFinal = self::validarSalonEnArea($salonIdFinal, $areaId);

            $solicitudData = [
                'departamento_id' => $departamentoId,
                'area_id' => $areaId,
                'salon_id' => $salonIdFinal,
                'aula' => $aulaTexto,
                'estacion' => $estacion,
                'motivo' => $motivo,
            ];

            $inventario->update([
                'estado' => EstadoInventario::CambioDeArea->value,
                'estado_previo_cambio' => $estadoAnterior,
                'ubicacion_solicitada' => $solicitudData,
            ]);

            $motivoHistorial = filled($motivo) ? "Solicitud de cambio de área: {$motivo}" : 'Solicitud de cambio de área';

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => EstadoInventario::CambioDeArea->value,
                'motivo_cambio' => $motivoHistorial,
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function aprobarCambioArea(Inventario $inventario, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inventario, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $solicitud = $inventario->ubicacion_solicitada ?? [];
            $estadoPrevio = $inventario->estado_previo_cambio ?? EstadoInventario::Disponible->value;

            $depto = $solicitud['departamento_id'] ?? $inventario->departamento_id;
            $area = $solicitud['area_id'] ?? $inventario->area_id;
            $salon = $solicitud['salon_id'] ?? null;
            $aula = $solicitud['aula'] ?? ($salon ? Salon::find($salon)?->nombre : null);
            $estacion = $solicitud['estacion'] ?? null;

            $inventario->skipHistorialObserver = true;

            $inventario->departamento_id = $depto;
            $inventario->area_id = $area;
            $inventario->salon_id = self::validarSalonEnArea($salon, $area);
            $inventario->aula = $aula;
            $inventario->estacion = $estacion;
            $inventario->estado = $estadoPrevio;
            $inventario->fecha_cambio_area = now();
            $inventario->ubicacion_solicitada = null;
            $inventario->estado_previo_cambio = null;

            $specs = is_array($inventario->especificaciones) ? $inventario->especificaciones : [];
            if ($aula !== null) {
                $specs['aula'] = $aula;
            } else {
                unset($specs['aula']);
            }
            $inventario->especificaciones = $specs;

            $ubicacionNueva = UbicacionHelper::construir($depto, $area, $aula ?: $salon, $estacion);
            if (! empty($ubicacionNueva)) {
                $inventario->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
            }
            $inventario->save();

            $ubicacionTexto = $inventario->ubicacion_fisica ?: ($ubicacionNueva ?: 'Sin ubicación asignada');

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => EstadoInventario::CambioDeArea->value,
                'estado_nuevo' => $estadoPrevio,
                'motivo_cambio' => "Cambio de área aprobado: {$ubicacionTexto}",
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function rechazarCambioArea(Inventario $inventario, ?string $motivoRechazo = null, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inventario, $motivoRechazo, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoPrevio = $inventario->estado_previo_cambio ?? EstadoInventario::Disponible->value;

            $inventario->update([
                'estado' => $estadoPrevio,
                'ubicacion_solicitada' => null,
                'estado_previo_cambio' => null,
            ]);

            $motivoTexto = filled($motivoRechazo) ? "Cambio de área rechazado: {$motivoRechazo}" : 'Cambio de área rechazado';

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => EstadoInventario::CambioDeArea->value,
                'estado_nuevo' => $estadoPrevio,
                'motivo_cambio' => $motivoTexto,
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    /**
     * @return array{0: ?int, 1: ?string}
     */
    private static function resolverSalonYAula($salonId, $areaId): array
    {
        $aulaTexto = null;
        $salonIdFinal = null;

        if (! empty($salonId) && ! is_numeric($salonId) && is_string($salonId)) {
            $aulaTexto = trim($salonId);
            $salonObj = Salon::firstOrCreate(['nombre' => $aulaTexto, 'area_id' => $areaId]);
            $salonIdFinal = $salonObj->id;
        } elseif (! empty($salonId) && is_numeric($salonId)) {
            $salonIdFinal = (int) $salonId;
            $aulaTexto = Salon::find($salonIdFinal)?->nombre;
        }

        return [$salonIdFinal, $aulaTexto];
    }

    private static function validarSalonEnArea(?int $salonId, $areaId): ?int
    {
        if (! $salonId) {
            return null;
        }

        $salonValido = Salon::where('id', $salonId)
            ->where('area_id', $areaId)
            ->whereNull('deleted_at')
            ->exists();

        return $salonValido ? $salonId : null;
    }
}
