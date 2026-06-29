<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;
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
    protected static ?string $modelLabel = 'Mantenimiento';
    protected static ?string $pluralModelLabel = 'Mantenimientos';
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipo y técnico')
                    ->schema([
                        Forms\Components\Select::make('id_inventario')
                            ->label('Equipo a Reparar')
                            ->relationship('inventario', 'num_serie')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('nombre_tecnico')
                            ->label('Técnico / Alumno')
                            ->required()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('num_control_tecnico')
                            ->label('Número de Control Técnico')
                            ->maxLength(100),
                    ])->columns(3),

                Forms\Components\Section::make('Servicio y Detalles')
                    ->schema([
                        Forms\Components\TextInput::make('tipo_servicio')
                            ->label('Tipo de Servicio')
                            ->maxLength(100),
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
                            ])
                            ->default('Solicitado')
                            ->required(),
                        Forms\Components\DatePicker::make('fecha_solicitud')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('descripcion_falla')
                            ->label('Descripción de la Falla')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('descripcion_trabajo')
                            ->label('Trabajo Realizado')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inventario.num_serie')
                    ->label('No. Serie (Activo)')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('inventario.material.nombre')
                    ->label('Material')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_mantenimiento')
                    ->label('Tipo Mantenimiento')
                    ->badge()
                    ->colors([
                        'success' => 'Preventivo',
                        'danger' => 'Correctivo',
                        'primary' => 'Mejora',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_tecnico')
                    ->label('Técnico')
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Solicitado' => 'Solicitado',
                        'En proceso' => 'En proceso',
                        'Pendiente Revision Admin' => 'Pendiente Revisión',
                    ]),
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
            'index' => Pages\ListMantenimientos::route('/'),
            'create' => Pages\CreateMantenimiento::route('/create'),
            'edit' => Pages\EditMantenimiento::route('/{record}/edit'),
        ];
    }
}
