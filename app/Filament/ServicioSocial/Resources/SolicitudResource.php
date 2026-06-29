<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;
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
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la solicitud')
                    ->schema([
                        Forms\Components\DatePicker::make('fecha_solicitud')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('tipo_movimiento')
                            ->options([
                                'Asignacion Temporal' => 'Asignación Temporal',
                                'Asignacion Permanente' => 'Asignación Permanente',
                            ])
                            ->default('Asignacion Temporal')
                            ->required(),
                        Forms\Components\Select::make('estado')
                            ->options([
                                'Pendiente' => 'Pendiente',
                            ])
                            ->default('Pendiente')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Participantes')
                    ->schema([
                        Forms\Components\Select::make('id_usuario')
                            ->label('Solicitante (Usuario)')
                            ->relationship('usuario', 'name')
                            ->default(auth()->id())
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('id_receptor')
                            ->label('Receptor (Área/Persona)')
                            ->relationship('receptor', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Forms\Components\Section::make('Observaciones')
                    ->schema([
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
                Tables\Columns\TextColumn::make('id_solicitud')
                    ->label('Folio')
                    ->formatStateUsing(fn ($state) => 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->badge()
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Pendiente' => 'Pendiente',
                        'Autorizado' => 'Autorizado',
                        'Rechazado' => 'Rechazado',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
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
            'index' => Pages\ListSolicituds::route('/'),
            'create' => Pages\CreateSolicitud::route('/create'),
            'edit' => Pages\EditSolicitud::route('/{record}/edit'),
        ];
    }
}
