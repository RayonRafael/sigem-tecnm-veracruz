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

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Identificación y ubicación')
                        ->icon('heroicon-m-identification')
                        ->schema([
                            Forms\Components\Grid::make(3)
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
                                        ->prefixIcon('heroicon-m-cube'),
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

                            Forms\Components\Section::make('Jerarquía de Ubicación')
                                ->description('Selecciona la ubicación física estructurada del activo')
                                ->icon('heroicon-m-map-pin')
                                ->schema([
                                    Forms\Components\Grid::make(4)
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

                            Forms\Components\ToggleButtons::make('estado')->label('Estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                    'Baja' => 'Baja',
                                    'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                                ])
                                ->colors([
                                    'Disponible' => 'success',
                                    'Asignado' => 'warning',
                                    'En Mantenimiento' => 'gray',
                                    'Dañado' => 'danger',
                                    'Baja' => 'gray',
                                    'Devuelto a Proveedor' => 'info',
                                ])
                                ->inline()
                                ->default('Disponible')
                                ->required()
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
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        ]),

                    Forms\Components\Wizard\Step::make('Garantía y observaciones')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([

                            Forms\Components\Grid::make(3)
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
                                        ->rows(2),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie')
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('material.nombre')->wrap(false)->limit(40)->grow(false)
                    ->label('Equipo / Material')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Inventario $record): ?string => $record->num_serie ? "S/N: {$record->num_serie}" : null),
                Tables\Columns\TextColumn::make('material.marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('material.tipo.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ubicacion_fisica')->wrap(false)->limit(30)->grow(false)
                    ->label('Ubicación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)->label('Estado')
                    ->badge()
                    ->colors([
                        'success' => 'Disponible',
                        'warning' => 'En Mantenimiento',
                        'info' => 'Asignado',
                        'danger' => 'Dañado',
                        'gray' => ['Baja', 'Devuelto a Proveedor'],
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Disponible',
                        'heroicon-m-wrench' => 'En Mantenimiento',
                        'heroicon-m-user' => 'Asignado',
                        'heroicon-m-exclamation-triangle' => 'Dañado',
                        'heroicon-m-minus-circle' => 'Baja',
                        'heroicon-m-arrow-path' => 'Devuelto a Proveedor',
                    ]),
                Tables\Columns\TextColumn::make('tipo_propiedad')->wrap(false)->limit(30)->grow(false)
                    ->label('Propiedad')
                    ->badge()
                    ->colors([
                        'primary' => 'Propio',
                        'gray' => 'Rentado',
                    ])
                    ->icons([
                        'heroicon-m-building-library' => 'Propio',
                        'heroicon-m-currency-dollar' => 'Rentado',
                    ])
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
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('aprobado')
                    ->label('Estado de aprobación'),
                Tables\Filters\SelectFilter::make('estado')->label('Estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Asignado' => 'Asignado',
                        'En Mantenimiento' => 'En Mantenimiento',
                        'Dañado' => 'Dañado',
                        'Baja' => 'Baja',
                        'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_propiedad')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ]),
            ])

            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.aprobar'))
                    ->action(function (Inventario $record) {
                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                        Notification::make()->title('Registro aprobado exitosamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Inventario $record): bool => ! $record->aprobado && auth()->user()->can('aprobaciones.rechazar'))
                    ->action(function (Inventario $record) {
                        $record->update([
                            'estado_registro' => 'Rechazado',
                            'aprobado' => false,
                        ]);
                        Notification::make()->title('Registro rechazado exitosamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()->slideOver()->successNotificationTitle('Registro de inventario actualizado correctamente'),
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
                Infolists\Components\Section::make('Identificación del Equipo')
                    ->schema([
                        Infolists\Components\TextEntry::make('tipo_equipo')
                            ->label('Tipo de equipo')
                            ->badge()
                            ->color('primary')
                            ->placeholder('No definido'),
                        Infolists\Components\TextEntry::make('num_serie')
                            ->label('Número de serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code')
                            ->placeholder('Sin número de serie'),
                        Infolists\Components\TextEntry::make('material.nombre')
                            ->label('Material / Equipo')
                            ->icon('heroicon-m-cube'),
                        Infolists\Components\TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Disponible' => 'success',
                                'Asignado' => 'warning',
                                'Dañado' => 'danger',
                                'Devuelto a Proveedor' => 'info',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('tipo_propiedad')
                            ->label('Propiedad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Propio' => 'primary',
                                'Rentado' => 'gray',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('usuario.name')
                            ->label('Registrado por')
                            ->icon('heroicon-m-user'),
                    ])->columns(2),

                Infolists\Components\Section::make('Ubicación Física')
                    ->schema([
                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                            ->label('Ubicación física descriptiva')
                            ->icon('heroicon-m-map-pin')
                            ->placeholder('No especificada')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Componentes de la Estación')
                    ->schema([
                        Infolists\Components\TextEntry::make('especificaciones.cpu_marca')->label('Marca CPU')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.cpu_sn')->label('S/N CPU')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.monitor_marca')->label('Marca Monitor')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.monitor_sn')->label('S/N Monitor')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.teclado_marca')->label('Marca Teclado')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.teclado_sn')->label('S/N Teclado')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.mouse_marca')->label('Marca Mouse')->placeholder('—'),
                        Infolists\Components\TextEntry::make('especificaciones.mouse_sn')->label('S/N Mouse')->placeholder('—'),
                    ])
                    ->columns(4)
                    ->visible(fn ($record) => in_array($record->tipo_equipo, ['Desktop', 'All in One', 'Computadora'])),

                Infolists\Components\Section::make('Detalles adicionales')
                    ->schema([
                        Infolists\Components\TextEntry::make('proveedor.nombre_empresa')->label('Proveedor')->placeholder('—'),
                        Infolists\Components\TextEntry::make('num_factura')->label('Factura')->placeholder('—'),
                        Infolists\Components\TextEntry::make('garantia_estado')->label('Garantía')->badge(),
                        Infolists\Components\TextEntry::make('observaciones_generales')->label('Observaciones')->placeholder('Sin observaciones')->columnSpanFull(),
                    ])->columns(3)->collapsible(),
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
