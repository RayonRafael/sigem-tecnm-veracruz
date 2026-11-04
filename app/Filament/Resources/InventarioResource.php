<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Edificio;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Salon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'registro de inventario';

    protected static ?string $pluralModelLabel = 'registros de inventario';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['num_serie', 'num_factura'];
    }

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'create' || ($form->getLivewire() instanceof \Filament\Resources\Pages\CreateRecord)) {
            return $form
                ->schema(static::getMockupFormSchema());
        }

        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs(static::getEditFormTabs())
                    ->columnSpanFull(),
            ]);
    }

    public static function getMockupFormSchema(): array
    {
        return [
            // 1. "¿Qué vas a registrar?" (grid de 12 cards)
            Forms\Components\Section::make('¿Qué vas a registrar?')
                ->description('Selecciona el tipo de equipo a registrar')
                ->icon('heroicon-m-squares-2x2')
                ->schema([
                    Forms\Components\ViewField::make('tipo_equipo')
                        ->view('filament.forms.components.tipo-equipo-grid')
                        ->default('pc')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $map = [
                                'pc' => 'Computadora',
                                'aio' => 'All in One',
                                'lap' => 'Laptop',
                                'cpu' => 'Computadora de Escritorio',
                                'mon' => 'Monitor',
                                'tec' => 'Teclado',
                                'mou' => 'Mouse',
                                'imp' => 'Impresora',
                                'pro' => 'Proyector',
                                'nob' => 'No-Break',
                                'swi' => 'Switch de red',
                                'otro' => 'Otro artículo',
                            ];
                            $set('subtipo', $map[$state] ?? $state);

                            // Limpiar campos de componentes al cambiar de tipo
                            $set('num_serie', null);
                            $set('modelo', null);
                            $set('especificacion_texto', null);
                            $set('tipo_impresora', null);
                            $set('sistema_operativo', null);
                            $set('procesador', null);
                            $set('ram', null);
                            $set('disco', null);
                            $set('mac_address', null);
                            $set('ip_address', null);
                            $set('observaciones_generales', null);

                            $set('especificaciones.marca', null);
                            $set('especificaciones.cpu_marca', null);
                            $set('especificaciones.no_tiene_monitor', false);
                            $set('especificaciones.no_tiene_teclado', false);
                            $set('especificaciones.no_tiene_mouse', false);
                            $set('especificaciones.monitor_sn', null);
                            $set('especificaciones.monitor_marca', null);
                            $set('especificaciones.monitor_modelo', null);
                            $set('especificaciones.monitor_specs', null);
                            $set('especificaciones.monitor_desc', null);
                            $set('especificaciones.teclado_sn', null);
                            $set('especificaciones.teclado_marca', null);
                            $set('especificaciones.teclado_modelo', null);
                            $set('especificaciones.teclado_specs', null);
                            $set('especificaciones.teclado_desc', null);
                            $set('especificaciones.mouse_sn', null);
                            $set('especificaciones.mouse_marca', null);
                            $set('especificaciones.mouse_modelo', null);
                            $set('especificaciones.mouse_specs', null);
                            $set('especificaciones.mouse_desc', null);
                            $set('especificaciones.num_puertos', null);
                        }),
                ])
                ->columnSpanFull(),

            // 2. Banner informativo (solo para pc y aio)
            Forms\Components\Placeholder::make('banner_informativo')
                ->label('')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->content(function (Forms\Get $get) {
                    $tipo = $get('tipo_equipo') ?? 'pc';
                    $texto = match ($tipo) {
                        'pc' => '💡 Se registrarán <strong>monitor, teclado, mouse y CPU</strong> como un solo equipo. Si le falta alguno, marca <strong>«No tiene»</strong>.',
                        'aio' => '💡 Se registrará el <strong>All in One</strong> con su teclado y mouse como un solo equipo. Si le falta alguno, marca <strong>«No tiene»</strong>.',
                        default => '',
                    };
                    return new HtmlString('
                        <div style="background-color: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af; padding: 0.875rem 1.25rem; border-radius: 0.5rem; font-size: 0.9375rem; display: flex; align-items: center; gap: 0.5rem;">
                            ' . $texto . '
                        </div>
                    ');
                })
                ->columnSpanFull(),

            // 3. 📍 Ubicación
            Forms\Components\Section::make('📍 Ubicación')
                ->description('Ubicación física estructurada del equipo dentro de la institución')
                ->icon('heroicon-m-map-pin')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->schema([
                            Forms\Components\Select::make('departamento_id')
                                ->label('Departamento')
                                ->placeholder('Selecciona departamento...')
                                ->options(fn () => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $set('area_id', null);
                                    static::actualizarUbicacionDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-building-office'),

                            Forms\Components\Select::make('area_id')
                                ->label('Área')
                                ->placeholder(fn (Forms\Get $get) => $get('departamento_id') ? 'Selecciona área...' : 'Elige departamento primero')
                                ->options(function (Forms\Get $get) {
                                    $deptoId = $get('departamento_id');
                                    if (! $deptoId) {
                                        return [];
                                    }
                                    return Area::where('id_departamento', $deptoId)
                                        ->orderBy('nombre')
                                        ->pluck('nombre', 'id_area');
                                })
                                ->searchable()
                                ->live()
                                ->disabled(fn (Forms\Get $get) => blank($get('departamento_id')))
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    static::actualizarUbicacionDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-rectangle-group'),

                            Forms\Components\TextInput::make('especificaciones.aula')
                                ->label('Aula')
                                ->placeholder('ej. E101')
                                ->maxLength(5)
                                ->helperText('Letra del edificio + planta + número. Déjalo vacío si el equipo no está en un aula.')
                                ->prefixIcon('heroicon-m-academic-cap')
                                ->lazy()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    static::actualizarUbicacionDescriptiva($set, $get);
                                }),

                            Forms\Components\TextInput::make('estacion')
                                ->label('Estación')
                                ->placeholder('ej. AC001')
                                ->maxLength(20)
                                ->helperText('Identificador del puesto de trabajo')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap']))
                                ->required(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap'])),
                        ]),
                ])
                ->columnSpanFull(),

            // 4. Cards por componente
            ...static::getComponentCardsSchema(),

            // 5. Datos Administrativos
            Forms\Components\Section::make('⚙️ Datos Administrativos (opcional)')
                ->description('Detalles administrativos y de control interno')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'Reasignado' => 'Reasignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                    'Baja' => 'Baja',
                                ])
                                ->default('Disponible')
                                ->required(),

                            Forms\Components\Select::make('tipo_propiedad')
                                ->label('Tipo de propiedad')
                                ->options([
                                    'Propio' => 'Propio',
                                    'Rentado' => 'Rentado',
                                ])
                                ->default('Propio')
                                ->required(),

                            Forms\Components\DatePicker::make('fecha_registro')
                                ->label('Fecha de registro')
                                ->default(now())
                                ->required(),
                        ]),

                    Forms\Components\Hidden::make('subtipo')
                        ->default('Computadora'),

                    Forms\Components\Hidden::make('ubicacion_fisica'),

                    Forms\Components\Hidden::make('id_usuario')
                        ->default(fn () => auth()->id()),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function getComponentCardsSchema(): array
    {
        return [
            // Card 1 para PC: CPU
            Forms\Components\Section::make('🗄️ CPU')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. B4HPS22')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.cpu_marca', fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc'),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder('ej. OptiPlex 7040')
                                ->prefixIcon('heroicon-m-tag')
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. Equipo completo, formateado')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),

                    static::getDatosTecnicosSection(),
                ])
                ->columnSpanFull(),

            // Card 2 para PC: Monitor (con checkbox "No tiene")
            Forms\Components\Section::make('🖥️ Monitor')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_monitor') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_monitor') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_monitor')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_monitor') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.monitor_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. PE17H9NQ800371J')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc' && ! $get('especificaciones.no_tiene_monitor'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.monitor_marca',
                                fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc' && ! $get('especificaciones.no_tiene_monitor'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor')
                            ),

                            Forms\Components\TextInput::make('especificaciones.monitor_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. E2216H')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.monitor_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor')
                            ),

                            Forms\Components\Textarea::make('especificaciones.monitor_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. DELL FLAT PANEL MONITOR Dell E2216H')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card 1 para All in One
            Forms\Components\Section::make('🖥️ All in One')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. 3CG4251XYZ')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.marca', fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio'),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder('ej. ProOne 400 G5')
                                ->prefixIcon('heroicon-m-tag')
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. AIO HP + teclado y mouse')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),

                    static::getDatosTecnicosSection(),
                ])
                ->columnSpanFull(),

            // Card Teclado (compartida para PC y AIO, con checkbox "No tiene")
            Forms\Components\Section::make('⌨️ Teclado')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_teclado') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_teclado') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_teclado')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_teclado') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.teclado_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. CN-0GDPPG-71581-…')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_teclado'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.teclado_marca',
                                fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_teclado'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado')
                            ),

                            Forms\Components\TextInput::make('especificaciones.teclado_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. KB216')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.teclado_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado')
                            ),

                            Forms\Components\Textarea::make('especificaciones.teclado_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. DELL USB KEYBOARD')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card Mouse (compartida para PC y AIO, con checkbox "No tiene")
            Forms\Components\Section::make('🖱️ Mouse')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_mouse') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_mouse') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_mouse')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_mouse') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.mouse_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. MOC5UL-7412')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_mouse'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.mouse_marca',
                                fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_mouse'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse')
                            ),

                            Forms\Components\TextInput::make('especificaciones.mouse_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. MS116')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.mouse_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse')
                            ),

                            Forms\Components\Textarea::make('especificaciones.mouse_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. HP 125 WIRED MOUSE')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card para artículos individuales (lap, cpu, mon, tec, mou, imp, pro, nob, swi, otro)
            Forms\Components\Section::make(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                'lap' => '💻 Laptop',
                'cpu' => '🗄️ CPU',
                'mon' => '🖵 Monitor',
                'tec' => '⌨️ Teclado',
                'mou' => '🖱️ Mouse',
                'imp' => '🖨️ Impresora',
                'pro' => '📽️ Proyector',
                'nob' => '🔋 No-Break',
                'swi' => '🔌 Switch de red',
                'otro' => '📦 Otro artículo',
                default => '📦 Artículo de inventario',
            })
                ->visible(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. PE17H9NQ800371J',
                                    'cpu' => 'ej. B4HPS22',
                                    'tec' => 'ej. CN-0GDPPG-71581-…',
                                    'mou' => 'ej. MOC5UL-7412',
                                    'lap' => 'ej. 5CD1234ABC',
                                    'imp' => 'ej. HP-LJ4103-ADM01',
                                    'pro' => 'ej. PROJ-001',
                                    'nob' => 'ej. NB-2024-001',
                                    'swi' => 'ej. SW-2024-001',
                                    default => 'ej. SN-001',
                                })
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.marca', fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio'])),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. E2216H',
                                    'cpu' => 'ej. OptiPlex 7040',
                                    'tec' => 'ej. KB216',
                                    'mou' => 'ej. MS116',
                                    'lap' => 'ej. EliteBook 840 G7',
                                    'imp' => 'ej. LaserJet Pro MFP',
                                    'pro' => 'ej. EB-X51',
                                    'nob' => 'ej. Smart-UPS 1500',
                                    'swi' => 'ej. SG110-24',
                                    default => 'ej. Modelo',
                                })
                                ->prefixIcon('heroicon-m-tag')
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            // Campo específico para Impresora
                            Forms\Components\Select::make('tipo_impresora')
                                ->label('Tipo de impresora')
                                ->options([
                                    'Láser' => 'Láser',
                                    'Inyección' => 'Inyección',
                                ])
                                ->placeholder('Selecciona...')
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'imp')
                                ->required(fn (Forms\Get $get) => $get('tipo_equipo') === 'imp'),

                            // Campo específico para Switch de red
                            Forms\Components\TextInput::make('especificaciones.num_puertos')
                                ->label('Número de puertos')
                                ->placeholder('ej. 24 puertos Gigabit PoE')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'swi')
                                ->maxLength(50),

                            Forms\Components\Select::make('id_producto')
                                ->label('Artículo del catálogo (opcional)')
                                ->placeholder('Selecciona o deja que el sistema lo registre...')
                                ->relationship('material', 'nombre')
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'otro'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. DELL FLAT PANEL',
                                    'cpu' => 'ej. Equipo completo, formateado',
                                    'tec' => 'ej. DELL USB KEYBOARD',
                                    'mou' => 'ej. HP 125 WIRED MOUSE',
                                    'lap' => 'ej. Laptop HP EliteBook',
                                    'imp' => 'ej. Impresora Multifuncional HP',
                                    'pro' => 'ej. Epson PowerLite',
                                    'nob' => 'ej. APC No-Break',
                                    'swi' => 'ej. Cisco 24-Port',
                                    default => 'ej. Descripción del artículo',
                                })
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),

                    // Datos técnicos expandibles para Laptop o CPU individual
                    static::getDatosTecnicosSection()
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['lap', 'cpu'])),

                    // Datos de red expandibles para Impresora o Switch de red
                    static::getDatosRedSection()
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['imp', 'swi'])),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function getDatosTecnicosSection(string $heading = '⚙️ Datos técnicos (opcional, se pueden capturar después)'): Forms\Components\Section
    {
        return Forms\Components\Section::make($heading)
            ->collapsible()
            ->collapsed()
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
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
                    ]),
            ]);
    }

    protected static function getDatosRedSection(string $heading = '🌐 Datos de red (opcional)'): Forms\Components\Section
    {
        return Forms\Components\Section::make($heading)
            ->collapsible()
            ->collapsed()
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
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
                    ]),
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
        } catch (\Throwable $e) {}

        $merged = array_unique(array_merge(
            static::$defaultEspecificaciones,
            static::$memoriaEspecificaciones,
            $sessionSpecs,
            $db
        ));

        $merged = array_filter(array_map('trim', $merged), fn ($v) => filled($v));

        $result = [];
        foreach ($merged as $val) {
            $result[$val] = $val;
        }

        return $result;
    }

    public static function agregarEspecificacionGlobal(string $texto): string
    {
        $clean = trim($texto);
        if (filled($clean)) {
            if (! in_array($clean, static::$memoriaEspecificaciones)) {
                static::$memoriaEspecificaciones[] = $clean;
            }
            $sessionSpecs = session('inventario_especificaciones_creadas', []);
            if (! in_array($clean, $sessionSpecs)) {
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
                    $set($fieldName, $clean);
                }
            })
            ->createOptionForm([
                Forms\Components\TextInput::make('nombre')
                    ->label('Nombre de la marca')
                    ->required()
                    ->maxLength(100),
            ])
            ->createOptionUsing(function (array $data) {
                $clean = trim($data['nombre']);
                $marca = MarcaMaterial::firstOrCreate(['nombre' => $clean]);
                return $marca->nombre;
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('＋ Agregar marca como nueva opción')
                    ->modalButton('Agregar');
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
                    $set($fieldName, $clean);
                }
            })
            ->createOptionForm([
                Forms\Components\TextInput::make('especificacion')
                    ->label('Nueva especificación')
                    ->required()
                    ->maxLength(150),
            ])
            ->createOptionUsing(function (array $data) {
                return static::agregarEspecificacionGlobal($data['especificacion']);
            })
            ->createOptionAction(function (Action $action) {
                return $action
                    ->modalHeading('＋ Agregar especificación como nueva opción')
                    ->modalButton('Agregar');
            })
            ->prefixIcon('heroicon-m-adjustments-horizontal');

        if ($disabledCondition) {
            $field->disabled($disabledCondition);
        }

        return $field;
    }

    public static function actualizarUbicacionDescriptiva(Forms\Set $set, Forms\Get $get): void
    {
        $partes = [];
        if ($deptoId = $get('departamento_id')) {
            $depto = Departamento::find($deptoId);
            if ($depto) {
                $partes[] = $depto->nombre;
            }
        }
        if ($areaId = $get('area_id')) {
            $area = Area::find($areaId);
            if ($area) {
                $partes[] = $area->nombre;
            }
        }
        if ($aula = $get('especificaciones.aula')) {
            $partes[] = "Aula {$aula}";
        }
        if (! empty($partes)) {
            $set('ubicacion_fisica', implode(' > ', $partes));
        }
    }

    public static function contarEquiposParaCrear(array $data): int
    {
        $tipo = $data['tipo_equipo'] ?? 'pc';
        if ($tipo === 'pc') {
            $count = 1; // CPU siempre se crea
            if (empty($data['especificaciones']['no_tiene_monitor']) && filled($data['especificaciones']['monitor_sn'] ?? null)) {
                $count++;
            }
            if (empty($data['especificaciones']['no_tiene_teclado']) && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                $count++;
            }
            if (empty($data['especificaciones']['no_tiene_mouse']) && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                $count++;
            }
            return $count;
        }

        if ($tipo === 'aio') {
            $count = 1; // AIO siempre se crea
            if (empty($data['especificaciones']['no_tiene_teclado']) && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                $count++;
            }
            if (empty($data['especificaciones']['no_tiene_mouse']) && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                $count++;
            }
            return $count;
        }

        return 1;
    }

    public static function registrarMultiplesEquipos(array $data): Inventario
    {
        return DB::transaction(function () use ($data) {
            $tipo = $data['tipo_equipo'] ?? 'pc';
            $estacion = $data['estacion'] ?? null;
            $departamentoId = $data['departamento_id'] ?? null;
            $areaId = $data['area_id'] ?? null;
            $aula = $data['especificaciones']['aula'] ?? null;
            $salonId = $data['salon_id'] ?? null;

            if (empty($salonId) && ! empty($aula)) {
                $salon = Salon::where('nombre', $aula)
                    ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
                    ->first();
                if ($salon) {
                    $salonId = $salon->id;
                }
            }

            // Ubicación física descriptiva
            if (empty($data['ubicacion_fisica'])) {
                $partes = [];
                if ($departamentoId && ($depto = Departamento::find($departamentoId))) {
                    $partes[] = $depto->nombre;
                }
                if ($areaId && ($area = Area::find($areaId))) {
                    $partes[] = $area->nombre;
                }
                if (! empty($aula)) {
                    $partes[] = "Aula {$aula}";
                }
                $ubicacionFisica = ! empty($partes) ? implode(' > ', $partes) : null;
            } else {
                $ubicacionFisica = $data['ubicacion_fisica'];
            }

            // Determinar aprobación según rol / panel
            $isServicioSocial = (function_exists('filament') && filament()->getCurrentPanel()?->getId() === 'servicio-social')
                || (auth()->user()?->hasRole(\App\Enums\RoleEnum::SERVICIO_SOCIAL->value) ?? false);

            $aprobado = isset($data['aprobado'])
                ? (bool) $data['aprobado']
                : ! $isServicioSocial;

            $estadoRegistro = $data['estado_registro']
                ?? ($isServicioSocial ? 'Pendiente' : 'Aprobado');

            $aprobadoPor = isset($data['aprobado_por'])
                ? $data['aprobado_por']
                : ($isServicioSocial ? null : auth()->id());

            $fechaAprobacion = $aprobado ? now() : null;

            $limpiarMarca = function (?string $m): ?string {
                if (! filled($m)) return null;
                $clean = trim($m);
                if (str_starts_with($clean, '__NEW__:')) {
                    $clean = trim(substr($clean, 8));
                }
                MarcaMaterial::firstOrCreate(['nombre' => $clean]);
                return $clean;
            };

            $limpiarEspec = function (?string $e): ?string {
                if (! filled($e)) return null;
                $clean = trim($e);
                if (str_starts_with($clean, '__NEW__:')) {
                    $clean = trim(substr($clean, 8));
                }
                static::agregarEspecificacionGlobal($clean);
                return $clean;
            };

            $baseData = [
                'departamento_id' => $departamentoId,
                'area_id' => $areaId,
                'salon_id' => $salonId,
                'estacion' => $estacion,
                'estado' => $data['estado'] ?? 'Disponible',
                'tipo_propiedad' => $data['tipo_propiedad'] ?? 'Propio',
                'fecha_registro' => $data['fecha_registro'] ?? now(),
                'id_usuario' => $data['id_usuario'] ?? auth()->id() ?? 1,
                'ubicacion_fisica' => $ubicacionFisica,
                'aprobado' => $aprobado,
                'estado_registro' => $estadoRegistro,
                'aprobado_por' => $aprobadoPor,
                'fecha_aprobacion' => $fechaAprobacion,
            ];

            $registrosCreados = [];
            $primaryRecord = null;

            if ($tipo === 'pc') {
                // 1. Monitor (si no tiene "no tiene" y tiene datos)
                $noTieneMonitor = (bool) ($data['especificaciones']['no_tiene_monitor'] ?? false);
                if (! $noTieneMonitor && filled($data['especificaciones']['monitor_sn'] ?? null)) {
                    $monMarca = $limpiarMarca($data['especificaciones']['monitor_marca'] ?? null);
                    $monSpecsTexto = $limpiarEspec($data['especificaciones']['monitor_specs'] ?? null);
                    $monSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'monitor',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $monMarca,
                    ];
                    $monitor = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Monitor',
                        'num_serie' => $data['especificaciones']['monitor_sn'],
                        'modelo' => $data['especificaciones']['monitor_modelo'] ?? null,
                        'especificacion_texto' => $monSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['monitor_desc'] ?? null,
                        'especificaciones' => $monSpecs,
                    ]));
                    $registrosCreados[] = $monitor;
                }

                // 2. Teclado (si no tiene "no tiene" y tiene datos)
                $noTieneTeclado = (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false);
                if (! $noTieneTeclado && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                    $tecMarca = $limpiarMarca($data['especificaciones']['teclado_marca'] ?? null);
                    $tecSpecsTexto = $limpiarEspec($data['especificaciones']['teclado_specs'] ?? null);
                    $tecSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ];
                    $teclado = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Teclado',
                        'num_serie' => $data['especificaciones']['teclado_sn'],
                        'modelo' => $data['especificaciones']['teclado_modelo'] ?? null,
                        'especificacion_texto' => $tecSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['teclado_desc'] ?? null,
                        'especificaciones' => $tecSpecs,
                    ]));
                    $registrosCreados[] = $teclado;
                }

                // 3. Mouse (si no tiene "no tiene" y tiene datos)
                $noTieneMouse = (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false);
                if (! $noTieneMouse && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                    $mouMarca = $limpiarMarca($data['especificaciones']['mouse_marca'] ?? null);
                    $mouSpecsTexto = $limpiarEspec($data['especificaciones']['mouse_specs'] ?? null);
                    $mouSpecs = [
                        'tipo_equipo' => 'pc',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ];
                    $mouse = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Mouse',
                        'num_serie' => $data['especificaciones']['mouse_sn'],
                        'modelo' => $data['especificaciones']['mouse_modelo'] ?? null,
                        'especificacion_texto' => $mouSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['mouse_desc'] ?? null,
                        'especificaciones' => $mouSpecs,
                    ]));
                    $registrosCreados[] = $mouse;
                }

                // 4. CPU (SIEMPRE se crea para pc)
                $cpuMarca = $limpiarMarca($data['especificaciones']['cpu_marca'] ?? $data['especificaciones']['marca'] ?? null);
                $cpuSpecsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);
                $cpuSpecs = [
                    'tipo_equipo' => 'pc',
                    'componente' => 'cpu',
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $cpuMarca,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => count($registrosCreados) + 1,
                ];

                $cpu = Inventario::create(array_merge($baseData, [
                    'subtipo' => 'CPU',
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $cpuSpecsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $cpuSpecs,
                ]));

                $primaryRecord = $cpu;

            } elseif ($tipo === 'aio') {
                // 1. Teclado
                $noTieneTeclado = (bool) ($data['especificaciones']['no_tiene_teclado'] ?? false);
                if (! $noTieneTeclado && filled($data['especificaciones']['teclado_sn'] ?? null)) {
                    $tecMarca = $limpiarMarca($data['especificaciones']['teclado_marca'] ?? null);
                    $tecSpecsTexto = $limpiarEspec($data['especificaciones']['teclado_specs'] ?? null);
                    $tecSpecs = [
                        'tipo_equipo' => 'aio',
                        'componente' => 'teclado',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $tecMarca,
                    ];
                    $teclado = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Teclado',
                        'num_serie' => $data['especificaciones']['teclado_sn'],
                        'modelo' => $data['especificaciones']['teclado_modelo'] ?? null,
                        'especificacion_texto' => $tecSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['teclado_desc'] ?? null,
                        'especificaciones' => $tecSpecs,
                    ]));
                    $registrosCreados[] = $teclado;
                }

                // 2. Mouse
                $noTieneMouse = (bool) ($data['especificaciones']['no_tiene_mouse'] ?? false);
                if (! $noTieneMouse && filled($data['especificaciones']['mouse_sn'] ?? null)) {
                    $mouMarca = $limpiarMarca($data['especificaciones']['mouse_marca'] ?? null);
                    $mouSpecsTexto = $limpiarEspec($data['especificaciones']['mouse_specs'] ?? null);
                    $mouSpecs = [
                        'tipo_equipo' => 'aio',
                        'componente' => 'mouse',
                        'estacion' => $estacion,
                        'aula' => $aula,
                        'marca' => $mouMarca,
                    ];
                    $mouse = Inventario::create(array_merge($baseData, [
                        'subtipo' => 'Mouse',
                        'num_serie' => $data['especificaciones']['mouse_sn'],
                        'modelo' => $data['especificaciones']['mouse_modelo'] ?? null,
                        'especificacion_texto' => $mouSpecsTexto,
                        'observaciones_generales' => $data['especificaciones']['mouse_desc'] ?? null,
                        'especificaciones' => $mouSpecs,
                    ]));
                    $registrosCreados[] = $mouse;
                }

                // 3. All in One (SIEMPRE se crea)
                $aioMarca = $limpiarMarca($data['especificaciones']['marca'] ?? null);
                $aioSpecsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);
                $aioSpecs = [
                    'tipo_equipo' => 'aio',
                    'componente' => 'aio',
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $aioMarca,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => count($registrosCreados) + 1,
                ];

                $aio = Inventario::create(array_merge($baseData, [
                    'subtipo' => 'All in One',
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $aioSpecsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $aioSpecs,
                ]));

                $primaryRecord = $aio;

            } else {
                // Equipos individuales
                $mapSubtipos = [
                    'lap' => 'Laptop',
                    'cpu' => 'CPU',
                    'mon' => 'Monitor',
                    'tec' => 'Teclado',
                    'mou' => 'Mouse',
                    'imp' => 'Impresora',
                    'pro' => 'Proyector',
                    'nob' => 'No-Break',
                    'swi' => 'Switch de red',
                    'otro' => 'Otro artículo',
                ];
                $subtipo = $mapSubtipos[$tipo] ?? ($data['subtipo'] ?? 'Otro artículo');
                $marca = $limpiarMarca($data['especificaciones']['marca'] ?? null);
                $specsTexto = $limpiarEspec($data['especificacion_texto'] ?? null);

                $singleSpecs = [
                    'tipo_equipo' => $tipo,
                    'componente' => $tipo,
                    'estacion' => $estacion,
                    'aula' => $aula,
                    'marca' => $marca,
                    'num_puertos' => $data['especificaciones']['num_puertos'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    '_total_registrados' => 1,
                ];

                $itemData = array_merge($baseData, [
                    'subtipo' => $subtipo,
                    'num_serie' => $data['num_serie'] ?? null,
                    'modelo' => $data['modelo'] ?? null,
                    'especificacion_texto' => $specsTexto,
                    'observaciones_generales' => $data['observaciones_generales'] ?? null,
                    'tipo_impresora' => $data['tipo_impresora'] ?? null,
                    'ip_address' => $data['especificaciones']['ip_address'] ?? $data['ip_address'] ?? null,
                    'mac_address' => $data['especificaciones']['mac_address'] ?? $data['mac_address'] ?? null,
                    'procesador' => $data['especificaciones']['procesador'] ?? $data['procesador'] ?? null,
                    'ram' => $data['especificaciones']['ram'] ?? $data['ram'] ?? null,
                    'disco' => $data['especificaciones']['disco'] ?? $data['disco'] ?? null,
                    'sistema_operativo' => $data['especificaciones']['sistema_operativo'] ?? $data['sistema_operativo'] ?? null,
                    'especificaciones' => $singleSpecs,
                ]);

                if ($tipo === 'otro' && ! empty($data['id_producto'])) {
                    $itemData['id_producto'] = $data['id_producto'];
                }

                $primaryRecord = Inventario::create($itemData);
            }

            return $primaryRecord;
        });
    }

    public static function obtenerMensajeExito(?Inventario $record): string
    {
        if (! $record) {
            return 'Equipo registrado exitosamente';
        }

        $total = $record->especificaciones['_total_registrados'] ?? 1;
        $estacion = $record->estacion;

        if ($total > 1 && $estacion) {
            return "Se registraron {$total} equipos como estación {$estacion}";
        }

        return 'Equipo registrado exitosamente';
    }

    public static function getFormSteps(): array
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $isEdit = false;
        foreach ($backtrace as $frame) {
            $func = strtolower($frame['function'] ?? '');
            if (str_contains($func, 'edit') || str_contains($func, 'update')) {
                $isEdit = true;
                break;
            }
        }

        if (! $isEdit) {
            return [
                Forms\Components\Wizard\Step::make('Registro')
                    ->schema(static::getMockupFormSchema()),
            ];
        }

        return static::getEditFormSteps();
    }

    public static function getEditFormTabs(): array
    {
        return [
            Forms\Components\Tabs\Tab::make('Identificación y ubicación')
                ->icon('heroicon-m-identification')
                ->schema([
                    Forms\Components\Section::make('Información del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_equipo')
                                        ->label('Nombre (Material/Equipo)')
                                        ->placeholder('Ej. Impresora HP LaserJet Pro')
                                        ->required()
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->nombre ?? $record?->subtipo ?? ''))
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('marca')
                                        ->label('Marca')
                                        ->placeholder('Selecciona la marca...')
                                        ->options(fn () => MarcaMaterial::orderBy('nombre')->pluck('nombre', 'nombre'))
                                        ->searchable()
                                        ->preload()
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificaciones['marca'] ?? $record?->especificaciones['cpu_marca'] ?? $record?->material?->marca?->nombre ?? '')),

                                    Forms\Components\TextInput::make('modelo')
                                        ->label('Modelo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->modelo ?? $record?->material?->modelo ?? '—')),

                                    Forms\Components\TextInput::make('num_serie')
                                        ->label('No. de serie (S/N)')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('subtipo')
                                        ->label('Subtipo')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('categoria_display')
                                        ->label('Categoría / Tipo de equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')),

                                    Forms\Components\TextInput::make('especificacion_texto')
                                        ->label('Especificaciones')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificacion_texto ?? ($record?->especificaciones['especificaciones'] ?? '—')))
                                        ->columnSpan(2),

                                    Forms\Components\Textarea::make('observaciones_generales')
                                        ->label('Descripción del equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->rows(2)
                                        ->columnSpan(2),
                                ]),
                        ]),

                    Forms\Components\Section::make('Datos técnicos')
                        ->description('Hardware y configuración técnica (solo lectura)')
                        ->icon('heroicon-m-cpu-chip')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('procesador')
                                        ->label('Procesador')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->procesador ?? ($record?->especificaciones['procesador'] ?? '—'))),

                                    Forms\Components\TextInput::make('ram')
                                        ->label('Memoria RAM')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ram ?? ($record?->especificaciones['ram'] ?? '—'))),

                                    Forms\Components\TextInput::make('disco')
                                        ->label('Disco duro')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->disco ?? ($record?->especificaciones['disco'] ?? '—'))),

                                    Forms\Components\TextInput::make('sistema_operativo')
                                        ->label('Sistema operativo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->sistema_operativo ?? ($record?->especificaciones['sistema_operativo'] ?? '—'))),
                                ]),
                        ]),

                    Forms\Components\Section::make('Ubicación y Red')
                        ->description('Ubicación física y parámetros de red asignados')
                        ->icon('heroicon-m-map-pin')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('area_id')
                                        ->label('Área')
                                        ->placeholder('Selecciona el área...')
                                        ->options(fn () => Area::orderBy('nombre')->pluck('nombre', 'id_area'))
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\TextInput::make('aula')
                                        ->label('Aula')
                                        ->placeholder('Ej. E101')
                                        ->maxLength(5)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->salon?->nombre ?? ($record?->especificaciones['aula'] ?? null) ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record?->ubicacion_fisica ?? '', $m) ? $m[1] : null) ?? '')),

                                    Forms\Components\TextInput::make('ip_address')
                                        ->label('Dirección IP')
                                        ->placeholder('Ej. 192.168.1.100')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ip_address ?? ($record?->especificaciones['ip_address'] ?? ''))),

                                    Forms\Components\TextInput::make('mac_address')
                                        ->label('Dirección MAC')
                                        ->placeholder('Ej. AA:BB:CC:DD:EE:FF')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->mac_address ?? ($record?->especificaciones['mac_address'] ?? ''))),
                                ]),
                        ]),
                ]),

            Forms\Components\Tabs\Tab::make('Factura y proveedor')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\ToggleButtons::make('tipo_propiedad')
                                ->options([
                                    'Propio' => 'Propio',
                                    'Rentado' => 'Rentado',
                                ])
                                ->colors([
                                    'Propio' => 'primary',
                                    'Rentado' => 'gray',
                                ])
                                ->inline()
                                ->default('Propio')
                                ->live(),

                            Forms\Components\Select::make('id_proveedor')
                                ->label('Proveedor')
                                ->placeholder('Selecciona...')
                                ->relationship('proveedor', 'nombre_empresa')
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-truck')
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),

                            Forms\Components\TextInput::make('num_factura')
                                ->label('Número de factura')
                                ->placeholder('Ej. FAC-2026-001')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->maxLength(100),

                            Forms\Components\DatePicker::make('fecha_factura')
                                ->label('Fecha de factura')
                                ->prefixIcon('heroicon-m-calendar'),
                        ]),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('fecha_inicio_renta')
                                ->label('Inicio de renta')
                                ->prefixIcon('heroicon-m-calendar-days'),

                            Forms\Components\DatePicker::make('fecha_fin_renta')
                                ->label('Fin de renta')
                                ->prefixIcon('heroicon-m-calendar-days'),

                            Forms\Components\Textarea::make('observaciones_renta')
                                ->label('Observaciones de renta')
                                ->placeholder('Ej. Contrato de arrendamiento...')
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                ]),

            Forms\Components\Tabs\Tab::make('Garantía y observaciones')
                ->icon('heroicon-m-clipboard-document-check')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\DatePicker::make('fecha_registro')
                                ->label('Fecha de registro')
                                ->default(now())
                                ->prefixIcon('heroicon-m-clock'),

                            Forms\Components\DatePicker::make('garantia_fecha_fin')
                                ->label('Fin de garantía')
                                ->prefixIcon('heroicon-m-shield-check'),

                            Forms\Components\Select::make('garantia_estado')
                                ->label('Estado de garantía')
                                ->placeholder('Selecciona...')
                                ->options([
                                    'vigente' => 'Vigente',
                                    'vencida' => 'Vencida',
                                    'sin_garantia' => 'Sin Garantía',
                                ])
                                ->default('sin_garantia'),

                            Forms\Components\ToggleButtons::make('estado')
                                ->label('Estado del activo')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'Reasignado' => 'Reasignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                    'Baja' => 'Baja',
                                ])
                                ->colors([
                                    'Disponible' => 'success',
                                    'Asignado' => 'info',
                                    'Reasignado' => 'reassign',
                                    'En Mantenimiento' => 'warning',
                                    'Dañado' => 'danger',
                                    'Baja' => 'gray',
                                ])
                                ->inline(),
                        ]),

                    Forms\Components\Textarea::make('observaciones_adicionales')
                        ->label('Observaciones adicionales')
                        ->placeholder('Notas u observaciones sobre el estado...')
                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->observaciones_generales))
                        ->dehydrated(false)
                        ->rows(2),
                ]),
        ];
    }

    public static function getEditFormSteps(): array
    {
        return array_map(function (Forms\Components\Tabs\Tab $tab) {
            return Forms\Components\Wizard\Step::make($tab->getLabel())
                ->icon($tab->getIcon())
                ->schema($tab->getChildComponents());
        }, static::getEditFormTabs());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('departamento.nombre')
                    ->label('Departamento')
                    ->getStateUsing(function (Inventario $record): ?string {
                        return $record->departamento?->nombre 
                            ?? $record->area?->departamento?->nombre 
                            ?? ($record->ubicacion_fisica ? explode(' > ', $record->ubicacion_fisica)[0] ?? null : null)
                            ?? '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                              ->orWhereHas('area.departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                              ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('departamento', 'inventarios.departamento_id', '=', 'departamento.id_departamento')
                            ->orderBy('departamento.nombre', $direction)
                            ->select('inventarios.*');
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('equipo')
                    ->label('Equipo')
                    ->html()
                    ->getStateUsing(function (Inventario $record): string {
                        $nombre = $record->material?->nombre ?? $record->subtipo ?? 'Equipo';
                        $cod = !empty($record->codigo) ? $record->codigo : null;
                        if ($cod) {
                            $sub = str_starts_with($cod, 'ID: ') ? $cod : 'ID: ' . $cod;
                        } else {
                            $sub = 'ID: #' . ($record->id_inventario ?? $record->id);
                        }
                        return '<div class="td-main" style="font-weight:600;line-height:1.25;color:#111827;">' . e($nombre) . '</div>'
                            . '<div class="td-sub" style="font-size:11.5px;color:#6b7280;margin-top:1px;">' . e($sub) . '</div>';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('material', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                              ->orWhere('subtipo', 'like', "%{$search}%")
                              ->orWhere('codigo', 'like', "%{$search}%")
                              ->orWhere('num_serie', 'like', "%{$search}%")
                              ->orWhere('modelo', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('materiales', 'inventarios.id_producto', '=', 'materiales.id_producto')
                            ->orderBy('materiales.nombre', $direction)
                            ->select('inventarios.*');
                    }),

                Tables\Columns\TextColumn::make('categoria')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray')
                    ->getStateUsing(fn (Inventario $record): string => $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('material.tipo', fn ($sub) => $sub->where('nombre', 'like', trim($search) . '%'))
                              ->orWhere('subtipo', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('materiales', 'inventarios.id_producto', '=', 'materiales.id_producto')
                            ->leftJoin('tipos_material', 'materiales.id_tipo', '=', 'tipos_material.id_tipo')
                            ->orderBy('tipos_material.nombre', $direction)
                            ->select('inventarios.*');
                    }),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->html()
                    ->getStateUsing(function (Inventario $record): string {
                        $estado = $record->estado ?? 'Disponible';
                        $colors = match ($estado) {
                            'Disponible' => [
                                'bg' => '#d1fae5',
                                'text' => '#065f46',
                                'border' => '#a7f3d0',
                                'dot' => '#059669',
                                'class' => 'ok',
                            ],
                            'En mantenimiento', 'En Mantenimiento', 'Cambio de área' => [
                                'bg' => '#fef3c7',
                                'text' => '#92400e',
                                'border' => '#fde68a',
                                'dot' => '#d97706',
                                'class' => 'warn',
                            ],
                            'Asignado', 'Reasignado' => [
                                'bg' => '#e0f2fe',
                                'text' => '#075985',
                                'border' => '#bae6fd',
                                'dot' => '#0284c7',
                                'class' => 'info',
                            ],
                            'Dañado', 'Baja', 'Solicitud de baja' => [
                                'bg' => '#fee2e2',
                                'text' => '#991b1b',
                                'border' => '#fecaca',
                                'dot' => '#dc2626',
                                'class' => 'danger',
                            ],
                            default => [
                                'bg' => '#f3f4f6',
                                'text' => '#374151',
                                'border' => '#e5e7eb',
                                'dot' => '#9ca3af',
                                'class' => 'neutral',
                            ],
                        };

                        return '<span class="pill ' . $colors['class'] . '" style="display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap;background-color:' . $colors['bg'] . ';color:' . $colors['text'] . ';border:1px solid ' . $colors['border'] . ';">'
                            . '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background-color:' . $colors['dot'] . ';flex-shrink:0;"></span>'
                            . e($estado)
                            . '</span>';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('area.nombre')
                    ->label('Área')
                    ->getStateUsing(function (Inventario $record): ?string {
                        if ($record->area?->nombre) {
                            return $record->area->nombre;
                        }
                        if (! empty($record->ubicacion_fisica)) {
                            $parts = explode(' > ', $record->ubicacion_fisica);
                            return $parts[1] ?? $parts[0] ?? null;
                        }
                        return '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('area', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                              ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('areas', 'inventarios.area_id', '=', 'areas.id_area')
                            ->orderBy('areas.nombre', $direction)
                            ->select('inventarios.*');
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('aula')
                    ->label('Aula')
                    ->getStateUsing(function (Inventario $record): string {
                        return $record->salon?->nombre 
                            ?? ($record->especificaciones['aula'] ?? null)
                            ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                            ?? '—';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('salon', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                              ->orWhere('especificaciones->aula', 'like', "%{$search}%")
                              ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->leftJoin('salones', 'inventarios.salon_id', '=', 'salones.id')
                            ->orderBy('salones.nombre', $direction)
                            ->select('inventarios.*');
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('num_serie')
                    ->label('No. de serie')
                    ->searchable(isIndividual: true)
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->getStateUsing(fn (Inventario $record): string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('ip_address', 'like', "%{$search}%")
                              ->orWhere('especificaciones->ip_address', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('mac_address')
                    ->label('MAC')
                    ->getStateUsing(fn (Inventario $record): string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('mac_address', 'like', "%{$search}%")
                              ->orWhere('especificaciones->mac_address', 'like', "%{$search}%");
                        });
                    }, isIndividual: true)
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por equipo, código, IP, MAC, marca...')
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([])
            ->modifyQueryUsing(function (Builder $query, $livewire): Builder {
                $desde = $livewire->tableColumnSearches['fecha_desde'] ?? null;
                $hasta = $livewire->tableColumnSearches['fecha_hasta'] ?? null;
                if (filled($desde)) {
                    $query->where(function (Builder $q) use ($desde) {
                        $q->whereDate('fecha_registro', '>=', $desde)
                          ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '>=', $desde));
                    });
                }
                if (filled($hasta)) {
                    $query->where(function (Builder $q) use ($hasta) {
                        $q->whereDate('fecha_registro', '<=', $hasta)
                          ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '<=', $hasta));
                    });
                }
                $estadoResumen = $livewire->tableFilters['estado']['value'] ?? null;
                if (filled($estadoResumen)) {
                    $query->where('estado', $estadoResumen);
                }
                return $query;
            })
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('↓ Exportar')
                    ->color('gray')
                    ->action(fn () => null),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle', 'class' => 'icon-btn'])
                    ->slideOver()
                    ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categoría')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Modificar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('update', $record))
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_cambio')
                            ->label('Cambio')
                            ->color('info')
                            ->icon('heroicon-m-arrows-right-left')
                            ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('update', $record))
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_baja')
                            ->label('Baja')
                            ->color('danger')
                            ->icon('heroicon-m-arrow-down-circle')
                            ->visible(fn (?Inventario $record) => $record && auth()->user()?->can('delete', $record))
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Modificar')
                    ->extraAttributes(['title' => 'Modificar', 'class' => 'icon-btn'])
                    ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
                    ))
                    ->slideOver()
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalCancelActionLabel('Cancelar')
                    ->mutateRecordDataUsing(function (array $data, Inventario $record): array {
                        $data['nombre_equipo'] = $record->material?->nombre ?? $record->subtipo ?? '';
                        $data['marca'] = $record->especificaciones['marca'] 
                            ?? $record->especificaciones['cpu_marca'] 
                            ?? $record->material?->marca?->nombre 
                            ?? '';
                        $data['aula'] = $record->salon?->nombre 
                            ?? ($record->especificaciones['aula'] ?? null) 
                            ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null) 
                            ?? '';
                        $data['categoria_display'] = $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General';
                        $data['especificacion_texto'] = $record->especificacion_texto ?? ($record->especificaciones['especificaciones'] ?? '');
                        $data['procesador'] = $record->procesador ?? ($record->especificaciones['procesador'] ?? '');
                        $data['ram'] = $record->ram ?? ($record->especificaciones['ram'] ?? '');
                        $data['disco'] = $record->disco ?? ($record->especificaciones['disco'] ?? '');
                        $data['sistema_operativo'] = $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? '');
                        $data['ip_address'] = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? '');
                        $data['mac_address'] = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? '');
                        return $data;
                    })
                    ->using(function (Inventario $record, array $data): Inventario {
                        // 1. Nombre (Material/Equipo)
                        if (isset($data['nombre_equipo']) && filled($data['nombre_equipo'])) {
                            $nuevoNombre = trim($data['nombre_equipo']);
                            $record->subtipo = $nuevoNombre;
                            if ($record->material) {
                                $record->material->update(['nombre' => $nuevoNombre]);
                            }
                        }

                        // 2. Marca
                        if (isset($data['marca']) && filled($data['marca'])) {
                            $nuevaMarca = trim($data['marca']);
                            $specs = $record->especificaciones ?? [];
                            $specs['marca'] = $nuevaMarca;
                            $record->especificaciones = $specs;

                            $marcaModel = MarcaMaterial::firstOrCreate(['nombre' => $nuevaMarca]);
                            if ($record->material) {
                                $record->material->update(['id_marca' => $marcaModel->id_marca]);
                            }
                        }

                        // 3. Aula
                        if (isset($data['aula'])) {
                            $nuevaAula = trim($data['aula']);
                            $specs = $record->especificaciones ?? [];
                            $specs['aula'] = $nuevaAula;
                            $record->especificaciones = $specs;

                            if ($record->area_id && filled($nuevaAula)) {
                                $salon = Salon::where('area_id', $record->area_id)
                                    ->where('nombre', $nuevaAula)
                                    ->first();
                                if ($salon) {
                                    $record->salon_id = $salon->id;
                                }
                            }
                        }

                        // 4. IP & MAC
                        if (isset($data['ip_address'])) {
                            $record->ip_address = $data['ip_address'];
                            $specs = $record->especificaciones ?? [];
                            $specs['ip_address'] = $data['ip_address'];
                            $record->especificaciones = $specs;
                        }
                        if (isset($data['mac_address'])) {
                            $record->mac_address = $data['mac_address'];
                            $specs = $record->especificaciones ?? [];
                            $specs['mac_address'] = $data['mac_address'];
                            $record->especificaciones = $specs;
                        }

                        // 5. Área
                        if (isset($data['area_id']) && filled($data['area_id'])) {
                            $record->area_id = $data['area_id'];
                            $area = Area::find($data['area_id']);
                            if ($area && $area->id_departamento) {
                                $record->departamento_id = $area->id_departamento;
                            }
                        }

                        // 6. Fill other allowable fields
                        $record->fill($data);
                        $record->save();

                        return $record;
                    })
                    ->successNotificationTitle('Registro actualizado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('aprobarMasivo')
                        ->label('Aprobar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()->can('aprobaciones.aprobar'))
                        ->action(function (Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if (! $record->aprobado) {
                                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                                    }
                                }
                            });
                            Notification::make()->title('Registros aprobados exitosamente')->success()->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Registro de inventario eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Detalle')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Infolists\Components\Section::make('Identificación del Equipo')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tipo_equipo')
                                            ->label('Tipo de equipo')
                                            ->badge()
                                            ->color('primary')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('subtipo')
                                            ->label('Subtipo')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_serie')
                                            ->label('Número de serie')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-qr-code')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('material.nombre')
                                            ->label('Material / Equipo')
                                            ->icon('heroicon-m-cube')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Disponible' => 'success',
                                                'Asignado' => 'info',
                                                'Reasignado' => 'reassign',
                                                'En Mantenimiento' => 'warning',
                                                'Dañado' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('tipo_propiedad')
                                            ->label('Propiedad')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Propio' => 'primary',
                                                'Rentado' => 'gray',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('usuario.name')
                                            ->label('Registrado por')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Ubicación y Asignación')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                                            ->label('Ubicación física descriptiva')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('fecha_asignacion')
                                            ->label('Fecha de asignación')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_cambio_area')
                                            ->label('Fecha de cambio de área')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar-days')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Red y Conectividad')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('ip_address')
                                            ->label('Dirección IP')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-globe-alt')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('mac_address')
                                            ->label('Dirección MAC')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-signal')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('procesador')
                                            ->label('Procesador')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('ram')
                                            ->label('Memoria RAM')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('disco')
                                            ->label('Disco duro')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Componentes de la Estación')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('especificaciones.cpu_marca')->label('Marca CPU')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.cpu_sn')->label('S/N CPU')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.monitor_marca')->label('Marca Monitor')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.monitor_sn')->label('S/N Monitor')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.teclado_marca')->label('Marca Teclado')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.teclado_sn')->label('S/N Teclado')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.mouse_marca')->label('Marca Mouse')->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificaciones.mouse_sn')->label('S/N Mouse')->placeholder('Por capturar'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (?Inventario $record) => in_array($record?->tipo_equipo, ['Desktop', 'All in One', 'Computadora'])),

                                Infolists\Components\Section::make('Observaciones')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('observaciones_generales')
                                            ->label('Observaciones generales')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Historial')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('historial')
                                    ->label('')
                                    ->getStateUsing(fn (?Inventario $record) => $record ? \App\Models\HistorialEstado::where('id_inventario', $record->id_inventario)->orderByDesc('fecha_cambio')->get() : [])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('fecha_cambio')
                                            ->label('Fecha')
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('estado_anterior')
                                            ->label('Anterior')
                                            ->badge()
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('estado_nuevo')
                                            ->label('Nuevo')
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('motivo_cambio')
                                            ->label('Motivo')
                                            ->placeholder('Sin motivo registrado'),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->placeholder('Sin historial de cambios registrado'),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Documentación')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Infolists\Components\Section::make('Factura y proveedor')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('proveedor.nombre_empresa')
                                            ->label('Proveedor')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_factura')
                                            ->label('Factura')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_factura')
                                            ->label('Fecha de factura')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Garantía')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('garantia_estado')
                                            ->label('Estado de garantía')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . ucfirst(str_replace('_', ' ', $state)) : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'vigente' => 'success',
                                                'por_vencer' => 'warning',
                                                'vencida' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('garantia_fecha_fin')
                                            ->label('Fin de garantía')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Archivos adjuntos')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('archivos')
                                            ->label('')
                                            ->default('Sin archivos digitales adjuntos')
                                            ->getStateUsing(fn () => 'Sin archivos digitales adjuntos')
                                            ->color('gray'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }

    public static function actualizarUbicacionFisica(Forms\Set $set, Forms\Get $get): void
    {
        $partes = [];
        if ($edificioId = $get('edificio_id')) {
            $edificio = Edificio::find($edificioId);
            if ($edificio) {
                $partes[] = $edificio->letra ? "Edif. {$edificio->letra} ({$edificio->nombre})" : $edificio->nombre;
            }
        }
        if ($deptoId = $get('departamento_id')) {
            $depto = Departamento::find($deptoId);
            if ($depto) {
                $partes[] = $depto->nombre;
            }
        }
        if ($areaId = $get('area_id')) {
            $area = Area::find($areaId);
            if ($area) {
                $partes[] = $area->nombre;
            }
        }
        if ($salonId = $get('salon_id')) {
            $salon = Salon::find($salonId);
            if ($salon) {
                $partes[] = $salon->nombre;
            }
        }

        if (! empty($partes)) {
            $set('ubicacion_fisica', implode(' > ', $partes));
        }
    }
}
