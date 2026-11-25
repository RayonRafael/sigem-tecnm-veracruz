<?php

namespace App\Support;

use App\Models\Inventario;
use App\Models\Receptor;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Validaciones centralizadas de unicidad (case-insensitive e incluyendo soft-deleted)
 * para números de serie de Inventario y nombres en los 8 catálogos institucionales.
 */
class SigemUniqueValidator
{
    public static function normalizar(?string $valor): string
    {
        return mb_strtolower(trim((string) $valor), 'UTF-8');
    }

    /**
     * Busca el primer registro (incluyendo soft-deleted) cuyo valor de columna coincida
     * de forma case-insensitive y sin espacios laterales.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function buscarPorValorNormalizado(
        string $modelClass,
        string $column,
        ?string $value,
        int|string|null $ignoreId = null,
    ): ?Model {
        $needle = self::normalizar($value);
        if ($needle === '') {
            return null;
        }

        /** @var Model $instance */
        $instance = new $modelClass();
        $pk = $instance->getKeyName();

        $usaSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
        $query = $usaSoftDeletes
            ? $modelClass::withTrashed()
            : $modelClass::query();

        if ($ignoreId !== null && $ignoreId !== '') {
            $query->where($pk, '!=', $ignoreId);
        }

        if (DB::getDriverName() !== 'sqlite') {
            return $query
                ->whereNotNull($column)
                ->whereRaw("LOWER(TRIM({$column})) = ?", [$needle])
                ->first();
        }

        // En SQLite el LOWER() nativo solo convierte ASCII; comparamos en UTF-8 con mb_strtolower
        return $query
            ->whereNotNull($column)
            ->get()
            ->first(fn (Model $row): bool => self::normalizar((string) $row->getAttribute($column)) === $needle);
    }

    /**
     * Valida unicidad de num_serie en inventario (incluye soft-deleted, case-insensitive, permite vacío).
     * Retorna el mensaje de error o null si es válido.
     */
    public static function validarSerieInventario(?string $value, int|string|null $ignoreId = null): ?string
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        /** @var ?Inventario $existing */
        $existing = self::buscarPorValorNormalizado(Inventario::class, 'num_serie', $clean, $ignoreId);
        if (! $existing) {
            return null;
        }

        $codigoRef = filled($existing->codigo) ? $existing->codigo : ('#' . $existing->id_inventario);

        return "Este número de serie ({$clean}) ya está registrado en {$codigoRef}";
    }

    /**
     * Valida un lote de números de serie (registro múltiple PC/AIO o individual):
     * verifica duplicados dentro del mismo envío y contra la BD (incluyendo soft-deleted).
     *
     * @param  array<string, ?string>  $seriesPorCampo  Mapa ['campo' => 'valor_sn']
     * @return array<string, string>  Mapa de errores ['campo' => 'mensaje']
     */
    public static function validarSeriesLote(array $seriesPorCampo, int|string|null $ignoreId = null): array
    {
        $errores = [];
        $vistos = [];

        foreach ($seriesPorCampo as $campo => $valor) {
            $clean = trim((string) $valor);
            if ($clean === '') {
                continue;
            }

            $norm = self::normalizar($clean);
            if (isset($vistos[$norm])) {
                $errores[$campo] = "El número de serie ({$clean}) está repetido en otro componente del mismo registro";
                continue;
            }
            $vistos[$norm] = $campo;

            if ($msg = self::validarSerieInventario($clean, $ignoreId)) {
                $errores[$campo] = $msg;
            }
        }

        return $errores;
    }

    /**
     * Regla de Filament para campos num_serie en formularios de creación/edición de Inventario.
     *
     * @param  array<int, array{field: string, skipIf?: string, onlyTypes?: array<int, string>}>  $camposHermanos
     */
    public static function reglaSerieUnica(array $camposHermanos = []): \Closure
    {
        return function (Forms\Get $get, ?Model $record = null) use ($camposHermanos): \Closure {
            return function (string $attribute, $value, \Closure $fail) use ($get, $record, $camposHermanos): void {
                $clean = trim((string) $value);
                if ($clean === '') {
                    return;
                }

                $ignoreId = $record instanceof Inventario ? $record->getKey() : null;
                if ($error = self::validarSerieInventario($clean, $ignoreId)) {
                    $fail($error);

                    return;
                }

                if (empty($camposHermanos)) {
                    return;
                }

                $norm = self::normalizar($clean);
                $tipoActual = (string) ($get('tipo_equipo') ?? 'pc');

                foreach ($camposHermanos as $hermano) {
                    if (! empty($hermano['onlyTypes']) && ! in_array($tipoActual, $hermano['onlyTypes'], true)) {
                        continue;
                    }
                    if (! empty($hermano['skipIf']) && (bool) $get($hermano['skipIf'])) {
                        continue;
                    }
                    $otroValor = trim((string) $get($hermano['field']));
                    if ($otroValor !== '' && self::normalizar($otroValor) === $norm) {
                        $fail("El número de serie ({$clean}) está repetido en otro componente del mismo registro");

                        return;
                    }
                }
            };
        };
    }

    /**
     * Valida unicidad de nombre en cualquier catálogo (incluye soft-deleted, case-insensitive).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function validarCatalogoUnico(
        string $modelClass,
        string $column,
        ?string $value,
        string $etiquetaArticulo,
        int|string|null $ignoreId = null,
    ): ?string {
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        $existing = self::buscarPorValorNormalizado($modelClass, $column, $clean, $ignoreId);
        if (! $existing) {
            return null;
        }

        return "Ya existe {$etiquetaArticulo} con el nombre '{$clean}'";
    }

    /**
     * Regla de Filament para validar unicidad de nombre en formularios de catálogos.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function reglaCatalogoUnico(
        string $modelClass,
        string $etiquetaArticulo,
        string $column = 'nombre',
        bool $ignorarRecord = true,
    ): \Closure {
        return function (?Model $record = null) use ($modelClass, $etiquetaArticulo, $column, $ignorarRecord): \Closure {
            return function (string $attribute, $value, \Closure $fail) use ($record, $modelClass, $etiquetaArticulo, $column, $ignorarRecord): void {
                $ignoreId = ($ignorarRecord && $record instanceof $modelClass) ? $record->getKey() : null;
                if ($error = self::validarCatalogoUnico($modelClass, $column, is_scalar($value) ? (string) $value : null, $etiquetaArticulo, $ignoreId)) {
                    $fail($error);
                }
            };
        };
    }

    /**
     * Valida unicidad de Receptor por nombre completo (nombre + apellido_paterno + apellido_materno),
     * case-insensitive e incluyendo soft-deleted.
     */
    public static function validarReceptorUnico(
        ?string $nombre,
        ?string $apellidoPaterno = null,
        ?string $apellidoMaterno = null,
        int|string|null $ignoreId = null,
    ): ?string {
        $n = self::normalizar($nombre);
        $ap = self::normalizar($apellidoPaterno);
        $am = self::normalizar($apellidoMaterno);

        if ($n === '') {
            return null;
        }

        $query = Receptor::withTrashed();
        if ($ignoreId !== null && $ignoreId !== '') {
            $query->where('id_receptor', '!=', $ignoreId);
        }

        if (DB::getDriverName() !== 'sqlite') {
            $existing = $query
                ->whereRaw("LOWER(TRIM(COALESCE(nombre, ''))) = ?", [$n])
                ->whereRaw("LOWER(TRIM(COALESCE(apellido_paterno, ''))) = ?", [$ap])
                ->whereRaw("LOWER(TRIM(COALESCE(apellido_materno, ''))) = ?", [$am])
                ->first();
        } else {
            $existing = $query->get()->first(function (Receptor $r) use ($n, $ap, $am): bool {
                return self::normalizar($r->nombre) === $n
                    && self::normalizar($r->apellido_paterno) === $ap
                    && self::normalizar($r->apellido_materno) === $am;
            });
        }

        if (! $existing) {
            return null;
        }

        $nombreCompleto = trim(implode(' ', array_filter([
            trim((string) $nombre),
            trim((string) $apellidoPaterno),
            trim((string) $apellidoMaterno),
        ], fn (string $p): bool => $p !== '')));

        return "Ya existe un receptor con el nombre '{$nombreCompleto}'";
    }

    /**
     * Regla de Filament para validar unicidad de Receptor (nombre completo).
     */
    public static function reglaReceptorUnico(): \Closure
    {
        return function (Forms\Get $get, ?Model $record = null): \Closure {
            return function (string $attribute, $value, \Closure $fail) use ($get, $record): void {
                $ignoreId = $record instanceof Receptor ? $record->getKey() : null;
                $error = self::validarReceptorUnico(
                    is_scalar($value) ? (string) $value : null,
                    is_scalar($get('apellido_paterno')) ? (string) $get('apellido_paterno') : null,
                    is_scalar($get('apellido_materno')) ? (string) $get('apellido_materno') : null,
                    $ignoreId,
                );

                if ($error !== null) {
                    $fail($error);
                }
            };
        };
    }
}
