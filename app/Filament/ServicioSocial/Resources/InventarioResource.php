<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'activo';

    protected static ?string $pluralModelLabel = 'activos';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 3;

    public static function hasRecordTitle(): bool
    {
        return false;
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
                    Forms\Components\TextInput::make('num_serie')
                        ->label('Número de serie')
                        ->prefixIcon('heroicon-m-qr-code')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(1),
                    Forms\Components\Select::make('id_producto')
                        ->label('Material / equipo')
                        ->relationship('material', 'nombre')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-m-cube')
                        ->columnSpan(1),
                    // TODO: Convertir en Select con opciones dependientes de tipo_equipo (ej. Computadora -> Escritorio / Portátil)
                    Forms\Components\TextInput::make('subtipo')
                        ->label('Subtipo')
                        ->placeholder('Ej. Escritorio, Portátil, Alámbrico...')
                        ->required()
                        ->maxLength(100)
                        ->prefixIcon('heroicon-m-tag')
                        ->columnSpan(1),
                    // TODO: En el historial del equipo, registrar automáticamente el estado 'Reasignado' al cambiar de área o persona.
                    Forms\Components\ToggleButtons::make('estado')
                        ->label('Estado')
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
                    // TODO: Hacer dependientes del tipo Computadora según el requerimiento del cliente
                    Forms\Components\Section::make('Especificaciones Técnicas y Red')
                        ->description('Hardware principal y conectividad de red')
                        ->icon('heroicon-m-cpu-chip')
                        ->collapsible()
                        ->schema([
                            Forms\Components\Grid::make(3)
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
                                        ->maxLength(100),
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
                    Forms\Components\TextInput::make('ubicacion_fisica')
                        ->label('Ubicación física')
                        ->prefixIcon('heroicon-m-building-office')
                        ->maxLength(150)
                        ->columnSpan(2),
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
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\Select::make('id_usuario')
                        ->label('Registrado por (usuario)')
                        ->relationship('usuario', 'name')
                        ->default(auth()->id())
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-m-user')
                        ->columnSpanFull(),
                ])->columns(3),

            Forms\Components\Wizard\Step::make('Factura y proveedor')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('id_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor', 'nombre_empresa')
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-truck')
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
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
                            Forms\Components\Select::make('garantia_estado')
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
                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Observaciones generales')
                                ->rows(2),
                        ]),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subtipo')->wrap(false)->limit(30)->grow(false)
                    ->label('Subtipo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ubicacion_fisica')->wrap(false)->limit(30)->grow(false)
                    ->label('Ubicación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)->label('Estado')
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
                    ]),
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
                    ->label('Estado aprobación')
                    ->formatStateUsing(fn ($state) => $state ? 'Aprobado' : 'Pendiente')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->icon(fn ($state) => $state ? 'heroicon-m-shield-check' : 'heroicon-m-clock'),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Asignado' => 'Asignado',
                        'Reasignado' => 'Reasignado',
                        'En Mantenimiento' => 'En Mantenimiento',
                        'Dañado' => 'Dañado',
                        'Baja' => 'Baja',
                    ]),
                Tables\Filters\TernaryFilter::make('aprobado')
                    ->label('Estado de aprobación'),
                Tables\Filters\Filter::make('red')
                    ->form([
                        Forms\Components\TextInput::make('mac_address')->label('Dirección MAC')->placeholder('Ej. AA:BB:CC...'),
                        Forms\Components\TextInput::make('ip_address')->label('Dirección IP')->placeholder('Ej. 192.168...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['mac_address'], fn (Builder $q, $mac) => $q->where('mac_address', 'like', "%{$mac}%"))
                            ->when($data['ip_address'], fn (Builder $q, $ip) => $q->where('ip_address', 'like', "%{$ip}%"));
                    }),
                Tables\Filters\Filter::make('especificaciones')
                    ->form([
                        Forms\Components\TextInput::make('procesador')->label('Procesador')->placeholder('Ej. i5, Ryzen...'),
                        Forms\Components\TextInput::make('ram')->label('Memoria RAM')->placeholder('Ej. 8 GB, 16 GB...'),
                        Forms\Components\TextInput::make('disco')->label('Disco Duro')->placeholder('Ej. 512GB SSD...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['procesador'], fn (Builder $q, $val) => $q->where('procesador', 'like', "%{$val}%"))
                            ->when($data['ram'], fn (Builder $q, $val) => $q->where('ram', 'like', "%{$val}%"))
                            ->when($data['disco'], fn (Builder $q, $val) => $q->where('disco', 'like', "%{$val}%"));
                    }),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver()->tooltip('Ver detalle'),
                Tables\Actions\EditAction::make()->iconButton()->slideOver()->tooltip('Editar')->successNotificationTitle('Registro de inventario actualizado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Identificación del activo')
                    ->schema([
                        TextEntry::make('num_serie')->label('No. serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code'),
                        TextEntry::make('material.nombre')->label('Material')
                            ->icon('heroicon-m-cube'),
                        TextEntry::make('subtipo')->label('Subtipo')->placeholder('No definido'),
                        TextEntry::make('estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Disponible' => 'success',
                                'Asignado' => 'info',
                                'Reasignado' => 'reassign',
                                'En Mantenimiento' => 'warning',
                                'Dañado' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Disponible' => 'heroicon-m-check-circle',
                                'Asignado' => 'heroicon-m-user',
                                'Reasignado' => 'heroicon-m-arrows-right-left',
                                'En Mantenimiento' => 'heroicon-m-wrench',
                                'Dañado' => 'heroicon-m-exclamation-triangle',
                                default => 'heroicon-m-minus-circle',
                            }),
                    ])->columns(4),
                Section::make('Ubicación y asignación')
                    ->schema([
                        TextEntry::make('ubicacion_fisica')->label('Ubicación')
                            ->icon('heroicon-m-building-office'),
                        TextEntry::make('fecha_asignacion')
                            ->label('Fecha de asignación')
                            ->date('d/m/Y')
                            ->icon('heroicon-m-calendar')
                            ->placeholder('Sin fecha de asignación'),
                        TextEntry::make('fecha_cambio_area')
                            ->label('Fecha de cambio de área')
                            ->date('d/m/Y')
                            ->icon('heroicon-m-calendar-days')
                            ->placeholder('Sin cambio de área'),
                        TextEntry::make('tipo_propiedad')->label('Propiedad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Propio' => 'primary',
                                'Rentado' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('usuario.name')->label('Registrado por')
                            ->icon('heroicon-m-user'),
                    ])->columns(3),
                Section::make('Red y Conectividad')
                    ->schema([
                        TextEntry::make('ip_address')
                            ->label('Dirección IP')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-globe-alt')
                            ->placeholder('—'),
                        TextEntry::make('mac_address')
                            ->label('Dirección MAC')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-signal')
                            ->placeholder('—'),
                        TextEntry::make('procesador')
                            ->label('Procesador')
                            ->placeholder('—'),
                        TextEntry::make('ram')
                            ->label('Memoria RAM')
                            ->placeholder('—'),
                        TextEntry::make('disco')
                            ->label('Disco duro')
                            ->placeholder('—'),
                    ])->columns(3)->collapsible(),
                Section::make('Detalles adicionales')
                    ->schema([
                        TextEntry::make('proveedor.nombre_empresa')->label('Proveedor'),
                        TextEntry::make('num_factura')->label('Factura'),
                        TextEntry::make('garantia_estado')->label('Garantía'),
                        TextEntry::make('observaciones_generales')->label('Observaciones')->columnSpanFull(),
                    ])->columns(3),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
        // Sin filtro por usuario — SS ve todo el inventario
        // Los permisos de edición los controla InventarioPolicy
    }
}
