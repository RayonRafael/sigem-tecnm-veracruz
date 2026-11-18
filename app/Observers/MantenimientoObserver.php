<?php

namespace App\Observers;

use App\Enums\EstadoInventario;
use App\Models\HistorialEstado;
use App\Models\Mantenimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MantenimientoObserver
{
    // Cuando se CREA un nuevo mantenimiento
    public function created(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->estado === 'En proceso') {
            DB::transaction(function () use ($mantenimiento) {
                $mantenimiento->inventario()->update([
                    'estado' => EstadoInventario::EnMantenimiento->value,
                ]);
            });
        }
    }

    // Cuando se ACTUALIZA un mantenimiento
    public function updated(Mantenimiento $mantenimiento): void
    {
        if ($mantenimiento->isDirty('estado')) {
            DB::transaction(function () use ($mantenimiento) {
                $estadoNuevo = $mantenimiento->estado;

                if ($estadoNuevo === 'En proceso') {
                    $mantenimiento->inventario?->update([
                        'estado' => EstadoInventario::EnMantenimiento->value,
                    ]);
                } elseif ($estadoNuevo === 'Completado') {
                    $inventario = $mantenimiento->inventario;
                    if ($inventario) {
                        $inventario->update([
                            'estado' => EstadoInventario::Disponible->value,
                        ]);
                    }
                } elseif ($estadoNuevo === 'Cancelado') {
                    $inventario = $mantenimiento->inventario;
                    if ($inventario && in_array($inventario->estado, [EstadoInventario::EnMantenimiento->value, 'En Mantenimiento'], true)) {
                        $estadoAnterior = $inventario->estado;
                        $inventario->update([
                            'estado' => EstadoInventario::Disponible->value,
                        ]);

                        $userId = auth()->id()
                            ?? $mantenimiento->id_usuario_solicita
                            ?? $inventario->id_usuario
                            ?? User::where('email', 'admin@tecnm.edu.mx')->value('id')
                            ?? User::first()?->id;

                        if ($userId) {
                            HistorialEstado::create([
                                'id_inventario' => $inventario->id_inventario,
                                'id_usuario' => $userId,
                                'estado_anterior' => $estadoAnterior,
                                'estado_nuevo' => EstadoInventario::Disponible->value,
                                'motivo_cambio' => 'Mantenimiento rechazado: equipo restaurado a Disponible',
                                'ubicacion_fisica' => $inventario->ubicacion_fisica,
                                'fecha_cambio' => now(),
                            ]);
                        }
                    }
                }
            });
        }
    }
}
