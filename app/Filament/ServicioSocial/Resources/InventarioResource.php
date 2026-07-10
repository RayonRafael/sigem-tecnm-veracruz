<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Inventario (Servicio Social)';
    protected static ?string $modelLabel = 'Activo';
    protected static ?string $pluralModelLabel = 'Activos';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Identificación')
                        ->icon('heroicon-m-identification')
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('Número de Serie')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required()
                                ->maxLength(100)
                                ->columnSpan(1),
                            Forms\Components\Select::make('id_producto')
                                ->label('Material / Equipo')
                                ->relationship('material', 'nombre')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-cube')
                                ->columnSpan(2),
                            Forms\Components\ToggleButtons::make('estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                ])
                                ->colors([
                                    'Disponible' => 'success',
                                    'Asignado' => 'warning',
                                    'En Mantenimiento' => 'gray',
                                    'Dañado' => 'danger',
                                ])
                                ->inline()
                                ->default('Disponible')
                                ->required()
                                ->columnSpanFull(),
                        ])->columns(3),

                    Forms\Components\Wizard\Step::make('Ubicación y asignación')
                        ->icon('heroicon-m-map-pin')
                        ->schema([
                            Forms\Components\TextInput::make('ubicacion_fisica')
                                ->label('Ubicación Física')
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
                                ->label('Registrado por (Usuario)')
                                ->relationship('usuario', 'name')
                                ->default(auth()->id())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-user')
                                ->columnSpanFull(),
                            Forms\Components\Hidden::make('aprobado')
                                ->default(false),
                            Forms\Components\Hidden::make('estado_registro')
                                ->default('Pendiente'),
                        ])->columns(3),

                    Forms\Components\Wizard\Step::make('Garantía y Facturación')
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
                                        ->label('Número de Factura')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->maxLength(100)
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                                    Forms\Components\DatePicker::make('fecha_factura')
                                        ->label('Fecha de Factura')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_inicio_renta')
                                        ->label('Inicio de Renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                    Forms\Components\DatePicker::make('fecha_fin_renta')
                                        ->label('Fin de Renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),
                                    Forms\Components\Textarea::make('observaciones_renta')
                                        ->label('Observaciones de Renta')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_registro')
                                        ->label('Fecha de Registro')
                                        ->default(now())
                                        ->required()
                                        ->prefixIcon('heroicon-m-clock'),
                                    Forms\Components\DatePicker::make('garantia_fecha_fin')
                                        ->label('Fin de Garantía')
                                        ->prefixIcon('heroicon-m-shield-check'),
                                    Forms\Components\Select::make('garantia_estado')
                                        ->label('Estado de Garantía')
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
                                        ->label('Observaciones Generales')
                                        ->rows(2),
                                ])
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')
                    ->label('No. Serie')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('material.nombre')
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ubicacion_fisica')
                    ->label('Ubicación')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'success' => 'Disponible',
                        'warning' => 'Asignado',
                        'gray' => 'En Mantenimiento',
                        'danger' => 'Dañado',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Disponible',
                        'heroicon-m-user' => 'Asignado',
                        'heroicon-m-wrench' => 'En Mantenimiento',
                        'heroicon-m-exclamation-triangle' => 'Dañado',
                    ]),
                Tables\Columns\TextColumn::make('aprobado')
                    ->label('Estado Aprobación')
                    ->formatStateUsing(fn ($state) => $state ? 'Aprobado' : 'Pendiente')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->icon(fn ($state) => $state ? 'heroicon-m-shield-check' : 'heroicon-m-clock'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Asignado' => 'Asignado',
                        'En Mantenimiento' => 'En Mantenimiento',
                        'Dañado' => 'Dañado',
                    ]),
                Tables\Filters\TernaryFilter::make('aprobado')
                    ->label('Estado de Aprobación'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Identificación del Activo')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('num_serie')->label('No. Serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code'),
                        \Filament\Infolists\Components\TextEntry::make('material.nombre')->label('Material')
                            ->icon('heroicon-m-cube'),
                        \Filament\Infolists\Components\TextEntry::make('estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Disponible' => 'success',
                                'Asignado' => 'warning',
                                'En Mantenimiento' => 'gray',
                                'Dañado' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Disponible' => 'heroicon-m-check-circle',
                                'Asignado' => 'heroicon-m-user',
                                'En Mantenimiento' => 'heroicon-m-wrench',
                                'Dañado' => 'heroicon-m-exclamation-triangle',
                                default => 'heroicon-m-minus',
                            }),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Ubicación y Asignación')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('ubicacion_fisica')->label('Ubicación')
                            ->icon('heroicon-m-building-office'),
                        \Filament\Infolists\Components\TextEntry::make('tipo_propiedad')->label('Propiedad')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Propio' => 'primary',
                                'Rentado' => 'gray',
                                default => 'gray',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('usuario.name')->label('Registrado por')
                            ->icon('heroicon-m-user'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Detalles Adicionales')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('proveedor.nombre_empresa')->label('Proveedor'),
                        \Filament\Infolists\Components\TextEntry::make('num_factura')->label('Factura'),
                        \Filament\Infolists\Components\TextEntry::make('garantia_estado')->label('Garantía'),
                        \Filament\Infolists\Components\TextEntry::make('observaciones_generales')->label('Observaciones')->columnSpanFull(),
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
}
