<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SolicitudResource\Pages;
use App\Models\Receptor;
use App\Models\Solicitud;
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
use Illuminate\Database\Eloquent\Builder;

class SolicitudResource extends Resource
{
    protected static ?string $model = Solicitud::class;

    protected static ?string $recordTitleAttribute = 'observaciones';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 2;

    public static function getGloballySearchableAttributes(): array
    {
        return ['observaciones'];
    }

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
                                    Forms\Components\DatePicker::make('fecha_solicitud')->label('Fecha de solicitud')
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
                            Forms\Components\ToggleButtons::make('estado')->label('Estado')
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
                            Forms\Components\Textarea::make('observaciones')->label('Observaciones')
                                ->label('Detalle / motivo de la solicitud')
                                ->placeholder('Explica qué necesitas y para qué...')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('detalles')
                                ->label('Materiales solicitados')
                                ->relationship('detalles')
                                ->schema([
                                    Forms\Components\Select::make('id_producto')->placeholder('Selecciona...')
                                        ->label('Material')
                                        ->relationship('material', 'nombre')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('cantidad')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->defaultItems(1)
                                ->addActionLabel('Añadir material'),
                        ]),

                    Forms\Components\Wizard\Step::make('Participantes y detalles')
                        ->icon('heroicon-m-users')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('id_usuario')->placeholder('Selecciona...')
                                        ->label('Solicitante (usuario)')
                                        ->relationship('usuario', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user'),
                                    Forms\Components\Select::make('id_receptor')->placeholder('Selecciona...')
                                        ->label('Receptor (área/persona)')
                                        ->relationship('receptor', 'nombre')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-user-group')
                                        ->live()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nombre')
                                                ->label('Nombre')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('apellido_paterno')
                                                ->label('Apellido paterno')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('apellido_materno')
                                                ->label('Apellido materno')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('email')
                                                ->label('Correo electrónico')
                                                ->email()
                                                ->maxLength(100),
                                            Forms\Components\TextInput::make('telefono')
                                                ->label('Teléfono')
                                                ->tel()
                                                ->maxLength(20),
                                            Forms\Components\Select::make('id_area')->placeholder('Selecciona...')
                                                ->label('Área')
                                                ->options(\App\Models\Area::pluck('nombre', 'id_area'))
                                                ->required()
                                                ->searchable()
                                                ->preload(),
                                        ]),
                                    Forms\Components\Placeholder::make('departamento')
                                        ->label('Departamento')
                                        ->content(function (callable $get) {
                                            $receptorId = $get('id_receptor');
                                            if ($receptorId) {
                                                $receptor = Receptor::with('area.departamento')->find($receptorId);

                                                return $receptor?->area?->departamento?->nombre ?? 'Sin departamento';
                                            }

                                            return 'Seleccione un receptor';
                                        }),
                                ]),
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('autorizado_por')->placeholder('Selecciona...')
                                        ->label('Autorizado por')
                                        ->relationship('autorizadoPor', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-shield-check'),
                                    Forms\Components\DatePicker::make('fecha_devolucion_estimada')
                                        ->label('Devolución estimada')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                    Forms\Components\DatePicker::make('fecha_devolucion_real')
                                        ->label('Devolución real')
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_movimiento') === 'Asignacion Temporal'),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_solicitud')->wrap(false)->limit(30)->grow(false)
                    ->label('Folio')
                    ->formatStateUsing(fn ($state) => 'FOLIO-'.str_pad($state, 5, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->weight(FontWeight::Bold)
                    ->extraAttributes(['class' => 'sigem-td-folio'])
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $num = preg_replace('/[^0-9]/', '', $search);
                        return $query->where(function (Builder $q) use ($search, $num) {
                            $q->where('id_solicitud', 'like', "%{$search}%");
                            if ($num !== '') {
                                $q->orWhere('id_solicitud', $num);
                            }
                        });
                    }, isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimiento')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo de movimiento')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tipo_movimiento', $search);
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('usuario.name')->wrap(false)->limit(30)->grow(false)
                    ->label('Solicitante')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('receptor.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Receptor')
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('receptor.area.departamento.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Departamento')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('receptor.area.departamento', fn ($q) => $q->where('nombre', 'like', '%' . trim($search) . '%'));
                    }, isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_solicitud')->wrap(false)->limit(30)->grow(false)->label('Fecha de solicitud')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($search), $m)) {
                            $search = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                        }
                        return $query->where('fecha_solicitud', 'like', "%{$search}%");
                    }, isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')->wrap(false)->limit(30)->grow(false)->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => '● ' . $state)
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Autorizado' => 'success',
                        'Rechazado' => 'danger',
                        'Entregado' => 'info',
                        default => 'gray',
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('estado', $search);
                    }, isIndividual: true),
                Tables\Columns\TextColumn::make('fecha_devolucion_estimada')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Devolución estimada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('autorizadoPor.name')->wrap(false)->limit(30)->grow(false)
                    ->label('Autorizado por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por folio, solicitante o detalle...')
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
                return $query;
            })
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
                    ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
                    ->action(function (Solicitud $record) {
                        $record->update([
                            'estado' => 'Autorizado',
                            'fecha_autorizacion' => now(),
                            'autorizado_por' => auth()->id(),
                        ]);
                        Notification::make()->title('Solicitud autorizada correctamente')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('rechazar')
                    ->iconButton()
                    ->color('danger')
                    ->tooltip('Rechazar')
                    ->extraAttributes(['title' => 'Rechazar'])
                    ->visible(fn (Solicitud $record): bool => $record->estado === 'Pendiente')
                    ->form([
                        Forms\Components\Textarea::make('motivo_rechazo')
                            ->label('Motivo de rechazo (opcional)')
                            ->rows(3),
                    ])
                    ->action(function (Solicitud $record, array $data) {
                        $observacion = $record->observaciones;
                        if ($data['motivo_rechazo']) {
                            $observacion = $observacion ? $observacion."\n\nMotivo Rechazo: ".$data['motivo_rechazo'] : 'Motivo Rechazo: '.$data['motivo_rechazo'];
                        }
                        $record->update([
                            'estado' => 'Rechazado',
                            'observaciones' => $observacion,
                        ]);
                        Notification::make()->title('Solicitud rechazada')->success()->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Solicitud $record) => 'Folio: ' . ($record?->id_solicitud ? 'FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT) : '—'))
                    ->modalDescription(fn (?Solicitud $record) => new \Illuminate\Support\HtmlString(
                        e($record?->tipo_movimiento ?? 'Por capturar') . ' · ' .
                        e($record?->estado ?? 'Por capturar')
                    ))
                    ->modalCancelActionLabel('Cerrar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Solicitud eliminada'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->tabs([
                        Tabs\Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('Datos de la solicitud')
                                    ->schema([
                                        TextEntry::make('id_solicitud')
                                            ->label('Folio')
                                            ->formatStateUsing(fn ($state) => $state ? 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT) : 'Por capturar')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-hashtag')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Fecha')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('tipo_movimiento')
                                            ->label('Tipo')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color('info')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Pendiente' => 'warning',
                                                'Autorizado' => 'success',
                                                'Completado' => 'success',
                                                'Entregado' => 'info',
                                                'Rechazado' => 'danger',
                                                'Cancelado' => 'gray',
                                                default => 'gray',
                                            })
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Participantes')
                            ->icon('heroicon-m-users')
                            ->schema([
                                Section::make('Participantes')
                                    ->schema([
                                        TextEntry::make('usuario.name')
                                            ->label('Solicitante')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('receptor.nombre')
                                            ->label('Receptor')
                                            ->icon('heroicon-m-user-group')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('receptor.area.departamento.nombre')
                                            ->label('Departamento')
                                            ->icon('heroicon-m-building-office')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('autorizadoPor.name')
                                            ->label('Autorizado por')
                                            ->icon('heroicon-m-shield-check')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Devolución')
                            ->icon('heroicon-m-arrow-uturn-left')
                            ->schema([
                                Section::make('Devoluciones y observaciones')
                                    ->schema([
                                        TextEntry::make('fecha_devolucion_estimada')
                                            ->label('Devolución estimada')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar-days')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('fecha_devolucion_real')
                                            ->label('Devolución real')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar-days')
                                            ->placeholder('Por capturar'),
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
