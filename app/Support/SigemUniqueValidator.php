<?php

namespace App\Support;

use App\Models\Inventario;
use App\Models\Receptor;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Validaciones centralizadas de unicidad y similitud en 4 niveles
 * (case-insensitive, sin acentos, sin puntos, colapso de espacios, incluyendo soft-deleted):
 *  - Nivel 1: Normalizar + BLOQUEAR (duplicado exacto normalizado)
 *  - Nivel 2: Similitud (Levenshtein <= 2) + ADVERTIR (no bloquea)
 *  - Nivel 3: Abreviaciones / prefijos / contención / iniciales + SUGERIR (no bloquea)
 *  - Nivel 4: Personas (Receptor: incompleto, inicial, abreviación) + ADVERTIR (no bloquea)
 */
class SigemUniqueValidator
{
    public const ACCION_PERMITIR = 'permitir';

    public const ACCION_BLOQUEAR = 'bloquear';

    public const ACCION_ADVERTIR = 'advertir';

    public const ACCION_SUGERIR = 'sugerir';

    /** @var array<int, string> */
    private const STOPWORDS_ES = [
        'de', 'del', 'la', 'las', 'el', 'los', 'en', 'y', 'e', 'o', 'u', 'a', 'al', 'para', 'por', 'con',
    ];

    /**
     * Nivel 1: normaliza texto para comparación estricta:
     * trim -> quitar acentos (ASCII) -> minúsculas -> quitar puntos -> colapsar espacios múltiples.
     */
    public static function normalizar(?string $valor): string
    {
        $raw = trim((string) $valor);
        if ($raw === '') {
            return '';
        }

        $sinAcentos = Str::ascii($raw, 'es');
        $lower = mb_strtolower($sinAcentos, 'UTF-8');
        $sinPuntos = str_replace('.', '', $lower);
        $colapsado = (string) preg_replace('/\s+/', ' ', $sinPuntos);

        return trim($colapsado);
    }

    /**
     * Busca el primer registro (incluyendo soft-deleted) cuyo valor de columna coincida
     * tras normalización Nivel 1 (sin acentos, sin puntos, case-insensitive, espacios colapsados).
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

        return $query
            ->whereNotNull($column)
            ->get()
            ->first(fn (Model $row): bool => self::normalizar((string) $row->getAttribute($column)) === $needle);
    }

    /**
     * Verifica si $sub es subsecuencia ordenada de $target compartiendo la misma inicial
     * (ej. "depto" dentro de "departamento", "ing" dentro de "ingenieria").
     */
    protected static function esSubsecuenciaAbreviada(string $sub, string $target): bool
    {
        $lenSub = strlen($sub);
        $lenTarget = strlen($target);

        if ($lenSub < 3 || $lenSub >= $lenTarget) {
            return false;
        }

        if ($sub[0] !== $target[0]) {
            return false;
        }

        $pos = 0;
        for ($i = 0; $i < $lenSub; $i++) {
            $found = strpos($target, $sub[$i], $pos);
            if ($found === false) {
                return false;
            }
            $pos = $found + 1;
        }

        return true;
    }

    /**
     * Calcula iniciales de una frase con y sin stopwords en español.
     *
     * @return array<int, string>
     */
    protected static function extraerIniciales(string $normalizado): array
    {
        $palabras = array_values(array_filter(
            preg_split('/[\s\-_\/]+/', $normalizado) ?: [],
            fn (string $p): bool => $p !== ''
        ));

        if (count($palabras) < 2) {
            return [];
        }

        $todas = '';
        $sinStop = '';
        foreach ($palabras as $p) {
            $letra = substr($p, 0, 1);
            $todas .= $letra;
            if (! in_array($p, self::STOPWORDS_ES, true)) {
                $sinStop .= $letra;
            }
        }

        return array_values(array_unique(array_filter([$todas, $sinStop], fn (string $s): bool => strlen($s) >= 2)));
    }

    /**
     * Nivel 3: detecta si $input es abreviación, prefijo, contención, iniciales o variante léxica de $existente.
     * Retorna el tipo de coincidencia ('prefijo'|'iniciales'|'abreviacion'|'contencion'|'similitud_token') o null.
     */
    public static function detectarAbreviacion(string $input, string $existente): ?string
    {
        $in = self::normalizar($input);
        $ex = self::normalizar($existente);

        if ($in === '' || $ex === '' || $in === $ex) {
            return null;
        }

        $lenIn = strlen($in);
        $lenEx = strlen($ex);

        // 1. Iniciales / siglas: "DISC" / "D.I.S.C." vs "Depto de Ing en Sistemas y Comp"
        if ($lenIn >= 2 && ! str_contains($in, ' ')) {
            $iniciales = self::extraerIniciales($ex);
            if (in_array($in, $iniciales, true)) {
                return 'iniciales';
            }
        }

        if ($lenIn < 3) {
            return null;
        }

        // 2. Prefijo significativo (ej. "AUTOC" = 5/7 de "AUTOCAD")
        if (str_starts_with($ex, $in) && ($lenEx - $lenIn) >= 2 && ($lenIn / $lenEx) >= 0.5) {
            return 'prefijo';
        }

        // 3. Contención directa del input dentro del existente (ej. "mecanica" en "metal-mecanica")
        if (str_contains($ex, $in) && $lenIn >= 4) {
            return 'contencion';
        }

        // 4. Análisis por palabras del existente (separa por espacios y guiones)
        $palabrasExistente = array_values(array_filter(
            preg_split('/[\s\-_\/]+/', $ex) ?: [],
            fn (string $p): bool => strlen($p) >= 3 && ! in_array($p, self::STOPWORDS_ES, true)
        ));

        foreach ($palabrasExistente as $palabra) {
            // 4a. Prefijo de palabra (ej. "depto" vs "departamento" via subsecuencia, o "siste" vs "sistemas")
            if (str_starts_with($palabra, $in) && ($lenIn / strlen($palabra)) >= 0.5) {
                return 'prefijo';
            }

            // 4b. Subsecuencia abreviada con misma inicial (ej. "depto" en "departamento")
            if (! str_contains($in, ' ') && self::esSubsecuenciaAbreviada($in, $palabra)) {
                return 'abreviacion';
            }

            // 4c. Similitud léxica fuerte contra un token (ej. "mecatronica" vs "mecanica" de "Metal-Mecánica")
            if (! str_contains($in, ' ') && $lenIn >= 5 && strlen($palabra) >= 5) {
                $ratio = similar_text($in, $palabra) / max($lenIn, strlen($palabra));
                $mismoInicio = substr($in, 0, 3) === substr($palabra, 0, 3);
                if ($ratio >= 0.65 && $mismoInicio) {
                    return 'similitud_token';
                }
            }
        }

        return null;
    }

    /**
     * Análisis completo de 3 niveles para catálogos (Departamento, Área, Material, Marca, Tipo, Unidad, Proveedor).
     *
     * @param  class-string<Model>  $modelClass
     * @return array{accion: string, nivel: int, mensaje: ?string, titulo: ?string, coincidencias: array<int, array{id: int|string, nombre: string, tipo: string}>}
     */
    public static function analizarCatalogo(
        string $modelClass,
        string $column,
        ?string $value,
        string $etiquetaArticulo,
        int|string|null $ignoreId = null,
    ): array {
        $clean = trim((string) $value);
        $needle = self::normalizar($clean);

        if ($needle === '') {
            return [
                'accion' => self::ACCION_PERMITIR,
                'nivel' => 0,
                'mensaje' => null,
                'titulo' => null,
                'coincidencias' => [],
            ];
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

        $registros = $query->whereNotNull($column)->get([$pk, $column]);

        // NIVEL 1: Coincidencia exacta tras normalización -> BLOQUEAR
        foreach ($registros as $row) {
            $valorExistente = trim((string) $row->getAttribute($column));
            if ($valorExistente === '') {
                continue;
            }
            if (self::normalizar($valorExistente) === $needle) {
                return [
                    'accion' => self::ACCION_BLOQUEAR,
                    'nivel' => 1,
                    'titulo' => 'Registro duplicado',
                    'mensaje' => "Ya existe {$etiquetaArticulo} con el nombre '{$clean}'",
                    'coincidencias' => [
                        ['id' => $row->getKey(), 'nombre' => $valorExistente, 'tipo' => 'exacto'],
                    ],
                ];
            }
        }

        $advertencias = [];
        $sugerencias = [];
        $lenNeedle = strlen($needle);

        foreach ($registros as $row) {
            $valorExistente = trim((string) $row->getAttribute($column));
            $normExistente = self::normalizar($valorExistente);
            if ($normExistente === '') {
                continue;
            }

            $lenExist = strlen($normExistente);
            $dist = ($lenNeedle <= 255 && $lenExist <= 255)
                ? levenshtein($needle, $normExistente)
                : 999;

            // Distinguir prefijo truncado >= 2 chars (Nivel 3: "AUTOC" -> "AUTOCAD") de typo 1-2 chars (Nivel 2: "AUTCCAD", "autoca")
            $esPrefijoTruncado = str_starts_with($normExistente, $needle) && ($lenExist - $lenNeedle) >= 2 && $lenNeedle >= 3;

            $umbralLev = ($lenNeedle >= 5 && $lenExist >= 5) ? 2 : 1;

            if (! $esPrefijoTruncado && $lenNeedle >= 3 && $lenExist >= 3 && $dist > 0 && $dist <= $umbralLev) {
                $advertencias[] = [
                    'id' => $row->getKey(),
                    'nombre' => $valorExistente,
                    'tipo' => 'levenshtein',
                    'score' => $dist,
                ];
                continue;
            }

            $tipoAbrev = self::detectarAbreviacion($needle, $normExistente);
            if ($tipoAbrev === 'similitud_token') {
                $advertencias[] = [
                    'id' => $row->getKey(),
                    'nombre' => $valorExistente,
                    'tipo' => 'similitud_token',
                    'score' => 3,
                ];
            } elseif ($tipoAbrev !== null) {
                $sugerencias[] = [
                    'id' => $row->getKey(),
                    'nombre' => $valorExistente,
                    'tipo' => $tipoAbrev,
                ];
            }
        }

        // NIVEL 2: Similitud (Levenshtein / typo / token cercano) -> ADVERTIR
        if (! empty($advertencias)) {
            usort($advertencias, fn (array $a, array $b): int => $a['score'] <=> $b['score']);
            $top = array_slice($advertencias, 0, 5);
            $primero = $top[0]['nombre'];

            return [
                'accion' => self::ACCION_ADVERTIR,
                'nivel' => 2,
                'titulo' => "¿Quisiste decir \"{$primero}\"?",
                'mensaje' => "Ya existe un registro similar: \"{$primero}\" (ID: #{$top[0]['id']}).",
                'coincidencias' => array_map(
                    fn (array $item): array => ['id' => $item['id'], 'nombre' => $item['nombre'], 'tipo' => $item['tipo']],
                    $top
                ),
            ];
        }

        // NIVEL 3: Abreviación / prefijo / contención / iniciales -> SUGERIR
        if (! empty($sugerencias)) {
            $top = array_slice($sugerencias, 0, 5);
            $primero = $top[0]['nombre'];

            return [
                'accion' => self::ACCION_SUGERIR,
                'nivel' => 3,
                'titulo' => "¿Quisiste decir \"{$primero}\"?",
                'mensaje' => "Encontramos registros similares que coinciden con \"{$clean}\".",
                'coincidencias' => $top,
            ];
        }

        return [
            'accion' => self::ACCION_PERMITIR,
            'nivel' => 0,
            'mensaje' => null,
            'titulo' => null,
            'coincidencias' => [],
        ];
    }

    /**
     * Análisis de 2 niveles para No. de serie (num_serie) en Inventario:
     *  - Nivel 1: duplicado exacto normalizado (incluyendo soft-deleted) -> BLOQUEAR
     *  - Nivel 2: Levenshtein <= 2 -> ADVERTIR
     *  - Vacío -> PERMITIR (opcional)
     *
     * @return array{accion: string, nivel: int, mensaje: ?string, titulo: ?string, coincidencias: array<int, array{id: int|string, nombre: string, codigo: string, tipo: string}>}
     */
    public static function analizarSerieInventario(?string $value, int|string|null $ignoreId = null): array
    {
        $clean = trim((string) $value);
        $needle = self::normalizar($clean);

        if ($needle === '') {
            return [
                'accion' => self::ACCION_PERMITIR,
                'nivel' => 0,
                'mensaje' => null,
                'titulo' => null,
                'coincidencias' => [],
            ];
        }

        $query = Inventario::withTrashed()->whereNotNull('num_serie');
        if ($ignoreId !== null && $ignoreId !== '') {
            $query->where('id_inventario', '!=', $ignoreId);
        }

        $equipos = $query->get(['id_inventario', 'codigo', 'num_serie']);

        // Nivel 1: exacto normalizado -> BLOQUEAR
        foreach ($equipos as $eq) {
            $sn = trim((string) ($eq->num_serie ?? ''));
            if ($sn === '') {
                continue;
            }
            if (self::normalizar($sn) === $needle) {
                $codigoRef = filled($eq->codigo) ? (string) $eq->codigo : ('#' . $eq->id_inventario);

                return [
                    'accion' => self::ACCION_BLOQUEAR,
                    'nivel' => 1,
                    'titulo' => 'Número de serie duplicado',
                    'mensaje' => "Este número de serie ({$clean}) ya está registrado en {$codigoRef}",
                    'coincidencias' => [
                        ['id' => $eq->id_inventario, 'nombre' => $sn, 'codigo' => $codigoRef, 'tipo' => 'exacto'],
                    ],
                ];
            }
        }

        // Nivel 2: Levenshtein <= 2 (para series de >= 4 caracteres) -> ADVERTIR
        $similares = [];
        $lenNeedle = strlen($needle);

        if ($lenNeedle >= 4 && $lenNeedle <= 255) {
            foreach ($equipos as $eq) {
                $sn = trim((string) ($eq->num_serie ?? ''));
                $normSn = self::normalizar($sn);
                if ($normSn === '' || strlen($normSn) < 4 || strlen($normSn) > 255) {
                    continue;
                }

                $dist = levenshtein($needle, $normSn);
                if ($dist > 0 && $dist <= 2) {
                    $codigoRef = filled($eq->codigo) ? (string) $eq->codigo : ('#' . $eq->id_inventario);
                    $similares[] = [
                        'id' => $eq->id_inventario,
                        'nombre' => $sn,
                        'codigo' => $codigoRef,
                        'tipo' => 'levenshtein',
                        'score' => $dist,
                    ];
                }
            }
        }

        if (! empty($similares)) {
            usort($similares, fn (array $a, array $b): int => $a['score'] <=> $b['score']);
            $top = array_slice($similares, 0, 5);
            $primero = $top[0];

            return [
                'accion' => self::ACCION_ADVERTIR,
                'nivel' => 2,
                'titulo' => "¿Quisiste decir \"{$primero['nombre']}\"?",
                'mensaje' => "Ya existe un equipo con número de serie muy similar: \"{$primero['nombre']}\" ({$primero['codigo']}).",
                'coincidencias' => array_map(
                    fn (array $item): array => [
                        'id' => $item['id'],
                        'nombre' => $item['nombre'],
                        'codigo' => $item['codigo'],
                        'tipo' => $item['tipo'],
                    ],
                    $top
                ),
            ];
        }

        return [
            'accion' => self::ACCION_PERMITIR,
            'nivel' => 0,
            'mensaje' => null,
            'titulo' => null,
            'coincidencias' => [],
        ];
    }

    /**
     * Análisis especial de personas (Receptor):
     *  - Nivel 1: nombre completo idéntico normalizado (sin acentos/puntos/mayúsculas) -> BLOQUEAR
     *  - Nivel 2: typo Levenshtein <= 2 en nombre completo -> ADVERTIR
     *  - Nivel 4a: nombre incompleto (mismos apellidos con nombre vacío, o mismo nombre + apellido paterno con materno faltante) -> ADVERTIR
     *  - Nivel 4b: abreviación / inicial de nombre ("R." vs "Roberto") con mismos apellidos -> ADVERTIR
     *
     * @return array{accion: string, nivel: int|string, mensaje: ?string, titulo: ?string, coincidencias: array<int, array{id: int|string, nombre: string, tipo: string}>}
     */
    public static function analizarReceptor(
        ?string $nombre,
        ?string $apellidoPaterno = null,
        ?string $apellidoMaterno = null,
        int|string|null $ignoreId = null,
    ): array {
        $n = self::normalizar($nombre);
        $ap = self::normalizar($apellidoPaterno);
        $am = self::normalizar($apellidoMaterno);

        $completo = trim(implode(' ', array_filter([$n, $ap, $am], fn (string $p): bool => $p !== '')));
        if ($completo === '') {
            return [
                'accion' => self::ACCION_PERMITIR,
                'nivel' => 0,
                'mensaje' => null,
                'titulo' => null,
                'coincidencias' => [],
            ];
        }

        $nombreCompletoDisplay = trim(implode(' ', array_filter([
            trim((string) $nombre),
            trim((string) $apellidoPaterno),
            trim((string) $apellidoMaterno),
        ], fn (string $p): bool => $p !== '')));

        $query = Receptor::withTrashed();
        if ($ignoreId !== null && $ignoreId !== '') {
            $query->where('id_receptor', '!=', $ignoreId);
        }

        $existentes = $query->get(['id_receptor', 'nombre', 'apellido_paterno', 'apellido_materno']);

        // Nivel 1: Exacto normalizado -> BLOQUEAR
        foreach ($existentes as $e) {
            $en = self::normalizar($e->nombre);
            $eap = self::normalizar($e->apellido_paterno);
            $eam = self::normalizar($e->apellido_materno);
            $eCompleto = trim(implode(' ', array_filter([$en, $eap, $eam], fn (string $p): bool => $p !== '')));

            if ($eCompleto !== '' && $completo === $eCompleto) {
                return [
                    'accion' => self::ACCION_BLOQUEAR,
                    'nivel' => 1,
                    'titulo' => 'Receptor duplicado',
                    'mensaje' => "Ya existe un receptor con el nombre '{$nombreCompletoDisplay}'",
                    'coincidencias' => [
                        ['id' => $e->id_receptor, 'nombre' => $e->nombre_completo, 'tipo' => 'exacto'],
                    ],
                ];
            }
        }

        $advertencias = [];

        foreach ($existentes as $e) {
            $en = self::normalizar($e->nombre);
            $eap = self::normalizar($e->apellido_paterno);
            $eam = self::normalizar($e->apellido_materno);
            $eCompleto = trim(implode(' ', array_filter([$en, $eap, $eam], fn (string $p): bool => $p !== '')));
            if ($eCompleto === '') {
                continue;
            }

            // Nivel 2: Levenshtein <= 2 en nombre completo (ej. "Roberto Pérez Garza" vs "Roberto Pérez García")
            if (strlen($completo) <= 255 && strlen($eCompleto) <= 255 && levenshtein($completo, $eCompleto) <= 2) {
                $advertencias[] = [
                    'id' => $e->id_receptor,
                    'nombre' => $e->nombre_completo,
                    'tipo' => 'levenshtein',
                    'nivel' => 2,
                    'titulo' => "¿Quisiste decir \"{$e->nombre_completo}\"?",
                    'mensaje' => "Ya existe un receptor con nombre muy similar: \"{$e->nombre_completo}\" (ID: #{$e->id_receptor}).",
                ];
                continue;
            }

            // Nivel 4b: Abreviación / inicial de nombre ("R." vs "Roberto") con mismos apellidos
            $inicialIn = $n !== '' ? substr($n, 0, 1) : '';
            $inicialEx = $en !== '' ? substr($en, 0, 1) : '';
            $esAbrevNombre = $n !== '' && $en !== '' && $n !== $en && $inicialIn === $inicialEx && (
                strlen($n) === 1
                || strlen($en) === 1
                || str_starts_with($en, $n)
                || str_starts_with($n, $en)
            );
            if ($esAbrevNombre && $ap !== '' && $ap === $eap && ($am === $eam || $am === '' || $eam === '')) {
                $advertencias[] = [
                    'id' => $e->id_receptor,
                    'nombre' => $e->nombre_completo,
                    'tipo' => 'abreviacion_nombre',
                    'nivel' => '4b',
                    'titulo' => "¿Es \"{$e->nombre_completo}\"?",
                    'mensaje' => "Detectamos una posible abreviación de nombre para \"{$e->nombre_completo}\" (ID: #{$e->id_receptor}).",
                ];
                continue;
            }

            // Nivel 4a-1: Falta nombre o mismos dos apellidos completos (ej. "" + "Pérez" + "García")
            $mismosDosApellidos = $ap !== '' && $am !== '' && $ap === $eap && $am === $eam;
            if ($mismosDosApellidos && ($n === '' || $en === '')) {
                $advertencias[] = [
                    'id' => $e->id_receptor,
                    'nombre' => $e->nombre_completo,
                    'tipo' => 'falta_nombre',
                    'nivel' => '4a',
                    'titulo' => "¿Es la misma persona que \"{$e->nombre_completo}\"?",
                    'mensaje' => "Los apellidos coinciden exactamente con \"{$e->nombre_completo}\" (ID: #{$e->id_receptor}).",
                ];
                continue;
            }

            // Nivel 4a-2: Nombre incompleto (mismo nombre + apellido paterno, falta materno en alguno)
            if ($n !== '' && $ap !== '' && $n === $en && $ap === $eap && ($am === '' || $eam === '')) {
                $advertencias[] = [
                    'id' => $e->id_receptor,
                    'nombre' => $e->nombre_completo,
                    'tipo' => 'nombre_incompleto',
                    'nivel' => '4a',
                    'titulo' => "¿Es la misma persona que \"{$e->nombre_completo}\"?",
                    'mensaje' => "El nombre y apellido paterno coinciden con \"{$e->nombre_completo}\" (ID: #{$e->id_receptor}).",
                ];
                continue;
            }
        }

        if (! empty($advertencias)) {
            $primero = $advertencias[0];

            return [
                'accion' => self::ACCION_ADVERTIR,
                'nivel' => $primero['nivel'],
                'titulo' => $primero['titulo'],
                'mensaje' => $primero['mensaje'],
                'coincidencias' => array_map(
                    fn (array $a): array => ['id' => $a['id'], 'nombre' => $a['nombre'], 'tipo' => $a['tipo']],
                    array_slice($advertencias, 0, 5)
                ),
            ];
        }

        return [
            'accion' => self::ACCION_PERMITIR,
            'nivel' => 0,
            'mensaje' => null,
            'titulo' => null,
            'coincidencias' => [],
        ];
    }

    /**
     * Renderiza el bloque HTML de advertencia (Nivel 2 / 4) o sugerencia (Nivel 3) para formularios Filament.
     */
    public static function renderAlertaHtml(array $analisis): HtmlString
    {
        $accion = $analisis['accion'] ?? self::ACCION_PERMITIR;
        if (! in_array($accion, [self::ACCION_ADVERTIR, self::ACCION_SUGERIR], true)) {
            return new HtmlString('');
        }

        $esSugerencia = $accion === self::ACCION_SUGERIR;
        $clase = $esSugerencia ? 'is-suggest' : 'is-warn';
        $icono = $esSugerencia ? '💡' : '⚠️';
        $titulo = e((string) ($analisis['titulo'] ?? 'Registro similar detectado'));
        $mensaje = e((string) ($analisis['mensaje'] ?? ''));

        $itemsHtml = '';
        foreach (($analisis['coincidencias'] ?? []) as $item) {
            $nom = e((string) ($item['nombre'] ?? ''));
            $meta = isset($item['codigo'])
                ? e((string) $item['codigo'])
                : ('ID: #' . e((string) ($item['id'] ?? '')));
            $itemsHtml .= '<li class="sigem-sim-item"><strong>' . $nom . '</strong> <span>(' . $meta . ')</span></li>';
        }

        return new HtmlString(
            '<div class="sigem-sim-alert ' . $clase . '" role="status">'
            . '<div class="sigem-sim-head">'
            . '<span class="sigem-sim-ico">' . $icono . '</span>'
            . '<strong class="sigem-sim-title">' . $titulo . '</strong>'
            . '</div>'
            . ($mensaje !== '' ? '<p class="sigem-sim-msg">' . $mensaje . '</p>' : '')
            . ($itemsHtml !== '' ? '<ul class="sigem-sim-list">' . $itemsHtml . '</ul>' : '')
            . '<div class="sigem-sim-foot">Puedes corregir el texto o continuar con <strong>Guardar</strong> de todas formas si es un registro distinto.</div>'
            . '</div>'
        );
    }

    // =========================================================================
    // MÉTODOS DE COMPATIBILIDAD (Nivel 1 — Bloqueo estricto en Filament y Services)
    // =========================================================================

    /**
     * Valida unicidad de num_serie en inventario (incluye soft-deleted, normalizado, permite vacío).
     * Retorna el mensaje de error (Nivel 1) o null si es válido/permitido.
     */
    public static function validarSerieInventario(?string $value, int|string|null $ignoreId = null): ?string
    {
        $analisis = self::analizarSerieInventario($value, $ignoreId);

        return $analisis['accion'] === self::ACCION_BLOQUEAR ? $analisis['mensaje'] : null;
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
     * Valida unicidad de nombre en cualquier catálogo (Nivel 1: normalizado, incluye soft-deleted).
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
        $analisis = self::analizarCatalogo($modelClass, $column, $value, $etiquetaArticulo, $ignoreId);

        return $analisis['accion'] === self::ACCION_BLOQUEAR ? $analisis['mensaje'] : null;
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
     * Valida unicidad de Receptor por nombre completo (Nivel 1: normalizado, incluye soft-deleted).
     */
    public static function validarReceptorUnico(
        ?string $nombre,
        ?string $apellidoPaterno = null,
        ?string $apellidoMaterno = null,
        int|string|null $ignoreId = null,
    ): ?string {
        if (self::normalizar($nombre) === '') {
            return null;
        }

        $analisis = self::analizarReceptor($nombre, $apellidoPaterno, $apellidoMaterno, $ignoreId);

        return $analisis['accion'] === self::ACCION_BLOQUEAR ? $analisis['mensaje'] : null;
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
