<?php

namespace App\Support;

use App\Models\Area;
use App\Models\Departamento;
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
                return self::getTiposMaterial();
            }

            if ($columnName === 'estado') {
                return self::getEstadosInventario();
            }

            if ($columnName === 'tipo_propiedad') {
                return self::getTiposPropiedad();
            }

            if ($columnName === 'ubicacion_fisica') {
                return self::getAreas();
            }
        }

        // 2. Solicitudes
        if ($model === Solicitud::class || $model === 'App\\Models\\Solicitud' || str_ends_with((string) $model, 'Solicitud')) {
            if ($columnName === 'tipo_movimiento') {
                return self::getTiposMovimiento();
            }

            if ($columnName === 'estado') {
                return self::getEstadosSolicitud();
            }

            if ($columnName === 'receptor.area.departamento.nombre' || $columnName === 'departamento') {
                return self::getDepartamentos();
            }
        }

        // 3. Mantenimientos
        if ($model === Mantenimiento::class || $model === 'App\\Models\\Mantenimiento' || str_ends_with((string) $model, 'Mantenimiento')) {
            if ($columnName === 'tipo_mantenimiento') {
                return self::getTiposMantenimiento();
            }

            if ($columnName === 'estado') {
                return self::getEstadosMantenimiento();
            }
        }

        // Fallbacks por nombre de columna si el modelo no está definido
        if ($columnName === 'tipo_propiedad') {
            return self::getTiposPropiedad();
        }

        if ($columnName === 'tipo_movimiento') {
            return self::getTiposMovimiento();
        }

        if ($columnName === 'tipo_mantenimiento') {
            return self::getTiposMantenimiento();
        }

        if ($columnName === 'ubicacion_fisica') {
            return self::getAreas();
        }

        if ($columnName === 'receptor.area.departamento.nombre' || $columnName === 'departamento') {
            return self::getDepartamentos();
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
                default => 'Filtrar...',
            };
        }

        if ($model === Solicitud::class || $model === 'App\\Models\\Solicitud' || str_ends_with((string) $model, 'Solicitud')) {
            return match ($columnName) {
                'id_solicitud' => 'ej. FOLIO-00001',
                'usuario.name' => 'ej. Juan Pérez',
                'receptor.nombre' => 'ej. Alejandro',
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
            'tipo_servicio' => 'ej. Servicio Social',
            'fecha_solicitud' => 'ej. dd/mm/aaaa',
            'nombre_tecnico' => 'ej. Juan Pérez',
            default => 'Filtrar...',
        };
    }

    private static function getTiposMaterial(): array
    {
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

    private static function getEstadosInventario(): array
    {
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

    private static function getTiposPropiedad(): array
    {
        return [
            '' => 'Todas',
            'Propio' => 'Propio',
            'Rentado' => 'Rentado',
        ];
    }

    private static function getAreas(): array
    {
        $areas = [];
        try {
            if (Schema::hasTable('area')) {
                $areas = Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
            }
        } catch (\Throwable $e) {
            $areas = [];
        }

        return ['' => 'Todas'] + $areas;
    }

    private static function getTiposMovimiento(): array
    {
        return [
            '' => 'Todos',
            'Asignacion Temporal' => 'Asignación Temporal',
            'Asignacion Permanente' => 'Asignación Permanente',
            'Renta Externa' => 'Renta Externa',
        ];
    }

    private static function getEstadosSolicitud(): array
    {
        return [
            '' => 'Todos',
            'Pendiente' => 'Pendiente',
            'Autorizado' => 'Autorizado',
            'Rechazado' => 'Rechazado',
            'Entregado' => 'Entregado',
            'Completado' => 'Completado',
            'Cancelado' => 'Cancelado',
        ];
    }

    private static function getDepartamentos(): array
    {
        $deptos = [];
        try {
            if (Schema::hasTable('departamento')) {
                $deptos = Departamento::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
            }
        } catch (\Throwable $e) {
            $deptos = [];
        }

        return ['' => 'Todos'] + $deptos;
    }

    private static function getTiposMantenimiento(): array
    {
        return [
            '' => 'Todos',
            'Preventivo' => 'Preventivo',
            'Correctivo' => 'Correctivo',
            'Mejora' => 'Mejora',
        ];
    }

    private static function getEstadosMantenimiento(): array
    {
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
