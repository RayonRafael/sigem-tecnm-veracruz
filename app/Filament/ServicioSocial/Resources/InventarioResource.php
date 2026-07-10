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
                Forms\Components\Section::make('Identificación del activo')
                    ->schema([
                        Forms\Components\TextInput::make('num_serie')
                            ->label('Número de Serie')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\Select::make('id_producto')
                            ->label('Material / Equipo')
                            ->relationship('material', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('estado')
                            ->options([
                                'Disponible' => 'Disponible',
                                'Asignado' => 'Asignado',
                                'En Mantenimiento' => 'En Mantenimiento',
                                'Dañado' => 'Dañado',
                            ])
                            ->default('Disponible')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Ubicación y asignación')
                    ->schema([
                        Forms\Components\TextInput::make('ubicacion_fisica')
                            ->label('Ubicación Física')
                            ->maxLength(150),
                        Forms\Components\Select::make('tipo_propiedad')
                            ->options([
                                'Propio' => 'Propio',
                                'Rentado' => 'Rentado',
                            ])
                            ->default('Propio')
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('id_usuario')
                            ->label('Registrado por (Usuario)')
                            ->relationship('usuario', 'name')
                            ->default(auth()->id())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Hidden::make('aprobado')
                            ->default(false),
                        Forms\Components\Hidden::make('estado_registro')
                            ->default('Pendiente'),
                    ])->columns(3),

                Forms\Components\Section::make('Proveedor y factura')
                    ->schema([
                        Forms\Components\Select::make('id_proveedor')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'nombre_empresa')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        Forms\Components\TextInput::make('num_factura')
                            ->label('Número de Factura')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        Forms\Components\DatePicker::make('fecha_factura')
                            ->label('Fecha de Factura')
                            ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        Forms\Components\DatePicker::make('fecha_registro')
                            ->label('Fecha de Registro')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Información de renta')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_inicio_renta')->label('Inicio de Renta'),
                        Forms\Components\DatePicker::make('fecha_fin_renta')->label('Fin de Renta'),
                        Forms\Components\Textarea::make('observaciones_renta')->label('Observaciones de Renta')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),

                Forms\Components\Section::make('Garantía')
                    ->schema([
                        Forms\Components\DatePicker::make('garantia_fecha_fin')->label('Fin de Garantía'),
                        Forms\Components\Select::make('garantia_estado')
                            ->label('Estado de Garantía')
                            ->options([
                                'vigente' => 'Vigente',
                                'vencida' => 'Vencida',
                                'sin_garantia' => 'Sin Garantía',
                            ])
                            ->default('sin_garantia'),
                    ])->columns(2),

                Forms\Components\Section::make('Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones_generales')
                            ->label('Observaciones Generales')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),
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
                    ]),
                Tables\Columns\TextColumn::make('aprobado')
                    ->label('Estado Aprobación')
                    ->formatStateUsing(fn ($state) => $state ? 'Aprobado' : 'Pendiente de aprobación')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
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
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
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
