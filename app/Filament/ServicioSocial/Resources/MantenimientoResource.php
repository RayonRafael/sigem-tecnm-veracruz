<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Enums\RoleEnum;
use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoResource extends Resource
{
    protected static ?string $model = Mantenimiento::class;

    protected static ?string $recordTitleAttribute = 'descripcion_falla';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Mantenimiento';

    protected static ?string $modelLabel = 'mantenimiento';

    protected static ?string $pluralModelLabel = 'mantenimientos';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 2;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Equipo y técnico')
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            Forms\Components\Select::make('id_inventario')
                                ->label('Equipo a reparar')
                                ->placeholder('Selecciona...')
                                ->relationship(
                                    name: 'inventario',
                                    titleAttribute: 'num_serie',
                                    modifyQueryUsing: fn ($query) => $query->with('material')
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->material?->nombre} - N/S: {$record->num_serie}")
                                ->required()
                                ->searchable(['num_serie', 'material.nombre'])
                                ->preload()
                                ->prefixIcon('heroicon-m-qr-code')
                                ->columnSpanFull(),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_tecnico')
                                        ->label('Técnico / alumno')
                                        ->placeholder('Ej. Juan Pérez López')
                                        ->required()
                                        ->default(fn () => auth()->user() ? trim(auth()->user()->name.' '.auth()->user()->apellido_paterno.' '.auth()->user()->apellido_materno) : null)
                                        ->prefixIcon('heroicon-m-user')
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('num_control_tecnico')
                                        ->label('Número de control técnico')
                                        ->placeholder('Ej. 21020123')
                                        ->default(fn () => auth()->user()?->num_control)
                                        ->prefixIcon('heroicon-m-identification')
                                        ->maxLength(100),
                                ]),
                            Forms\Components\Hidden::make('id_usuario_solicita')
                                ->default(fn () => auth()->id()),
                        ]),

                    Forms\Components\Wizard\Step::make('Servicio y detalles')
                        ->icon('heroicon-m-cog')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\ToggleButtons::make('tipo_servicio')
                                        ->label('Tipo de servicio')
                                        ->options([
                                            RoleEnum::SERVICIO_SOCIAL->value => RoleEnum::SERVICIO_SOCIAL->value,
                                            'Prácticas Profesionales' => 'Prácticas Profesionales',
                                            'Personal Técnico' => 'Personal Técnico',
                                        ])
                                        ->colors([
                                            RoleEnum::SERVICIO_SOCIAL->value => 'primary',
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
                            Forms\Components\DatePicker::make('fecha_solicitud')
                                ->default(now())
                                ->required()
                                ->prefixIcon('heroicon-m-calendar'),
                            Forms\Components\Textarea::make('descripcion_falla')
                                ->label('Descripción de la falla')
                                ->placeholder('Ej. El equipo no enciende o presenta pantalla azul...')
                                ->rows(3)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->placeholder('Ej. Observaciones adicionales del servicio...')
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
                Tables\Columns\TextColumn::make('inventario.num_serie')->wrap(false)->limit(30)->grow(false)
                    ->label('No. serie (activo)')
                    ->fontFamily('mono')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('inventario.material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('tipo_mantenimiento')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo mantenimiento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
                    ->colors([
                        'success' => 'Preventivo',
                        'danger' => 'Correctivo',
                        'primary' => 'Mejora',
                    ])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tipo_mantenimiento', $search);
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('tipo_servicio')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo servicio')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('nombre_tecnico')->wrap(false)->limit(30)->grow(false)
                    ->label('Técnico')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)
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
                        'heroicon-m-clock' => ['Solicitado', 'Pendiente Revision Admin'],
                        'heroicon-m-wrench-screwdriver' => 'En proceso',
                        'heroicon-m-check-badge' => 'Completado',
                        'heroicon-m-x-circle' => 'Cancelado',
                    ])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('fecha_solicitud')->wrap(false)->limit(30)->grow(false)
                    ->label('Fecha sol.')
                    ->date('d/m/Y')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($search), $m)) {
                            $search = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                        }
                        return $query->where('fecha_solicitud', 'like', "%{$search}%");
                    }, isIndividual: true)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por equipo, técnico o tipo de servicio...')
            ->defaultPaginationPageOption(10)
            ->filters([])
            ->modifyQueryUsing(function (Builder $query, $livewire): Builder {
                $desde = $livewire->tableColumnSearches['fecha_desde'] ?? null;
                $hasta = $livewire->tableColumnSearches['fecha_hasta'] ?? null;
                if (filled($desde)) {
                    $query->whereDate('fecha_solicitud', '>=', $desde);
                }
                if (filled($hasta)) {
                    $query->whereDate('fecha_solicitud', '<=', $hasta);
                }
                return $query;
            })
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Mantenimiento $record) => $record?->inventario?->material?->nombre ?? 'Detalle de mantenimiento')
                    ->modalDescription(fn (?Mantenimiento $record) => new \Illuminate\Support\HtmlString(
                        'No. Serie: ' . e($record?->inventario?->num_serie ?? '—') . ' · ' . e($record?->tipo_servicio ?? 'Mantenimiento')
                    ))
                    ->modalCancelActionLabel('Cerrar'),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->slideOver()
                    ->modalHeading(fn (?Mantenimiento $record) => 'Editar: ' . ($record?->inventario?->material?->nombre ?? 'mantenimiento'))
                    ->modalDescription(fn (?Mantenimiento $record) => new \Illuminate\Support\HtmlString(
                        'No. Serie: ' . e($record?->inventario?->num_serie ?? '—') . ' · ' . e($record?->tipo_servicio ?? '')
                    ))
                    ->successNotificationTitle('Mantenimiento actualizado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\MantenimientoResource::infolist($infolist);
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id_usuario_solicita', auth()->id());
    }
}
