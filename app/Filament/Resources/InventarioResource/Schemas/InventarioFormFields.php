<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;

trait InventarioFormFields
{
    protected static function getDatosTecnicosSection(
        string $heading = '⚙️ Datos técnicos (opcional, se pueden capturar después)',
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

            Forms\Components\TextInput::make('procesador')
                ->label('Procesador')
                ->placeholder('ej. Intel Core i5-8500')
                ->prefixIcon('heroicon-m-cpu-chip')
                ->maxLength(100),

            Forms\Components\TextInput::make('ram')
                ->label('Memoria RAM')
                ->placeholder('ej. 8 GB DDR4')
                ->prefixIcon('heroicon-m-circle-stack')
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
        ];

        if ($dehydratedCondition) {
            foreach ($fields as $field) {
                $field->dehydrated($dehydratedCondition);
            }
        }

        return Forms\Components\Section::make($heading)
            ->collapsible()
            ->collapsed()
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                    ->schema($fields),
            ]);
    }

    protected static function getDatosRedSection(
        string $heading = '🌐 Datos de red (opcional)',
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
            ->collapsible()
            ->collapsed()
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
                    MarcaMaterial::firstOrCreate(['nombre' => $clean]);
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
                    ->maxLength(100),
                SubformSlideOverHelper::catalogTip('La marca estará disponible inmediatamente en el catálogo.'),
            ])
            ->createOptionUsing(function (array $data) {
                $clean = trim($data['nombre']);
                $marca = MarcaMaterial::firstOrCreate(['nombre' => $clean]);
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
