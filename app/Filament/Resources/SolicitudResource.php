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
    protected static bool $shouldRegisterNavigation = false;

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
                                            'Renta Externa' => 'Renta Externa',
                                        ])
                                        ->colors([
                                            'Asignacion Temporal' => 'warning',
                                            'Asignacion Permanente' => 'success',
                                            'Renta Externa' => 'info',
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
                                    'Autorizado' => 'Autorizado',
                                    'Rechazado' => 'Rechazado',
                                    'Completado' => 'Completado',
                                    'Cancelado' => 'Cancelado',
                                ])
                                ->colors([
                                    'Pendiente' => 'warning',
                                    'Autorizado' => 'primary',
                                    'Rechazado' => 'danger',
                                    'Completado' => 'success',
                                    'Cancelado' => 'gray',
                                ])
                                ->inline()
                                ->default('Pendiente')
                                ->required()
                                ->columnSpanFull(),
                        ]),
                    
                    Forms\Components\Wizard\Step::make('Participantes y detalles')
                        ->icon('heroicon-m-users')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('id_usuario')
                                        ->label('Solicitante (Usuario)')
                                        ->relationship('usuario', 'name')
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
                                    Forms\Components\Placeholder::make('departamento')
                                        ->label('Departamento')
                                        ->content(function (callable $get) {
                                            $receptorId = $get('id_receptor');
                                            if ($receptorId) {
                                                $receptor = \App\Models\Receptor::with('area.departamento')->find($receptorId);
                                                return $receptor?->area?->departamento?->nombre ?? 'Sin departamento';
                                            }
                                            return 'Seleccione un receptor';
                                        }),
                                ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('autorizado_por')
                                        ->label('Autorizado por')
                                        ->relationship('autorizadoPor', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-shield-check'),
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
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Solicitante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receptor.nombre')
                    ->label('Receptor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('receptor.area.departamento.nombre')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('fecha_devolucion_estimada')
                    ->label('Devolución Estimada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('autorizadoPor.name')
                    ->label('Autorizado por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'Asignacion Temporal' => 'Asignación Temporal',
                        'Asignacion Permanente' => 'Asignación Permanente',
                        'Renta Externa' => 'Renta Externa',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('autorizar')
                    ->label('Autorizar')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
                    ->action(fn (Solicitud $record) => $record->update([
                        'estado' => 'Autorizado',
                        'fecha_autorizacion' => now(),
                        'autorizado_por' => auth()->id()
                    ]))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo de rechazo (opcional)')
                            ->rows(3),
                    ])
                    ->action(function (Solicitud $record, array $data) {
                        $observacion = $record->observaciones;
                        if ($data['motivo_rechazo']) {
                            $observacion = $observacion ? $observacion . "\n\nMotivo Rechazo: " . $data['motivo_rechazo'] : "Motivo Rechazo: " . $data['motivo_rechazo'];
                        }
                        $record->update([
                            'estado' => 'Rechazado',
                            'observaciones' => $observacion
                        ]);
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
                        \Filament\Infolists\Components\TextEntry::make('autorizadoPor.name')->label('Autorizado por')->icon('heroicon-m-shield-check'),
                    ])->columns(4),
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
        return [
            SolicitudResource\RelationManagers\DetallesRelationManager::class,
        ];
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