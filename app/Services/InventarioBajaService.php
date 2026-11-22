<?php

namespace App\Services;

use App\Enums\EstadoInventario;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

class InventarioBajaService
{
    public static function darBajaDirecta(
        Inventario $inventario,
        string $motivo,
        string $observaciones,
        ?int $usuarioId = null,
        array $datosAdicionales = []
    ): void {
        DB::transaction(function () use ($inventario, $motivo, $observaciones, $usuarioId, $datosAdicionales) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoAnterior = $inventario->estado ?? EstadoInventario::Disponible->value;

            $payload = [
                'estado' => EstadoInventario::DadoDeBaja->value,
                'fecha_baja' => now(),
                'motivo_baja' => $motivo,
                'observaciones_baja' => $observaciones,
                'id_usuario_baja' => $userId,
            ];

            if (! empty($datosAdicionales)) {
                $payload['especificaciones'] = self::aplicarDatosBajaEnEspecificaciones($inventario, $datosAdicionales);
            }

            $inventario->update($payload);

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => EstadoInventario::DadoDeBaja->value,
                'motivo_cambio' => "Baja directa: {$motivo}",
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function solicitarBaja(
        Inventario $inventario,
        string $motivo,
        string $observaciones,
        ?int $usuarioId = null,
        array $datosAdicionales = []
    ): void {
        DB::transaction(function () use ($inventario, $motivo, $observaciones, $usuarioId, $datosAdicionales) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoAnterior = $inventario->estado ?? EstadoInventario::Disponible->value;

            $payload = [
                'estado' => EstadoInventario::SolicitudDeBaja->value,
                'estado_previo_baja' => $estadoAnterior,
                'motivo_baja' => $motivo,
                'observaciones_baja' => $observaciones,
                'id_usuario_baja' => $userId,
            ];

            if (! empty($datosAdicionales)) {
                $payload['especificaciones'] = self::aplicarDatosBajaEnEspecificaciones($inventario, $datosAdicionales);
            }

            $inventario->update($payload);

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => EstadoInventario::SolicitudDeBaja->value,
                'motivo_cambio' => "Solicitud de baja: {$motivo}",
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function aprobarBaja(Inventario $inventario, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inventario, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $motivo = $inventario->motivo_baja ?? 'Sin motivo especificado';

            $inventario->update([
                'estado' => EstadoInventario::DadoDeBaja->value,
                'fecha_baja' => now(),
                'id_usuario_baja' => $userId,
            ]);

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => EstadoInventario::SolicitudDeBaja->value,
                'estado_nuevo' => EstadoInventario::DadoDeBaja->value,
                'motivo_cambio' => "Baja aprobada: {$motivo}",
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function rechazarBaja(Inventario $inventario, ?string $observaciones = null, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inventario, $observaciones, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);
            $estadoRestaurar = $inventario->estado_previo_baja ?? EstadoInventario::Disponible->value;
            $obsTexto = ! empty($observaciones) ? $observaciones : ($inventario->observaciones_baja ?? 'Sin observaciones');

            $inventario->update([
                'estado' => $estadoRestaurar,
                'estado_previo_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'id_usuario_baja' => null,
                'fecha_baja' => null,
                'especificaciones' => self::limpiarDatosBajaDeEspecificaciones($inventario),
            ]);

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => EstadoInventario::SolicitudDeBaja->value,
                'estado_nuevo' => $estadoRestaurar,
                'motivo_cambio' => "Baja rechazada: {$obsTexto}",
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    public static function reactivar(Inventario $inventario, ?int $usuarioId = null): void
    {
        DB::transaction(function () use ($inventario, $usuarioId) {
            $userId = $inventario->resolverUsuarioId($usuarioId);

            $inventario->update([
                'estado' => EstadoInventario::Disponible->value,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
                'id_usuario_baja' => null,
                'estado_previo_baja' => null,
                'especificaciones' => self::limpiarDatosBajaDeEspecificaciones($inventario),
            ]);

            HistorialEstado::create([
                'id_inventario' => $inventario->id_inventario,
                'id_usuario' => $userId,
                'estado_anterior' => EstadoInventario::DadoDeBaja->value,
                'estado_nuevo' => EstadoInventario::Disponible->value,
                'motivo_cambio' => 'Reactivación del equipo',
                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                'fecha_cambio' => now(),
            ]);
        });
    }

    private static function aplicarDatosBajaEnEspecificaciones(Inventario $inventario, array $datosAdicionales): array
    {
        $specs = is_array($inventario->especificaciones) ? $inventario->especificaciones : [];

        foreach (['folio_sibsep', 'posible_area_utilizacion'] as $campo) {
            if (array_key_exists($campo, $datosAdicionales)) {
                $valor = $datosAdicionales[$campo];
                if (filled($valor)) {
                    $specs[$campo] = trim((string) $valor);
                } else {
                    unset($specs[$campo]);
                }
            }
        }

        return $specs;
    }

    private static function limpiarDatosBajaDeEspecificaciones(Inventario $inventario): array
    {
        $specs = is_array($inventario->especificaciones) ? $inventario->especificaciones : [];
        unset($specs['folio_sibsep'], $specs['posible_area_utilizacion']);

        return $specs;
    }
}
