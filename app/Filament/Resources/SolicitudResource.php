<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudResource\Pages;
use App\Models\Solicitud;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SolicitudResource extends Resource
{
    protected static ?string $model = Solicitud::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Solicitudes';
    protected static ?string $modelLabel = 'Solicitud';
    protected static ?string $pluralModelLabel = 'Solicitudes';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Solicitud')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_solicitud')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('id_usuario')
                            ->label('Solicitante (Usuario)')
                            ->relationship('usuario', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('id_receptor')
                            ->label('Receptor (Área/Persona)')
                            ->relationship('receptor', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('tipo_movimiento')
                            ->options([
                                'Asignacion Temporal' => 'Asignación Temporal',
                                'Asignacion Permanente' => 'Asignación Permanente',
                                'Renta Externa' => 'Renta Externa',
                            ])
                            ->default('Asignacion Temporal')
                            ->required(),
                        Forms\Components\Select::make('estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                                'Autorizado' => 'Autorizado',
                                'Rechazado' => 'Rechazado',
                                'Completado' => 'Completado',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->default('Pendiente')
                            ->required(),
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_solicitud')
                    ->label('Folio')
                    ->formatStateUsing(fn ($state) => 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Solicitante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receptor.nombre')
                    ->label('Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_solicitud')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->colors([
                        'warning' => 'Pendiente',
                        'primary' => 'Autorizado',
                        'success' => 'Completado',
                        'danger' => 'Rechazado',
                        'gray' => 'Cancelado',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Autorizado' => 'Autorizado',
                        'Rechazado' => 'Rechazado',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'Asignacion Temporal' => 'Asignación Temporal',
                        'Asignacion Permanente' => 'Asignación Permanente',
                        'Renta Externa' => 'Renta Externa',
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
            'index' => Pages\ListSolicituds::route('/'),
            'create' => Pages\CreateSolicitud::route('/create'),
            'edit' => Pages\EditSolicitud::route('/{record}/edit'),
        ];
    }
}