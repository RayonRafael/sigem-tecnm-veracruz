<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('num_serie')
                            ->label('Número de Serie')
                            ->maxLength(100),
                        Forms\Components\Select::make('id_producto')
                            ->label('Material / Producto')
                            ->relationship('material', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('id_usuario')
                            ->label('Registrado por (Usuario)')
                            ->relationship('usuario', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('id_proveedor')
                            ->label('Proveedor (Solo si es rentado)')
                            ->relationship('proveedor', 'nombre_empresa')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Estado y Propiedad')
                    ->schema([
                        Forms\Components\Select::make('estado')
                            ->options([
                                'Disponible' => 'Disponible',
                                'Asignado' => 'Asignado',
                                'En Mantenimiento' => 'En Mantenimiento',
                                'Dañado' => 'Dañado',
                                'Baja' => 'Baja',
                                'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                            ])
                            ->default('Disponible')
                            ->required(),
                        Forms\Components\Select::make('estado_registro')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Aprobado' => 'Aprobado',
                                'Rechazado' => 'Rechazado',
                            ])
                            ->default('Pendiente')
                            ->required(),
                        Forms\Components\Select::make('tipo_propiedad')
                            ->options([
                                'Propio' => 'Propio',
                                'Rentado' => 'Rentado',
                            ])
                            ->default('Propio')
                            ->required(),
                        Forms\Components\TextInput::make('ubicacion_fisica')
                            ->label('Ubicación Física')
                            ->maxLength(150),
                    ])->columns(2),

                Forms\Components\Section::make('Fechas y Garantía')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_registro')->default(now())->required(),
                        Forms\Components\DatePicker::make('fecha_inicio_renta'),
                        Forms\Components\DatePicker::make('fecha_fin_renta'),
                        Forms\Components\DatePicker::make('garantia_fecha_fin')->label('Fin de Garantía'),
                        Forms\Components\Select::make('garantia_estado')
                            ->options([
                                'vigente' => 'Vigente',
                                'vencida' => 'Vencida',
                                'sin_garantia' => 'Sin Garantía',
                            ])
                            ->default('sin_garantia'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('num_serie')->label('No. Serie')->searchable(),
                Tables\Columns\TextColumn::make('material.nombre')->label('Material')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('estado')
                    ->colors([
                        'success' => 'Disponible',
                        'primary' => 'Asignado',
                        'warning' => 'En Mantenimiento',
                        'danger' => 'Dañado',
                        'gray' => 'Baja',
                        'info' => 'Devuelto a Proveedor',
                    ]),
                Tables\Columns\BadgeColumn::make('estado_registro')
                    ->colors([
                        'warning' => 'Pendiente',
                        'success' => 'Aprobado',
                        'danger' => 'Rechazado',
                    ]),
                Tables\Columns\TextColumn::make('tipo_propiedad')->searchable(),
                Tables\Columns\TextColumn::make('ubicacion_fisica')->label('Ubicación')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Disponible' => 'Disponible',
                        'Asignado' => 'Asignado',
                        'En Mantenimiento' => 'En Mantenimiento',
                        'Dañado' => 'Dañado',
                        'Baja' => 'Baja',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_propiedad')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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