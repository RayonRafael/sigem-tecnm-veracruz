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
    protected static ?string $modelLabel = 'Mantenimiento';
    protected static ?string $pluralModelLabel = 'Mantenimientos';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?int $navigationSort = 3;

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
                        Forms\Components\Hidden::make('id_usuario_solicita')
                            ->default(fn () => auth()->id()),
                    ])->columns(3),

                Forms\Components\Section::make('Tipo de servicio')
                    ->schema([
                        Forms\Components\Select::make('tipo_servicio')
                            ->label('Tipo de Servicio')
                            ->options([
                                'Servicio Social' => 'Servicio Social',
                                'Prácticas Profesionales' => 'Prácticas Profesionales',
                                'Personal Técnico' => 'Personal Técnico',
                            ])
                            ->required(),
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
                                'Pendiente Revision Admin' => 'En revisión',
                                'Completado' => 'Completado',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->default('Pendiente Revision Admin')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Fechas')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_solicitud')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('fecha_inicio')
                            ->label('Fecha Inicio'),
                        Forms\Components\DatePicker::make('fecha_fin')
                            ->label('Fecha Fin'),
                    ])->columns(3),

                Forms\Components\Section::make('Detalles')
                    ->schema([
                        Forms\Components\Textarea::make('descripcion_falla')
                            ->label('Descripción de la Falla')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('descripcion_trabajo')
                            ->label('Trabajo Realizado')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(1),
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
                Tables\Columns\TextColumn::make('tipo_servicio')
                    ->label('Tipo Servicio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_tecnico')
                    ->label('Técnico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->formatStateUsing(fn ($state) => $state === 'Pendiente Revision Admin' ? 'En revisión' : $state)
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
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Solicitado' => 'Solicitado',
                        'En proceso' => 'En proceso',
                        'Pendiente Revision Admin' => 'En revisión',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_mantenimiento')
                    ->label('Tipo de Mantenimiento')
                    ->options([
                        'Preventivo' => 'Preventivo',
                        'Correctivo' => 'Correctivo',
                        'Mejora' => 'Mejora',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('autorizar')
                    ->label('Autorizar')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Mantenimiento $record): bool => $record->estado === 'Pendiente Revision Admin' || $record->estado === 'Solicitado')
                    ->action(fn (Mantenimiento $record) => $record->update(['estado' => 'En proceso']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('completar')
                    ->label('Completar')
                    ->icon('heroicon-m-check-badge')
                    ->color('info')
                    ->visible(fn (Mantenimiento $record): bool => $record->estado === 'En proceso')
                    ->action(fn (Mantenimiento $record) => $record->update(['estado' => 'Completado', 'fecha_fin' => now()]))
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
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