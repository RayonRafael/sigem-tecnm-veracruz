<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
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
                        ->placeholder('Ej. SN-12345')
                        ->prefixIcon('heroicon-m-qr-code')
                        ->required()
                        ->maxLength(100)
                        ->columnSpan(1),
                    Forms\Components\Select::make('id_producto')
                        ->label('Material / equipo')
                        ->placeholder('Selecciona...')
                        ->relationship('material', 'nombre')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->prefixIcon('heroicon-m-cube')
                        ->columnSpanFull(),
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
                    Forms\Components\TextInput::make('ubicacion_fisica')
                        ->label('Ubicación física')
                        ->placeholder('Ej. Edif. A > Centro de Cómputo')
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
                            Forms\Components\Select::make('garantia_estado')
                                ->label('Estado de garantía')
                                ->placeholder('Selecciona...')
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
                Tables\Columns\TextColumn::make('num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie')
                    ->fontFamily('mono')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('material.tipo.nombre')
                    ->label('CATEGORÍA')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('material.tipo', fn (Builder $q) => $q->where('nombre', 'like', trim($search) . '%'));
                    }, isIndividual: true)
                    ->sortable()
                    ->width('130px'),
                Tables\Columns\TextColumn::make('subtipo')->wrap(false)->limit(30)->grow(false)
                    ->label('Subtipo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ubicacion_fisica')->wrap(false)->limit(30)->grow(false)
                    ->label('Ubicación')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('ubicacion_fisica', 'like', '%' . trim($search) . '%');
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('material.marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('tipo_propiedad')
                    ->label('PROPIEDAD')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                    ->color(fn (?string $state): string => $state === 'Propio' ? 'success' : 'info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tipo_propiedad', $search);
                    }, isIndividual: true)
                    ->sortable(),
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
            ->searchPlaceholder('Buscar por equipo, código, IP, MAC, marca...')
            ->defaultPaginationPageOption(10)
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
                return $query;
            })
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
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
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->tooltip('Editar')
                    ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? 'equipo'))
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->num_serie ?? '—')
                    ))
                    ->successNotificationTitle('Registro de inventario actualizado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\InventarioResource::infolist($infolist);
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
}
