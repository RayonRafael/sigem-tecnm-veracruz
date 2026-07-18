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
    protected static bool $shouldRegisterNavigation = false;

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
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Equipo y falla')
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            Forms\Components\Select::make('id_inventario')
                                ->label('Equipo a Reparar')
                                ->relationship('inventario', 'num_serie')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->prefixIcon('heroicon-m-qr-code')
                                ->columnSpanFull(),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_tecnico')
                                        ->label('Técnico / Alumno')
                                        ->required()
                                        ->prefixIcon('heroicon-m-user')
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('num_control_tecnico')
                                        ->label('Número de Control Técnico')
                                        ->prefixIcon('heroicon-m-identification')
                                        ->maxLength(100),
                                ]),
                            Forms\Components\Hidden::make('id_usuario_solicita')
                                ->default(fn () => auth()->id()),
                            Forms\Components\DatePicker::make('fecha_solicitud')
                                ->default(now())
                                ->required()
                                ->prefixIcon('heroicon-m-calendar'),
                            Forms\Components\Textarea::make('descripcion_falla')
                                ->label('Descripción de la Falla')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    
                    Forms\Components\Wizard\Step::make('Tipo de mantenimiento')
                        ->icon('heroicon-m-cog')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\ToggleButtons::make('tipo_servicio')
                                        ->label('Tipo de Servicio')
                                        ->options([
                                            'Servicio Social' => 'Servicio Social',
                                            'Prácticas Profesionales' => 'Prácticas Profesionales',
                                            'Personal Técnico' => 'Personal Técnico',
                                        ])
                                        ->colors([
                                            'Servicio Social' => 'primary',
                                            'Prácticas Profesionales' => 'info',
                                            'Personal Técnico' => 'success',
                                        ])
                                        ->inline()
                                        ->required(),
                                    Forms\Components\ToggleButtons::make('tipo_mantenimiento')
                                        ->options([
                                            'Preventivo' => 'Preventivo',
                                            'Correctivo' => 'Correctivo',
                                            'Mejora' => 'Mejora',
                                        ])
                                        ->colors([
                                            'Preventivo' => 'success',
                                            'Correctivo' => 'danger',
                                            'Mejora' => 'info',
                                        ])
                                        ->inline()
                                        ->required(),
                                ]),
                            Forms\Components\ToggleButtons::make('estado')
                                ->options([
                                    'Solicitado' => 'Solicitado',
                                    'En proceso' => 'En proceso',
                                    'Pendiente Revision Admin' => 'En revisión',
                                ])
                                ->colors([
                                    'Solicitado' => 'warning',
                                    'En proceso' => 'primary',
                                    'Pendiente Revision Admin' => 'warning',
                                ])
                                ->inline()
                                ->default('Pendiente Revision Admin')
                                ->required()
                                ->columnSpanFull(),
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
                    ->icons([
                        'heroicon-m-shield-check' => 'Preventivo',
                        'heroicon-m-exclamation-triangle' => 'Correctivo',
                        'heroicon-m-arrow-trending-up' => 'Mejora',
                    ])
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
                    ])
                    ->icons([
                        'heroicon-m-clock' => 'Solicitado',
                        'heroicon-m-wrench-screwdriver' => 'En proceso',
                        'heroicon-m-clock' => 'Pendiente Revision Admin',
                        'heroicon-m-check-badge' => 'Completado',
                        'heroicon-m-x-circle' => 'Cancelado',
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
                        'Pendiente Revision Admin' => 'En revisión',
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
                \Filament\Infolists\Components\Section::make('Equipo y Técnico')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('inventario.num_serie')->label('No. Serie')
                            ->fontFamily('mono')
                            ->icon('heroicon-m-qr-code'),
                        \Filament\Infolists\Components\TextEntry::make('inventario.material.nombre')->label('Material')->icon('heroicon-m-cube'),
                        \Filament\Infolists\Components\TextEntry::make('nombre_tecnico')->label('Técnico')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('num_control_tecnico')->label('No. Control')->icon('heroicon-m-identification'),
                    ])->columns(4),
                \Filament\Infolists\Components\Section::make('Detalles del Servicio')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('tipo_servicio')->label('Servicio')->badge(),
                        \Filament\Infolists\Components\TextEntry::make('tipo_mantenimiento')->label('Tipo')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Preventivo' => 'success',
                                'Correctivo' => 'danger',
                                'Mejora' => 'info',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Preventivo' => 'heroicon-m-shield-check',
                                'Correctivo' => 'heroicon-m-exclamation-triangle',
                                'Mejora' => 'heroicon-m-arrow-trending-up',
                                default => 'heroicon-m-minus',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('estado')->label('Estado')
                            ->formatStateUsing(fn ($state) => $state === 'Pendiente Revision Admin' ? 'En revisión' : $state)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Solicitado' => 'warning',
                                'En proceso' => 'primary',
                                'Pendiente Revision Admin' => 'info',
                                'Completado' => 'success',
                                'Cancelado' => 'danger',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                'Solicitado' => 'heroicon-m-clock',
                                'En proceso' => 'heroicon-m-wrench-screwdriver',
                                'Pendiente Revision Admin' => 'heroicon-m-clock',
                                'Completado' => 'heroicon-m-check-badge',
                                'Cancelado' => 'heroicon-m-x-circle',
                                default => 'heroicon-m-minus',
                            }),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Fechas y Reportes')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('fecha_solicitud')->label('Solicitud')->date('d/m/Y')->icon('heroicon-m-calendar'),
                        \Filament\Infolists\Components\TextEntry::make('descripcion_falla')->label('Falla')->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('observaciones')->label('Observaciones')->columnSpanFull(),
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
            'index' => Pages\ListMantenimientos::route('/'),
            'create' => Pages\CreateMantenimiento::route('/create'),
            'edit' => Pages\EditMantenimiento::route('/{record}/edit'),
        ];
    }
}
