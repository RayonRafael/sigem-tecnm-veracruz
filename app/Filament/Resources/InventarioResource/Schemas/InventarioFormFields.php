<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Receptor;
use App\Support\SigemUniqueValidator;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Carbon;

trait InventarioFormFields
{
    /**
     * Regla de unicidad para No. de serie (Nivel 1: normalizado, incluye soft-deleted, permite vacío).
     *
     * @param  array<int, array{field: string, skipIf?: string, onlyTypes?: array<int, string>}>  $camposHermanos
     */
    protected static function getNumSerieUniqueRule(array $camposHermanos = []): \Closure
    {
        return SigemUniqueValidator::reglaSerieUnica($camposHermanos);
    }

    /**
     * Banner reactivo no-bloqueante de advertencia (Nivel 2: Levenshtein <= 2)
     * cuando el No. de serie capturado es muy similar a uno ya existente.
     */
    protected static function getNumSerieSimilarAlert(string $fieldName, ?\Closure $extraVisible = null): Forms\Components\Placeholder
    {
        $resolver = function (Forms\Get $get, ?Inventario $record = null) use ($fieldName): array {
            $val = $get($fieldName);
            if (! is_scalar($val) || trim((string) $val) === '') {
                return ['accion' => SigemUniqueValidator::ACCION_PERMITIR];
            }

            return SigemUniqueValidator::analizarSerieInventario((string) $val, $record?->getKey());
        };

        return Forms\Components\Placeholder::make('alerta_sn_similar_' . str_replace('.', '_', $fieldName))
            ->label('')
            ->hiddenLabel()
            ->visible(function (Forms\Get $get, ?Inventario $record = null) use ($resolver, $extraVisible): bool {
                if ($extraVisible !== null && ! $extraVisible($get)) {
                    return false;
                }

                $analisis = $resolver($get, $record);

                return ($analisis['accion'] ?? '') === SigemUniqueValidator::ACCION_ADVERTIR;
            })
            ->content(fn (Forms\Get $get, ?Inventario $record = null) => SigemUniqueValidator::renderAlertaHtml($resolver($get, $record)))
            ->columnSpanFull();
    }

    public static function getRentaFechaFinField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('renta_fecha_fin')
            ->label('Fecha fin de renta')
            ->prefixIcon('heroicon-m-calendar-days')
            ->visible(fn (Forms\Get $get): bool => $get('tipo_propiedad') === 'Rentado')
            ->helperText(function (Forms\Get $get, ?Inventario $record = null): string {
                $inicioRaw = $get('fecha_registro') ?: $record?->fecha_registro;
                $inicio = $inicioRaw ? Carbon::parse($inicioRaw) : now();
                $max = $inicio->copy()->addYears(3)->format('d/m/Y');

                return "⚠️ Máximo permitido: {$max} (3 años desde registro). Si se deja vacío se calcula automáticamente.";
            })
            ->rule(function (Forms\Get $get, ?Inventario $record = null): \Closure {
                return function (string $attribute, $value, \Closure $fail) use ($get, $record): void {
                    if (blank($value) || $get('tipo_propiedad') !== 'Rentado') {
                        return;
                    }

                    $inicioRaw = $get('fecha_registro') ?: $record?->fecha_registro;
                    $inicio = $inicioRaw ? Carbon::parse($inicioRaw)->startOfDay() : now()->startOfDay();
                    $fin = Carbon::parse($value)->startOfDay();
                    $maxPermitido = $inicio->copy()->addYears(3);

                    if ($fin->lt($inicio)) {
                        $fail("La fecha fin de renta no puede ser anterior a la fecha de registro ({$inicio->format('d/m/Y')}).");
                    }

                    if ($fin->gt($maxPermitido)) {
                        $fail("La fecha fin de renta no puede exceder el máximo de 3 años ({$maxPermitido->format('d/m/Y')}).");
                    }
                };
            })
            ->afterStateHydrated(function (Forms\Components\DatePicker $component, ?Inventario $record): void {
                if ($record && $record->tipo_propiedad === 'Rentado' && blank($component->getState())) {
                    $fin = $record->especificaciones['renta_fecha_fin'] ?? $record->fecha_fin_renta?->format('Y-m-d');
                    if ($fin) {
                        $component->state($fin);
                    }
                }
            });
    }

    public static function getReceptorSelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('id_receptor')
            ->label('Receptor')
            ->placeholder('Sin receptor asignado...')
            ->options(fn (): array => Receptor::orderBy('nombre')
                ->orderBy('apellido_paterno')
                ->get()
                ->mapWithKeys(fn (Receptor $r): array => [$r->id_receptor => $r->nombre_completo])
                ->toArray())
            ->searchable()
            ->preload()
            ->nullable()
            ->prefixIcon('heroicon-m-user')
            ->afterStateHydrated(function (Forms\Components\Select $component, ?Inventario $record): void {
                if ($record && blank($component->getState())) {
                    $component->state($record->id_receptor);
                }
            });
    }

    protected static function getDatosTecnicosSection(
        string $heading = '🔧 Especificaciones técnicas',
        ?\Closure $dehydratedCondition = null
    ): Forms\Components\Section {
        $fields = [
            Forms\Components\TextInput::make('ram')
                ->label('Memoria RAM')
                ->placeholder('ej. 8 GB DDR4')
                ->prefixIcon('heroicon-m-circle-stack')
                ->maxLength(100),

            Forms\Components\TextInput::make('procesador')
                ->label('Procesador')
                ->placeholder('ej. Intel Core i5-8500')
                ->prefixIcon('heroicon-m-cpu-chip')
                ->maxLength(100),

            Forms\Components\TextInput::make('disco')
                ->label('Disco duro')
                ->placeholder('ej. 256 GB SSD')
                ->prefixIcon('heroicon-m-circle-stack')
                ->maxLength(100),

            Forms\Components\TextInput::make('sistema_operativo')
                ->label('Sistema operativo')
                ->placeholder('ej. Windows 11 Pro')
                ->prefixIcon('heroicon-m-computer-desktop')
                ->maxLength(100),

            Forms\Components\TextInput::make('ip_address')
                ->label('Dirección IP')
                ->placeholder('192.168.1.__')
                ->prefixIcon('heroicon-m-globe-alt')
                ->maxLength(50),

            Forms\Components\TextInput::make('mac_address')
                ->label('Dirección MAC')
                ->placeholder('AA:BB:CC:DD:EE:FF')
                ->prefixIcon('heroicon-m-signal')
                ->maxLength(50),
        ];

        if ($dehydratedCondition) {
            foreach ($fields as $field) {
                $field->dehydrated($dehydratedCondition);
            }
        }

        return Forms\Components\Section::make($heading)
            ->description('Detalles técnicos y de conectividad del equipo')
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                    ->schema($fields),
            ]);
    }

    protected static function getDatosRedSection(
        string $heading = '🔧 Especificaciones técnicas (Red)',
        ?\Closure $dehydratedCondition = null
    ): Forms\Components\Section {
        $fields = [
            Forms\Components\TextInput::make('ip_address')
                ->label('Dirección IP')
                ->placeholder('192.168.1.__')
                ->prefixIcon('heroicon-m-globe-alt')
                ->maxLength(50),

            Forms\Components\TextInput::make('mac_address')
                ->label('Dirección MAC')
                ->placeholder('AA:BB:CC:DD:EE:FF')
                ->prefixIcon('heroicon-m-signal')
                ->maxLength(50),
        ];

        if ($dehydratedCondition) {
            foreach ($fields as $field) {
                $field->dehydrated($dehydratedCondition);
            }
        }

        return Forms\Components\Section::make($heading)
            ->description('Parámetros de conexión a la red institucional')
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                    ->schema($fields),
            ]);
    }

    public static array $memoriaEspecificaciones = [];

    public static array $defaultEspecificaciones = [
        'Funcional',
        'Operativo',
        'Buen estado',
        'Detalle estético',
        'Sin probar',
        'Para refacciones',
    ];

    public static function getEspecificacionesOptions(): array
    {
        $sessionSpecs = session('inventario_especificaciones_creadas', []);

        $db = [];
        try {
            $db = Inventario::whereNotNull('especificacion_texto')
                ->where('especificacion_texto', '!=', '')
                ->distinct()
                ->pluck('especificacion_texto')
                ->toArray();
        } catch (\Throwable $e) {
            $db = [];
        }

        $all = array_unique(array_filter(array_merge(
            static::$defaultEspecificaciones,
            static::$memoriaEspecificaciones,
            $sessionSpecs,
            $db
        )));

        $res = [];
        foreach ($all as $val) {
            $clean = trim($val);
            if (filled($clean)) {
                $res[$clean] = $clean;
            }
        }
        ksort($res, SORT_NATURAL | SORT_FLAG_CASE);

        return $res;
    }

    public static function agregarEspecificacionGlobal(string $texto): string
    {
        $clean = trim($texto);
        if (filled($clean)) {
            static::$memoriaEspecificaciones[] = $clean;
            static::$memoriaEspecificaciones = array_unique(static::$memoriaEspecificaciones);

            $sessionSpecs = session('inventario_especificaciones_creadas', []);
            if (! in_array($clean, $sessionSpecs, true)) {
                $sessionSpecs[] = $clean;
                session(['inventario_especificaciones_creadas' => $sessionSpecs]);
            }
        }
        return $clean;
    }

    protected static function getMarcaSelectField(
        string $fieldName,
        ?\Closure $requiredCondition = null,
        ?\Closure $disabledCondition = null
    ): Forms\Components\Select {
        $select = Forms\Components\Select::make($fieldName)
            ->label('Marca')
            ->placeholder('Selecciona la marca...')
            ->options(fn () => MarcaMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray())
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(function (string $search): array {
                $clean = trim($search);
                $matches = MarcaMaterial::where('nombre', 'like', "%{$clean}%")
                    ->orderBy('nombre')
                    ->limit(50)
                    ->pluck('nombre', 'nombre')
                    ->toArray();

                $exact = false;
                foreach ($matches as $k => $v) {
                    if (strcasecmp($v, $clean) === 0) {
                        $exact = true;
                        break;
                    }
                }

                if (! $exact && filled($clean)) {
                    $matches['__NEW__:' . $clean] = "＋ Agregar «{$clean}» como nueva opción";
                }

                return $matches;
            })
            ->getOptionLabelUsing(function ($value): ?string {
                if (filled($value)) {
                    if (str_starts_with($value, '__NEW__:')) {
                        return trim(substr($value, 8));
                    }
                    return $value;
                }
                return null;
            })
            ->live()
            ->afterStateUpdated(function (Forms\Set $set, ?string $state) use ($fieldName) {
                if (filled($state)) {
                    $clean = trim($state);
                    if (str_starts_with($clean, '__NEW__:')) {
                        $clean = trim(substr($clean, 8));
                    }
                    $existente = SigemUniqueValidator::buscarPorValorNormalizado(MarcaMaterial::class, 'nombre', $clean);
                    if ($existente instanceof MarcaMaterial) {
                        if ($existente->trashed()) {
                            $existente->restore();
                        }
                        $clean = $existente->nombre;
                    } else {
                        MarcaMaterial::create(['nombre' => $clean]);
                    }
                    if ($clean !== $state) {
                        $set($fieldName, $clean);
                    }
                }
            })
            ->createOptionForm([
                SubformSlideOverHelper::headerNav(),
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre de la marca')
                    ->placeholder('Ej. Dell, HP, Lenovo...')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->rule(SigemUniqueValidator::reglaCatalogoUnico(MarcaMaterial::class, 'una marca', 'nombre', false)),
                CatalogoBaseFormSchema::alertaSimilitudCatalogo('nombre', MarcaMaterial::class, 'una marca', 'nombre', false),
                SubformSlideOverHelper::catalogTip('La marca estará disponible inmediatamente en el catálogo.'),
            ])
            ->createOptionUsing(function (array $data) {
                $clean = trim($data['nombre']);
                $existente = SigemUniqueValidator::buscarPorValorNormalizado(MarcaMaterial::class, 'nombre', $clean);
                if ($existente instanceof MarcaMaterial) {
                    if ($existente->trashed()) {
                        $existente->restore();
                    }

                    return $existente->nombre;
                }
                $marca = MarcaMaterial::create(['nombre' => $clean]);

                return $marca->nombre;
            })
            ->createOptionAction(function (Action $action) {
                return SubformSlideOverHelper::configureAction(
                    $action,
                    '✏️ Agregar marca como nueva opción',
                    '+ Agregar',
                    'Marca creada correctamente'
                );
            })
            ->prefixIcon('heroicon-m-tag');

        if ($requiredCondition) {
            $select->required($requiredCondition);
        } else {
            $select->required();
        }

        if ($disabledCondition) {
            $select->disabled($disabledCondition);
        }

        return $select;
    }

    protected static function getEspecificacionesField(
        string $fieldName,
        ?\Closure $disabledCondition = null
    ): Forms\Components\Select {
        $field = Forms\Components\Select::make($fieldName)
            ->label('Especificaciones')
            ->placeholder('Elige o escribe, ej. Funcional')
            ->options(fn () => static::getEspecificacionesOptions())
            ->searchable()
            ->preload()
            ->getSearchResultsUsing(function (string $search): array {
                $clean = trim($search);
                $all = static::getEspecificacionesOptions();
                $matches = [];

                foreach ($all as $k => $v) {
                    if (stripos($v, $clean) !== false) {
                        $matches[$k] = $v;
                    }
                }

                $exact = false;
                foreach ($all as $k => $v) {
                    if (strcasecmp($v, $clean) === 0) {
                        $exact = true;
                        break;
                    }
                }

                if (! $exact && filled($clean)) {
                    $matches['__NEW__:' . $clean] = "＋ Agregar «{$clean}» como nueva opción";
                }

                return $matches;
            })
            ->getOptionLabelUsing(function ($value): ?string {
                if (filled($value)) {
                    if (str_starts_with($value, '__NEW__:')) {
                        return trim(substr($value, 8));
                    }
                    return $value;
                }
                return null;
            })
            ->live()
            ->afterStateUpdated(function (Forms\Set $set, ?string $state) use ($fieldName) {
                if (filled($state)) {
                    $clean = trim($state);
                    if (str_starts_with($clean, '__NEW__:')) {
                        $clean = trim(substr($clean, 8));
                    }
                    $clean = static::agregarEspecificacionGlobal($clean);
                    if ($clean !== $state) {
                        $set($fieldName, $clean);
                    }
                }
            })
            ->createOptionForm([
                SubformSlideOverHelper::headerNav(),
                Forms\Components\TextInput::make('especificacion')
                    ->label('Nueva especificación')
                    ->placeholder('Ej. Core i7 16GB RAM 512GB SSD...')
                    ->required()
                    ->maxLength(150),
                SubformSlideOverHelper::catalogTip('La especificación estará disponible inmediatamente para este y otros equipos.'),
            ])
            ->createOptionUsing(function (array $data) {
                return static::agregarEspecificacionGlobal($data['especificacion']);
            })
            ->createOptionAction(function (Action $action) {
                return SubformSlideOverHelper::configureAction(
                    $action,
                    '✏️ Agregar especificación como nueva opción',
                    '+ Agregar',
                    'Especificación agregada correctamente'
                );
            })
            ->prefixIcon('heroicon-m-adjustments-horizontal');

        if ($disabledCondition) {
            $field->disabled($disabledCondition);
        }

        return $field;
    }
}
