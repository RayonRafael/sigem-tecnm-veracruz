<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MantenimientoResource\Pages;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MantenimientoResource extends Resource
{
    protected static ?string $model = Mantenimiento::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Mantenimiento';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Mantenimiento')
                    ->schema([
                        Forms\Components\Select::make('id_inventario')
                            ->label('Equipo a Reparar')
                            ->relationship('inventario', 'num_serie') // Muestra el número de serie
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('nombre_tecnico')
                            ->label('Técnico / Alumno')
                            ->required()
                            ->maxLength(150),
                        Forms\Components\Select::make('tipo_mantenimiento')
                            ->options([
                                'Preventivo' => 'Preventivo',
                                'Correctivo' => 'Correctivo',
                                'Mejora' => 'Mejora',
                            ])
                            ->required(),
                        Forms\Components\Select::make('estado')
                            ->options([
                                'Solicitado' => 'Solicitado',
                                'En proceso' => 'En proceso',
                                'Pendiente Revision Admin' => 'Pendiente Revisión',
                                'Completado' => 'Completado',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->default('Solicitado')
                            ->required(),
                        Forms\Components\DatePicker::make('fecha_solicitud')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('descripcion_falla')
                            ->label('Descripción de la Falla')
                            ->rows(3),
                        Forms\Components\Textarea::make('descripcion_trabajo')
                            ->label('Trabajo Realizado')
                            ->rows(3),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_mantenimiento')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventario.num_serie')
                    ->label('No. Serie')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_tecnico')
                    ->label('Técnico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_mantenimiento')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'warning' => 'Solicitado',
                        'primary' => 'En proceso',
                        'info' => 'Pendiente Revision Admin',
                        'success' => 'Completado',
                        'danger' => 'Cancelado',
                    ]),
                Tables\Columns\TextColumn::make('fecha_solicitud')
                    ->label('Fecha Sol.')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Solicitado' => 'Solicitado',
                        'En proceso' => 'En proceso',
                        'Pendiente Revision Admin' => 'Pendiente Revisión',
                        'Completado' => 'Completado',
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
            'index' => Pages\ListMantenimientos::route('/'),
            'create' => Pages\CreateMantenimiento::route('/create'),
            'edit' => Pages\EditMantenimiento::route('/{record}/edit'),
        ];
    }
}