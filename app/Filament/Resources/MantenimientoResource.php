<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\MantenimientoResource\Pages;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class MantenimientoResource extends Resource
{
    protected static ?string $model = Mantenimiento::class;

    protected static ?string $recordTitleAttribute = 'descripcion_falla';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Mantenimiento';

    protected static ?string $modelLabel = 'mantenimiento';

    protected static ?string $pluralModelLabel = 'mantenimientos';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['descripcion_falla', 'nombre_tecnico', 'observaciones'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Equipo y técnico')
                        ->icon('heroicon-m-wrench')
                        ->schema([
                            Forms\Components\Select::make('id_inventario')->placeholder('Selecciona...')
                                ->label('Equipo a reparar')
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
                                        ->required()
                                        ->default(fn () => auth()->user() ? trim(auth()->user()->name.' '.auth()->user()->apellido_paterno.' '.auth()->user()->apellido_materno) : null)
                                        ->prefixIcon('heroicon-m-user')
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('num_control_tecnico')
                                        ->label('Número de control técnico')
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
                            Forms\Components\ToggleButtons::make('estado')->label('Estado')
                                ->options([
                                    'Solicitado' => 'Solicitado',
                                    'En proceso' => 'En proceso',
                                    'Pendiente Revision Admin' => 'En revisión',
                                    'Completado' => 'Completado',
                                    'Cancelado' => 'Cancelado',
                                ])
                                ->colors([
                                    'Solicitado' => 'warning',
                                    'En proceso' => 'primary',
                                    'Pendiente Revision Admin' => 'warning',
                                    'Completado' => 'success',
                                    'Cancelado' => 'danger',
                                ])
                                ->inline()
                                ->default('Pendiente Revision Admin')
                                ->required()
                                ->required()
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_solicitud')->label('Fecha de solicitud')
                                        ->default(now())
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->required(),
                                    Forms\Components\DatePicker::make('fecha_inicio')->label('Fecha de inicio')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->label('Fecha inicio'),
                                    Forms\Components\DatePicker::make('fecha_fin')->label('Fecha de fin')
                                        ->prefixIcon('heroicon-m-calendar')
                                        ->label('Fecha fin'),
                                ]),
                            Forms\Components\Textarea::make('descripcion_falla')
                                ->label('Descripción de la falla')
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('descripcion_trabajo')
                                ->label('Trabajo realizado')
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('observaciones')->label('Observaciones')
                                ->label('Observaciones')
                                ->rows(2)
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
                    ->label('Equipo')
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'sigem-td-folio'])
                    ->searchable()
                    ->description(fn (Mantenimiento $record): ?string => $record->inventario?->material?->nombre),
                Tables\Columns\TextColumn::make('inventario.material.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Material')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tipo_mantenimiento')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Tipo mantenimiento')
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
                Tables\Columns\TextColumn::make('tipo_servicio')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo servicio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nombre_tecnico')->wrap(false)->limit(30)->grow(false)
                    ->label('Técnico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Pendiente Revision Admin' => 'En revisión',
                        'Completado' => 'Terminado',
                        'Cancelado' => 'Rechazado',
                        default => $state,
                    })
                    ->badge()
                    ->colors([
                        'info'    => ['En revisión', 'Pendiente Revision Admin'],
                        'warning' => ['En proceso', 'Solicitado'],
                        'success' => ['Terminado', 'Completado'],
                        'danger'  => ['Rechazado', 'Cancelado'],
                        'gray'    => 'otros',
                    ]),
                Tables\Columns\TextColumn::make('fecha_solicitud')->wrap(false)->limit(30)->grow(false)->label('Fecha de solicitud')
                    ->label('Fecha sol.')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_inicio')->wrap(false)->limit(30)->grow(false)->label('Fecha de inicio')
                    ->label('Fecha inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fecha_fin')->wrap(false)->limit(30)->grow(false)->label('Fecha de fin')
                    ->label('Fecha fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([
                Tables\Filters\SelectFilter::make('estado')->label('Estado')
                    ->options([
                        'Solicitado' => 'Solicitado',
                        'En proceso' => 'En proceso',
                        'Pendiente Revision Admin' => 'En revisión',
                        'Completado' => 'Completado',
                        'Cancelado' => 'Cancelado',
                    ]),
                Tables\Filters\SelectFilter::make('tipo_mantenimiento')
                    ->label('Tipo de mantenimiento')
                    ->options([
                        'Preventivo' => 'Preventivo',
                        'Correctivo' => 'Correctivo',
                        'Mejora' => 'Mejora',
                    ]),
            ])

            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\Action::make('autorizar')
                    ->iconButton()
                    ->color('success')
                    ->tooltip('Autorizar')
                    ->extraAttributes(['title' => 'Autorizar'])
                    ->visible(fn (Mantenimiento $record): bool => in_array($record->estado, ['Pendiente Revision Admin', 'En revisión']))
                    ->action(function (Mantenimiento $record) {
                        $record->update(['estado' => 'En proceso']);
                        Notification::make()->title('Mantenimiento autorizado correctamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('completar')
                    ->iconButton()
                    ->color('info')
                    ->tooltip('Completar')
                    ->extraAttributes(['title' => 'Completar'])
                    ->visible(fn (Mantenimiento $record): bool => $record->estado === 'En proceso')
                    ->action(function (Mantenimiento $record) {
                        $record->update(['estado' => 'Completado', 'fecha_fin' => now()]);
                        Notification::make()->title('Mantenimiento completado')->success()->send();
                    })
                    ->requiresConfirmation(),
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
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Editar')->successNotificationTitle('Mantenimiento actualizado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Mantenimiento eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->tabs([
                        Tabs\Tab::make('Servicio')
                            ->icon('heroicon-m-wrench')
                            ->schema([
                                Section::make('Equipo y técnico')
                                    ->schema([
                                        TextEntry::make('inventario.num_serie')
                                            ->label('No. serie')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-qr-code')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('inventario.material.nombre')
                                            ->label('Material')
                                            ->icon('heroicon-m-cube')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('nombre_tecnico')
                                            ->label('Técnico')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('num_control_tecnico')
                                            ->label('No. control')
                                            ->icon('heroicon-m-identification')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                                Section::make('Detalles del servicio')
                                    ->schema([
                                        TextEntry::make('tipo_servicio')
                                            ->label('Servicio')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color('info')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('tipo_mantenimiento')
                                            ->label('Tipo')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Preventivo' => 'success',
                                                'Correctivo' => 'danger',
                                                'Mejora' => 'info',
                                                default => 'gray',
                                            })
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(function (?string $state): string {
                                                if (! $state) {
                                                    return 'Por capturar';
                                                }
                                                $label = match ($state) {
                                                    'Pendiente Revision Admin' => 'En revisión',
                                                    'Completado' => 'Terminado',
                                                    'Cancelado' => 'Rechazado',
                                                    default => $state,
                                                };
                                                return '● ' . $label;
                                            })
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Solicitado' => 'warning',
                                                'En proceso' => 'primary',
                                                'Pendiente Revision Admin', 'En revisión' => 'info',
                                                'Completado', 'Terminado' => 'success',
                                                'Cancelado', 'Rechazado' => 'danger',
                                                default => 'gray',
                                            })
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Solicitud')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('fecha_inicio')
                                            ->label('Inicio')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('fecha_fin')
                                            ->label('Fin')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Fallas y Diagnóstico')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->schema([
                                Section::make('Fechas y reportes')
                                    ->schema([
                                        TextEntry::make('descripcion_falla')
                                            ->label('Falla')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        TextEntry::make('descripcion_trabajo')
                                            ->label('Trabajo realizado')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        TextEntry::make('observaciones')
                                            ->label('Observaciones')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
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
