<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Edificio;
use App\Models\Inventario;
use App\Models\Salon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        return $form
            ->schema([
                Forms\Components\Wizard::make(static::getFormSteps())
                    ->columnSpanFull(),
            ]);
    }

    public static function getFormSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('Identificación y ubicación')
                ->icon('heroicon-m-identification')
                ->schema([
                    Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('num_serie')
                                        ->label('Número de serie')
                                        ->prefixIcon('heroicon-m-qr-code')
                                        ->placeholder('Ej. SN-12345')
                                        ->maxLength(100),
                                    Forms\Components\Select::make('id_producto')
                                        ->placeholder('Selecciona...')
                                        ->label('Material / equipo')
                                        ->relationship('material', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-cube')
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('tipo_equipo')
                                        ->label('Tipo de equipo')
                                        ->placeholder('Selecciona el tipo...')
                                        ->options([
                                            'Desktop' => 'Desktop',
                                            'All in One' => 'All in One',
                                            'Computadora' => 'Computadora',
                                            'Laptop' => 'Laptop',
                                            'Servidor' => 'Servidor',
                                            'Impresora' => 'Impresora',
                                            'Switch' => 'Switch / Red',
                                            'Proyector' => 'Proyector',
                                            'Otro' => 'Otro',
                                        ])
                                        ->live()
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-computer-desktop'),
                                    // TODO: Convertir en Select con opciones dependientes de tipo_equipo (ej. Computadora -> Escritorio / Portátil)
                                    Forms\Components\TextInput::make('subtipo')
                                        ->label('Subtipo')
                                        ->placeholder('Ej. Escritorio, Portátil, Alámbrico...')
                                        ->required()
                                        ->maxLength(100)
                                        ->prefixIcon('heroicon-m-tag'),
                                ]),

                            Forms\Components\Section::make('Componentes de la Estación')
                                ->description('Detalles y periféricos asignados a esta computadora / estación de trabajo')
                                ->icon('heroicon-m-computer-desktop')
                                ->collapsible()
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['Desktop', 'All in One', 'Computadora']))
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('especificaciones.cpu_marca')
                                                ->label('CPU / Gabinete - Marca')
                                                ->placeholder('Ej. Dell, HP, Lenovo')
                                                ->prefixIcon('heroicon-m-cpu-chip'),
                                            Forms\Components\TextInput::make('especificaciones.cpu_sn')
                                                ->label('CPU / Gabinete - No. de Serie')
                                                ->placeholder('Ej. SN-CPU-9876')
                                                ->prefixIcon('heroicon-m-hashtag'),

                                            Forms\Components\TextInput::make('especificaciones.monitor_marca')
                                                ->label('Monitor - Marca')
                                                ->placeholder('Ej. LG, Samsung, Dell')
                                                ->prefixIcon('heroicon-m-tv'),
                                            Forms\Components\TextInput::make('especificaciones.monitor_sn')
                                                ->label('Monitor - No. de Serie')
                                                ->placeholder('Ej. SN-MON-5432')
                                                ->prefixIcon('heroicon-m-hashtag'),

                                            Forms\Components\TextInput::make('especificaciones.teclado_marca')
                                                ->label('Teclado - Marca')
                                                ->placeholder('Ej. Logitech, Genius, HP')
                                                ->prefixIcon('heroicon-m-command-line'),
                                            Forms\Components\TextInput::make('especificaciones.teclado_sn')
                                                ->label('Teclado - No. de Serie')
                                                ->placeholder('Ej. SN-KB-1122')
                                                ->prefixIcon('heroicon-m-hashtag'),

                                            Forms\Components\TextInput::make('especificaciones.mouse_marca')
                                                ->label('Mouse - Marca')
                                                ->placeholder('Ej. Logitech, Microsoft')
                                                ->prefixIcon('heroicon-m-cursor-arrow-rays'),
                                            Forms\Components\TextInput::make('especificaciones.mouse_sn')
                                                ->label('Mouse - No. de Serie')
                                                ->placeholder('Ej. SN-MS-3344')
                                                ->prefixIcon('heroicon-m-hashtag'),
                                        ]),
                                ])
                                ->columnSpanFull(),

                            // TODO: Hacer dependientes del tipo Computadora según el requerimiento del cliente
                            Forms\Components\Section::make('Especificaciones Técnicas y Red')
                                ->description('Hardware principal y conectividad de red')
                                ->icon('heroicon-m-cpu-chip')
                                ->collapsible()
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('procesador')
                                                ->label('Procesador')
                                                ->placeholder('Ej. Intel Core i5')
                                                ->prefixIcon('heroicon-m-cpu-chip')
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('ram')
                                                ->label('Memoria RAM')
                                                ->placeholder('Ej. 8 GB DDR4')
                                                ->prefixIcon('heroicon-m-circle-stack')
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('disco')
                                                ->label('Disco duro')
                                                ->placeholder('Ej. 512GB SSD')
                                                ->prefixIcon('heroicon-m-circle-stack')
                                                ->maxLength(100)
                                                ->columnSpanFull(),
                                        ]),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('mac_address')
                                                ->label('Dirección MAC')
                                                ->placeholder('Ej. AA:BB:CC:DD:EE:FF')
                                                ->prefixIcon('heroicon-m-signal')
                                                ->maxLength(50),
                                            Forms\Components\TextInput::make('ip_address')
                                                ->label('Dirección IP')
                                                ->placeholder('Ej. 192.168.1.100')
                                                ->prefixIcon('heroicon-m-globe-alt')
                                                ->maxLength(50),
                                        ]),
                                ])
                                ->columnSpanFull(),

                            Forms\Components\Section::make('Jerarquía de Ubicación')
                                ->description('Selecciona la ubicación física estructurada del activo')
                                ->icon('heroicon-m-map-pin')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('edificio_id')
                                                ->label('Edificio')
                                                ->placeholder('Selecciona edificio...')
                                                ->options(fn () => Edificio::orderBy('nombre')->pluck('nombre', 'id'))
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $set('departamento_id', null);
                                                    $set('area_id', null);
                                                    $set('salon_id', null);
                                                    static::actualizarUbicacionFisica($set, $get);
                                                })
                                                ->prefixIcon('heroicon-m-building-office-2'),

                                            Forms\Components\Select::make('departamento_id')
                                                ->label('Departamento')
                                                ->placeholder(fn (Forms\Get $get) => $get('edificio_id') ? 'Selecciona depto...' : 'Elige edificio primero')
                                                ->options(function (Forms\Get $get) {
                                                    $edificioId = $get('edificio_id');
                                                    if (! $edificioId) {
                                                        return [];
                                                    }
                                                    return Departamento::where('edificio_id', $edificioId)
                                                        ->orderBy('nombre')
                                                        ->pluck('nombre', 'id_departamento');
                                                })
                                                ->searchable()
                                                ->live()
                                                ->disabled(fn (Forms\Get $get) => blank($get('edificio_id')))
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $set('area_id', null);
                                                    $set('salon_id', null);
                                                    static::actualizarUbicacionFisica($set, $get);
                                                })
                                                ->prefixIcon('heroicon-m-building-office'),

                                            Forms\Components\Select::make('area_id')
                                                ->label('Área')
                                                ->placeholder(fn (Forms\Get $get) => $get('departamento_id') ? 'Selecciona área...' : 'Elige depto primero')
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
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $set('salon_id', null);
                                                    static::actualizarUbicacionFisica($set, $get);
                                                })
                                                ->prefixIcon('heroicon-m-rectangle-group'),

                                            Forms\Components\Select::make('salon_id')
                                                ->label('Salón')
                                                ->placeholder(fn (Forms\Get $get) => $get('area_id') ? 'Selecciona salón...' : 'Elige área primero')
                                                ->options(function (Forms\Get $get) {
                                                    $areaId = $get('area_id');
                                                    if (! $areaId) {
                                                        return [];
                                                    }
                                                    return Salon::where('area_id', $areaId)
                                                        ->orderBy('nombre')
                                                        ->pluck('nombre', 'id');
                                                })
                                                ->searchable()
                                                ->live()
                                                ->disabled(fn (Forms\Get $get) => blank($get('area_id')))
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    static::actualizarUbicacionFisica($set, $get);
                                                })
                                                ->prefixIcon('heroicon-m-academic-cap'),
                                        ]),

                                    Forms\Components\TextInput::make('ubicacion_fisica')
                                        ->label('Ubicación física descriptiva')
                                        ->placeholder('Se genera automáticamente a partir de la jerarquía o ingresa detalles adicionales...')
                                        ->prefixIcon('heroicon-m-map-pin')
                                        ->maxLength(150)
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),

                            // TODO: En el historial del equipo, registrar automáticamente el estado 'Reasignado' al cambiar de área o persona.
                            Forms\Components\ToggleButtons::make('estado')->label('Estado')
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
                                ->inline()
                                ->default('Disponible')
                                ->live()
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_asignacion')
                                        ->label('Fecha de asignación / entrega')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->visible(fn (Forms\Get $get) => $get('estado') === 'Asignado')
                                        ->required(fn (Forms\Get $get) => $get('estado') === 'Asignado'),
                                    Forms\Components\DatePicker::make('fecha_cambio_area')
                                        ->label('Fecha de cambio de área')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('estado') === 'Reasignado')
                                        ->required(fn (Forms\Get $get) => $get('estado') === 'Reasignado'),
                                ])
                                ->visible(fn (Forms\Get $get) => in_array($get('estado'), ['Asignado', 'Reasignado']))
                                ->columnSpanFull(),

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
                                        ->live()
                                        ->required(),
                                    Forms\Components\Select::make('id_usuario')->placeholder('Selecciona...')
                                        ->label('Registrado por (usuario)')
                                        ->relationship('usuario', 'name')
                                        ->default(auth()->id())
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user'),
                                ]),
                        ]),

                    Forms\Components\Wizard\Step::make('Factura y proveedor')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('id_proveedor')->placeholder('Selecciona...')
                                        ->label('Proveedor')
                                        ->relationship('proveedor', 'nombre_empresa')
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-truck')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado')
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nombre_empresa')
                                                ->label('Nombre de la empresa')
                                                ->required()
                                                ->maxLength(150),
                                            Forms\Components\TextInput::make('rfc')
                                                ->label('Rfc')
                                                ->maxLength(13),
                                            Forms\Components\TextInput::make('contacto_nombre')
                                                ->label('Nombre del contacto')
                                                ->maxLength(150),
                                            Forms\Components\TextInput::make('contacto_telefono')
                                                ->label('Teléfono del contacto')
                                                ->tel()
                                                ->maxLength(20),
                                            Forms\Components\TextInput::make('contacto_email')
                                                ->label('Email del contacto')
                                                ->email()
                                                ->maxLength(100),
                                        ]),
                                    Forms\Components\TextInput::make('num_factura')
                                        ->label('Número de factura')
                                        ->placeholder('Ej. FAC-2026-001')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->maxLength(100)
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                                    Forms\Components\DatePicker::make('fecha_factura')
                                        ->label('Fecha de factura')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
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
                                        ->placeholder('Ej. Contrato anual de arrendamiento...')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        ]),

                    Forms\Components\Wizard\Step::make('Garantía y observaciones')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_registro')
                                        ->label('Fecha de registro')
                                        ->default(now())
                                        ->required()
                                        ->prefixIcon('heroicon-m-clock'),
                                    Forms\Components\DatePicker::make('garantia_fecha_fin')
                                        ->label('Fin de garantía')
                                        ->prefixIcon('heroicon-m-shield-check'),
                                    Forms\Components\Select::make('garantia_estado')->placeholder('Selecciona...')
                                        ->label('Estado de garantía')
                                        ->options([
                                            'vigente' => 'Vigente',
                                            'vencida' => 'Vencida',
                                            'sin_garantia' => 'Sin Garantía',
                                        ])
                                        ->default('sin_garantia'),
                                ]),

                            Forms\Components\Grid::make(1)
                                ->schema([
                                    Forms\Components\Select::make('estado_registro')->placeholder('Selecciona...')
                                        ->label('Estado de registro')
                                        ->options([
                                            'Pendiente' => 'Pendiente',
                                            'Aprobado' => 'Aprobado',
                                            'Rechazado' => 'Rechazado',
                                        ])
                                        ->default('Pendiente')
                                        ->required()
                                        ->visible(fn () => auth()->user() && auth()->user()->hasPermissionTo(RoleEnum::PERM_ACCESS_ADMIN)),
                                    Forms\Components\Textarea::make('observaciones_generales')
                                        ->label('Observaciones generales')
                                        ->placeholder('Ej. Equipo en buen estado físico...')
                                        ->rows(2),
                                ]),
                        ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')
                    ->label('CÓDIGO')
                    ->searchable(isIndividual: true)
                    ->weight('bold')
                    ->width('130px'),
                Tables\Columns\TextColumn::make('material.nombre')
                    ->label('EQUIPO')
                    ->searchable(isIndividual: true)
                    ->sortable()
                    ->grow()
                    ->description(fn (Inventario $record): ?string => $record->num_serie ? "S/N: {$record->num_serie}" : null),
                Tables\Columns\TextColumn::make('material.tipo.nombre')
                    ->label('CATEGORÍA')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('material.tipo', fn (Builder $q) => $q->where('nombre', 'like', trim($search) . '%'));
                    }, isIndividual: true)
                    ->sortable()
                    ->width('130px'),
                Tables\Columns\TextColumn::make('estado')->label('ESTADO')
                    ->badge()
                    ->colors([
                        'success' => 'Disponible',
                        'info' => 'Asignado',
                        'reassign' => 'Reasignado',
                        'warning' => 'En Mantenimiento',
                        'danger' => 'Dañado',
                        'gray' => ['Baja', 'Devuelto a Proveedor'],
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Disponible',
                        'heroicon-m-user' => 'Asignado',
                        'heroicon-m-arrows-right-left' => 'Reasignado',
                        'heroicon-m-wrench' => 'En Mantenimiento',
                        'heroicon-m-exclamation-triangle' => 'Dañado',
                        'heroicon-m-minus-circle' => 'Baja',
                    ])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true)
                    ->width('140px'),
                Tables\Columns\TextColumn::make('tipo_propiedad')
                    ->label('PROPIEDAD')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                    ->color(fn (?string $state): string => $state === 'Propio' ? 'success' : 'info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tipo_propiedad', $search);
                    }, isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('ubicacion_fisica')
                    ->label('ÁREA')
                    ->searchable(isIndividual: true)
                    ->placeholder('—')
                    ->width('120px'),
                Tables\Columns\TextColumn::make('material.marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('procesador')->wrap(false)->limit(30)->grow(false)
                    ->label('Procesador')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ram')->wrap(false)->limit(30)->grow(false)
                    ->label('RAM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('disco')->wrap(false)->limit(30)->grow(false)
                    ->label('Disco')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('mac_address')->wrap(false)->limit(30)->grow(false)
                    ->label('MAC')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')->wrap(false)->limit(30)->grow(false)
                    ->label('IP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('aprobado')->wrap(false)->limit(30)->grow(false)
                    ->label('Aprobado')
                    ->formatStateUsing(fn ($state) => $state ? 'Aprobado' : 'Pendiente')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->icon(fn ($state) => $state ? 'heroicon-m-shield-check' : 'heroicon-m-clock')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('aprobadoPor.name')->wrap(false)->limit(30)->grow(false)
                    ->label('Aprobado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_aprobacion')->wrap(false)->limit(30)->grow(false)
                    ->label('Fecha aprobación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? 'Detalle de equipo')
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? 'Sin categoría')
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
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? 'equipo'))
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->num_serie ?? '—')
                    ))
                    ->slideOver()
                    ->successNotificationTitle('Registro actualizado correctamente'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Eliminar')
                    ->extraAttributes(['title' => 'Eliminar'])
                    ->modalHeading(fn (?Inventario $record) => '¿Eliminar ' . ($record?->material?->nombre ?? 'equipo') . '?')
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->num_serie ?? '—') . ' · Esta acción no se puede deshacer'
                    ))
                    ->successNotificationTitle('Registro eliminado correctamente'),
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
