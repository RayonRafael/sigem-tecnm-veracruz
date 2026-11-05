<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MantenimientoResource\Pages;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
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

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inventario.material']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['descripcion_falla', 'nombre_tecnico', 'observaciones'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🔧 Datos del mantenimiento')
                    ->extraAttributes(['class' => 'form-card'])
                    ->schema([
                        Forms\Components\Select::make('id_inventario')
                            ->label('¿Qué equipo necesita servicio?')
                            ->placeholder('Selecciona...')
                            ->options(function (): array {
                                return Inventario::with('material')
                                    ->get()
                                    ->mapWithKeys(function ($inv) {
                                        $code = $inv->codigo ?: $inv->num_serie ?: $inv->numero_serie ?: 'S/N';
                                        $name = $inv->material?->nombre ?: $inv->subtipo ?: 'Equipo';
                                        return [$inv->id_inventario => "{$code} — {$name}"];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\ViewField::make('tipo_mantenimiento')
                            ->label('Tipo de servicio')
                            ->view('filament.forms.components.tipo-mantenimiento-cards')
                            ->default('Correctivo')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nombre_tecnico')
                                    ->label('Nombre del técnico')
                                    ->placeholder('Ej. Juan Pérez')
                                    ->default(fn () => auth()->user() ? trim(auth()->user()->name . ' ' . (auth()->user()->apellido_paterno ?? '')) : '')
                                    ->required(),

                                Forms\Components\TextInput::make('num_control_tecnico')
                                    ->label('No. de control del técnico')
                                    ->placeholder('Ej. 20240001')
                                    ->default(fn () => auth()->user()?->num_control ?? '')
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('descripcion_falla')
                            ->label('Describe la falla')
                            ->placeholder('Ej. El equipo no enciende. Se escucha un ruido extraño al arrancar...')
                            ->helperText('Entre más detalle des, más rápido se autoriza el servicio.')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('id_usuario_solicita')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('tipo_servicio')
                            ->default('Servicio Social'),

                        Forms\Components\Hidden::make('estado')
                            ->default('Pendiente Revision Admin'),

                        Forms\Components\Hidden::make('fecha_solicitud')
                            ->default(now()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_mantenimiento')
                    ->label('Folio')
                    ->formatStateUsing(fn ($state) => '#' . str_pad($state, 4, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'sigem-td-folio'])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $num = preg_replace('/[^0-9]/', '', $search);
                        return $query->where(function (Builder $q) use ($search, $num) {
                            $q->where('id_mantenimiento', 'like', "%{$search}%");
                            if ($num !== '') {
                                $q->orWhere('id_mantenimiento', $num);
                            }
                        });
                    }, isIndividual: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventario.num_serie')
                    ->label('Equipo')
                    ->getStateUsing(fn (Mantenimiento $record): string => $record->inventario?->codigo ?? $record->inventario?->num_serie ?? '—')
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'sigem-td-folio'])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('inventario', function (Builder $q) use ($search) {
                            $q->where('codigo', 'like', "%{$search}%")
                              ->orWhere('num_serie', 'like', "%{$search}%")
                              ->orWhereHas('material', fn ($m) => $m->where('nombre', 'like', "%{$search}%"));
                        });
                    }, isIndividual: true)
                    ->description(fn (Mantenimiento $record): ?string => $record->inventario?->material?->nombre ?? $record->inventario?->subtipo),

                Tables\Columns\TextColumn::make('nombre_tecnico')
                    ->label('Técnico')
                    ->searchable(isIndividual: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_mantenimiento')
                    ->label('Tipo')
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

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'Pendiente Revision Admin' => '● En revisión',
                        'Completado' => '● Terminado',
                        'Cancelado' => '● Rechazado',
                        'Solicitado' => '● En revisión',
                        default => '● ' . $state,
                    })
                    ->badge()
                    ->colors([
                        'info' => ['En revisión', 'Pendiente Revision Admin', 'Solicitado'],
                        'warning' => 'En proceso',
                        'success' => ['Terminado', 'Completado'],
                        'danger' => ['Rechazado', 'Cancelado'],
                        'gray' => 'otros',
                    ])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true),

                Tables\Columns\TextColumn::make('fecha_solicitud')
                    ->label('Fecha')
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
            ->searchPlaceholder('Buscar por folio, equipo, técnico o falla...')
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
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
                $area = $livewire->tableColumnSearches['area'] ?? null;
                if (filled($area)) {
                    $query->whereHas('inventario', fn ($q) => $q->where('area_id', $area));
                }
                return $query;
            })
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Mantenimiento $record) => 'Mantenimiento #' . ($record?->id_mantenimiento ? str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT) : '—'))
                    ->modalDescription(fn (?Mantenimiento $record) => new \Illuminate\Support\HtmlString(
                        'Equipo: <b>' . e($record?->inventario?->codigo ?? $record?->inventario?->num_serie ?? '—') . '</b> · ' . e($record?->fecha_solicitud?->format('d/m/Y') ?? '—')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('autorizar_slideover')
                            ->label('✓ Autorizar servicio')
                            ->color('primary')
                            ->visible(fn (?Mantenimiento $record): bool => in_array($record?->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
                            ->requiresConfirmation()
                            ->modalHeading('Autorizar mantenimiento')
                            ->modalDescription('El reporte pasará a En proceso y el equipo pasará a estado En mantenimiento.')
                            ->modalSubmitActionLabel('Sí, autorizar')
                            ->action(function (Mantenimiento $record) {
                                $record->update(['estado' => 'En proceso', 'fecha_inicio' => now()]);
                                $record->inventario?->update(['estado' => 'En Mantenimiento']);
                                Notification::make()->title('Mantenimiento autorizado correctamente')->success()->send();
                            }),
                        Tables\Actions\Action::make('rechazar_slideover')
                            ->label('✕ Rechazar')
                            ->color('danger')
                            ->visible(fn (?Mantenimiento $record): bool => in_array($record?->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
                            ->requiresConfirmation()
                            ->modalHeading('Rechazar mantenimiento')
                            ->modalDescription('El reporte quedará marcado como rechazado y el estado del equipo no cambiará.')
                            ->modalSubmitActionLabel('Sí, rechazar')
                            ->action(function (Mantenimiento $record) {
                                $record->update(['estado' => 'Cancelado']);
                                // Al rechazar, el estado del Inventario NO cambia
                                Notification::make()->title('Mantenimiento rechazado')->danger()->send();
                            }),
                        Tables\Actions\Action::make('completar_slideover')
                            ->label('Completar')
                            ->color('success')
                            ->visible(fn (?Mantenimiento $record): bool => $record?->estado === 'En proceso')
                            ->requiresConfirmation()
                            ->modalHeading('Completar mantenimiento')
                            ->modalDescription('Se marcará el servicio como finalizado y el equipo volverá a Disponible.')
                            ->modalSubmitActionLabel('Completar')
                            ->action(function (Mantenimiento $record) {
                                $record->update(['estado' => 'Completado', 'fecha_fin' => now()]);
                                $record->inventario?->update(['estado' => 'Disponible']);
                                Notification::make()->title('Mantenimiento completado')->success()->send();
                            }),
                    ]),

                Tables\Actions\Action::make('autorizar')
                    ->iconButton()
                    ->color('success')
                    ->icon('heroicon-m-check')
                    ->tooltip('Autorizar')
                    ->extraAttributes(['title' => 'Autorizar'])
                    ->visible(fn (Mantenimiento $record): bool => in_array($record->estado, ['Pendiente Revision Admin', 'En revisión', 'Solicitado']))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Mantenimiento $record) => 'Autorizar mantenimiento #' . str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT))
                    ->modalDescription('El reporte pasará a En proceso y el equipo pasará a estado En mantenimiento.')
                    ->modalSubmitActionLabel('Sí, autorizar')
                    ->action(function (Mantenimiento $record) {
                        $record->update(['estado' => 'En proceso', 'fecha_inicio' => now()]);
                        $record->inventario?->update(['estado' => 'En Mantenimiento']);
                        Notification::make()->title('Mantenimiento autorizado correctamente')->success()->send();
                    }),

                Tables\Actions\Action::make('completar')
                    ->iconButton()
                    ->color('info')
                    ->icon('heroicon-m-check-badge')
                    ->tooltip('Completar')
                    ->extraAttributes(['title' => 'Completar'])
                    ->visible(fn (Mantenimiento $record): bool => $record->estado === 'En proceso')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Mantenimiento $record) => 'Completar mantenimiento #' . str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT))
                    ->modalDescription('Se marcará el servicio como finalizado y el equipo volverá a Disponible.')
                    ->modalSubmitActionLabel('Completar')
                    ->action(function (Mantenimiento $record) {
                        $record->update(['estado' => 'Completado', 'fecha_fin' => now()]);
                        $record->inventario?->update(['estado' => 'Disponible']);
                        Notification::make()->title('Mantenimiento completado')->success()->send();
                    }),
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
                    ->extraAttributes(['style' => 'display: flex; flex-direction: column;'])
                    ->tabs([
                        Tabs\Tab::make('informacion')
                            ->label('🔖 Información')
                            ->schema([
                                Section::make('💻 Equipo')
                                    ->schema([
                                        TextEntry::make('inventario.num_serie')
                                            ->label('Equipo')
                                            ->getStateUsing(fn (Mantenimiento $record): string => $record->inventario?->codigo ?? $record->inventario?->num_serie ?? '—')
                                            ->fontFamily('mono')
                                            ->weight(FontWeight::Bold)
                                            ->default('—'),
                                        TextEntry::make('inventario.material.nombre')
                                            ->label('Material')
                                            ->getStateUsing(fn (Mantenimiento $record): string => $record->inventario?->material?->nombre ?? $record->inventario?->subtipo ?? '—')
                                            ->default('—'),
                                        TextEntry::make('nombre_tecnico')
                                            ->label('Técnico asignado')
                                            ->default('—'),
                                        TextEntry::make('num_control_tecnico')
                                            ->label('No. de control')
                                            ->default('—'),
                                    ])
                                    ->columns(2),

                                Section::make('🧰 Detalle del servicio')
                                    ->schema([
                                        TextEntry::make('tipo_mantenimiento')
                                            ->label('Tipo de servicio')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
                                            ->colors([
                                                'success' => 'Preventivo',
                                                'danger' => 'Correctivo',
                                                'primary' => 'Mejora',
                                            ]),
                                        TextEntry::make('estado')
                                            ->label('Estado actual')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => match ($state) {
                                                'Pendiente Revision Admin' => '● En revisión',
                                                'Completado' => '● Terminado',
                                                'Cancelado' => '● Rechazado',
                                                'Solicitado' => '● En revisión',
                                                default => '● ' . $state,
                                            })
                                            ->colors([
                                                'info' => ['En revisión', 'Pendiente Revision Admin', 'Solicitado'],
                                                'warning' => 'En proceso',
                                                'success' => ['Terminado', 'Completado'],
                                                'danger' => ['Rechazado', 'Cancelado'],
                                                'gray' => 'otros',
                                            ]),
                                        TextEntry::make('descripcion_falla')
                                            ->label('Descripción de la falla')
                                            ->default('Sin descripción registrada.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('🗓 Fechas y estado')
                                    ->schema([
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Fecha de solicitud')
                                            ->date('d/m/Y')
                                            ->default('—'),
                                        TextEntry::make('fecha_inicio')
                                            ->label('Fecha de inicio')
                                            ->date('d/m/Y')
                                            ->placeholder('— pendiente de autorizar'),
                                        TextEntry::make('fecha_fin')
                                            ->label('Fecha de fin')
                                            ->date('d/m/Y')
                                            ->placeholder('— pendiente de completar'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tabs\Tab::make('historial')
                            ->label('🕘 Historial')
                            ->schema([
                                ViewEntry::make('historial')
                                    ->label('')
                                    ->view('filament.infolists.components.mantenimiento-historial')
                                    ->columnSpanFull(),
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
