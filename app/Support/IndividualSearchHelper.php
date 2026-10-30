<?php

namespace App\Support;

use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Solicitud;
use App\Models\TipoMaterial;
use Illuminate\Support\Facades\Schema;

class IndividualSearchHelper
{
    /**
     * Devuelve las opciones para una columna tipo enum/relación, o null si debe ser input de texto.
     */
    public static function getOptionsForColumn(string $columnName, ?string $model = null): ?array
    {
        // 1. Inventario
        if ($model === Inventario::class || $model === 'App\\Models\\Inventario' || str_ends_with((string) $model, 'Inventario')) {
            if ($columnName === 'material.tipo.nombre' || $columnName === 'id_tipodematerial' || $columnName === 'tipo') {
                $tipos = [];
                try {
                    if (Schema::hasTable('tiposdematerial')) {
                        $tipos = TipoMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
                    }
                } catch (\Throwable $e) {
                    $tipos = [];
                }

                return ['' => 'Todas'] + $tipos;
            }

            if ($columnName === 'estado') {
                return [
                    '' => 'Todos',
                    'Disponible' => 'Disponible',
                    'Asignado' => 'Asignado',
                    'Reasignado' => 'Reasignado',
                    'En Mantenimiento' => 'En Mantenimiento',
                    'Dañado' => 'Dañado',
                    'Baja' => 'Baja',
                ];
            }

            if ($columnName === 'tipo_propiedad') {
                return [
                    '' => 'Todas',
                    'Propio' => 'Propio',
                    'Rentado' => 'Rentado',
                ];
            }
        }

        // 2. Solicitudes
        if ($model === Solicitud::class || $model === 'App\\Models\\Solicitud' || str_ends_with((string) $model, 'Solicitud')) {
            if ($columnName === 'tipo_movimiento') {
                return [
                    '' => 'Todos',
                    'Asignacion Temporal' => 'Asignación Temporal',
                    'Asignacion Permanente' => 'Asignación Permanente',
                    'Renta Externa' => 'Renta Externa',
                ];
            }

            if ($columnName === 'estado') {
                return [
                    '' => 'Todos',
                    'Pendiente' => 'Pendiente',
                    'Autorizado' => 'Autorizado',
                    'Rechazado' => 'Rechazado',
                    'Completado' => 'Completado',
                    'Cancelado' => 'Cancelado',
                ];
            }
        }

        // 3. Mantenimientos
        if ($model === Mantenimiento::class || $model === 'App\\Models\\Mantenimiento' || str_ends_with((string) $model, 'Mantenimiento')) {
            if ($columnName === 'tipo_mantenimiento') {
                return [
                    '' => 'Todos',
                    'Preventivo' => 'Preventivo',
                    'Correctivo' => 'Correctivo',
                    'Mejora' => 'Mejora',
                ];
            }

            if ($columnName === 'estado') {
                return [
                    '' => 'Todos',
                    'Solicitado' => 'Solicitado',
                    'En proceso' => 'En proceso',
                    'Pendiente Revision Admin' => 'En revisión',
                    'Completado' => 'Completado',
                    'Cancelado' => 'Cancelado',
                ];
            }
        }

        // Fallbacks por nombre de columna si el modelo no está definido
        if ($columnName === 'tipo_propiedad') {
            return [
                '' => 'Todas',
                'Propio' => 'Propio',
                'Rentado' => 'Rentado',
            ];
        }

        if ($columnName === 'tipo_movimiento') {
            return [
                '' => 'Todos',
                'Asignacion Temporal' => 'Asignación Temporal',
                'Asignacion Permanente' => 'Asignación Permanente',
                'Renta Externa' => 'Renta Externa',
            ];
        }

        if ($columnName === 'tipo_mantenimiento') {
            return [
                '' => 'Todos',
                'Preventivo' => 'Preventivo',
                'Correctivo' => 'Correctivo',
                'Mejora' => 'Mejora',
            ];
        }

        return null;
    }

    /**
     * Devuelve el placeholder contextual para una columna de texto.
     */
    public static function getPlaceholderForColumn(string $columnName, ?string $model = null): string
    {
        if ($model === Inventario::class || $model === 'App\\Models\\Inventario' || str_ends_with((string) $model, 'Inventario')) {
            return match ($columnName) {
                'num_serie' => 'ej. BM6R...',
                'material.nombre' => 'Filtrar...',
                'ubicacion_fisica' => 'ej. Aulas de cómputo',
                default => 'Filtrar...',
            };
        }

        if ($model === Solicitud::class || $model === 'App\\Models\\Solicitud' || str_ends_with((string) $model, 'Solicitud')) {
            return match ($columnName) {
                'id_solicitud' => 'ej. FOLIO-00001',
                'usuario.name' => 'ej. Juan Pérez',
                'receptor.nombre' => 'ej. Alejandro',
                'receptor.area.departamento.nombre' => 'ej. Sistemas',
                'fecha_solicitud' => 'ej. dd/mm/aaaa',
                default => 'Filtrar...',
            };
        }

        if ($model === Mantenimiento::class || $model === 'App\\Models\\Mantenimiento' || str_ends_with((string) $model, 'Mantenimiento')) {
            return match ($columnName) {
                'inventario.num_serie' => 'ej. DELL-OPT...',
                'inventario.material.nombre' => 'ej. DELL-OPT...',
                'tipo_servicio' => 'ej. Servicio Social',
                'nombre_tecnico' => 'ej. Juan Pérez',
                'fecha_solicitud' => 'ej. dd/mm/aaaa',
                default => 'Filtrar...',
            };
        }

        return match ($columnName) {
            'num_serie' => 'ej. BM6R...',
            'id_solicitud' => 'ej. FOLIO-00001',
            'usuario.name' => 'ej. Juan Pérez',
            'receptor.nombre' => 'ej. Alejandro',
            'receptor.area.departamento.nombre' => 'ej. Sistemas',
            'tipo_servicio' => 'ej. Servicio Social',
            'fecha_solicitud' => 'ej. dd/mm/aaaa',
            'nombre_tecnico' => 'ej. Juan Pérez',
            'ubicacion_fisica' => 'ej. Aulas de cómputo',
            default => 'Filtrar...',
        };
    }
}

