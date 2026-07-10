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
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos de la solicitud')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_solicitud')
                                        ->default(now())
                                        ->required()
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->columnSpan(1),
                                    Forms\Components\ToggleButtons::make('tipo_movimiento')
                                        ->options([
                                            'Asignacion Temporal' => 'Asignación Temporal',
                                            'Asignacion Permanente' => 'Asignación Permanente',
                                        ])
                                        ->colors([
                                            'Asignacion Temporal' => 'warning',
                                            'Asignacion Permanente' => 'success',
                                        ])
                                        ->inline()
                                        ->default('Asignacion Temporal')
                                        ->required()
                                        ->live()
                                        ->columnSpan(2),
                                ]),
                            Forms\Components\ToggleButtons::make('estado')
                                ->options([
                                    'Pendiente' => 'Pendiente',
                                ])
                                ->colors([
                                    'Pendiente' => 'warning',
                                ])
                                ->inline()
                                ->default('Pendiente')
                                ->required()
                                ->columnSpanFull(),
                        ]),
                    
                    Forms\Components\Wizard\Step::make('Participantes y detalles')
                        ->icon('heroicon-m-users')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('id_usuario')
                                        ->label('Solicitante (Usuario)')
                                        ->relationship('usuario', 'name')
                                        ->default(auth()->id())
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user'),
                                    Forms\Components\Select::make('id_receptor')
                                        ->label('Receptor (Área/Persona)')
                                        ->relationship('receptor', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user-group')
                                        ->live(),
                                ]),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_devolucion_estimada')
                                        ->label('Devolución Estimada')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                    Forms\Components\DatePicker::make('fecha_devolucion_real')
                                        ->label('Devolución Real')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                ]),
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
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
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'Pendiente',
                        'heroicon-m-check-circle' => 'Autorizado',
                        'heroicon-m-check-badge' => 'Completado',
                        'heroicon-m-x-circle' => 'Rechazado',
                        'heroicon-m-no-symbol' => 'Cancelado',
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
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Datos de la Solicitud')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('id_solicitud')->label('Folio')
                            ->formatStateUsing(fn ($state) => 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT))
                            ->fontFamily('mono')
                            ->icon('heroicon-m-hashtag'),
                        \Filament\Infolists\Components\TextEntry::make('fecha_solicitud')->label('Fecha')->date('d/m/Y')->icon('heroicon-m-calendar'),
                        \Filament\Infolists\Components\TextEntry::make('tipo_movimiento')->label('Tipo')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pendiente' => 'warning',
                                'Autorizado' => 'primary',
                                'Completado' => 'success',
                                'Rechazado' => 'danger',
                                'Cancelado' => 'gray',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Pendiente' => 'heroicon-m-clock',
                                'Autorizado' => 'heroicon-m-check-circle',
                                'Completado' => 'heroicon-m-check-badge',
                                'Rechazado' => 'heroicon-m-x-circle',
                                'Cancelado' => 'heroicon-m-no-symbol',
                                default => 'heroicon-m-minus',
                            }),
                    ])->columns(4),
                \Filament\Infolists\Components\Section::make('Participantes')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('usuario.name')->label('Solicitante')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('receptor.nombre')->label('Receptor')->icon('heroicon-m-user-group'),
                        \Filament\Infolists\Components\TextEntry::make('receptor.area.departamento.nombre')->label('Departamento')->icon('heroicon-m-building-office'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Devoluciones y Observaciones')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('fecha_devolucion_estimada')->label('Devolución Estimada')->date('d/m/Y')->icon('heroicon-m-calendar-days'),
                        \Filament\Infolists\Components\TextEntry::make('fecha_devolucion_real')->label('Devolución Real')->date('d/m/Y')->icon('heroicon-m-calendar-days'),
                        \Filament\Infolists\Components\TextEntry::make('observaciones')->label('Observaciones')->columnSpanFull(),
                    ])->columns(2),
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
