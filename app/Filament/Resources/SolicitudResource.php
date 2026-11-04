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
use Filament\Infolists\Components\ViewEntry;
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
                Forms\Components\Section::make('📝 Datos de la solicitud')
                    ->extraAttributes(['class' => 'form-card'])
                    ->schema([
                        Forms\Components\ViewField::make('tipo_solicitud')
                            ->label('¿Qué tipo de solicitud?')
                            ->view('filament.forms.components.tipo-solicitud-cards')
                            ->default('Salida')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('fecha_solicitud')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->helperText('Puedes programar la solicitud para otro día.')
                                    ->prefixIcon('heroicon-m-calendar'),

                                Forms\Components\Select::make('id_producto')
                                    ->label('Material')
                                    ->placeholder('Selecciona...')
                                    ->options(function () {
                                        return \App\Models\Material::pluck('nombre', 'id_producto');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Describe brevemente para qué necesitas el material...')
                            ->helperText('Esto ayuda al administrador a decidir más rápido.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('solicitante_info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div style="display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:13px;color:#0369a1;">'
                                . '<span>ℹ️</span> <span>Tu registro se enviará al administrador para su revisión</span>'
                                . '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('id_usuario')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('estado')
                            ->default('Pendiente'),

                        Forms\Components\Hidden::make('id_receptor')
                            ->default(fn () => Receptor::value('id_receptor') ?? 1),
                    ])
                    ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Solicitante')
                    ->searchable(isIndividual: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_movimiento')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'Asignacion Permanente', 'Salida' => 'Salida',
                        'Asignacion Temporal', 'Entrada' => 'Entrada',
                        'Renta Externa', 'Traslado' => 'Traslado',
                        default => $state ?? '—',
                    })
                    ->color('info')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $mapped = match (trim($search)) {
                            'Salida' => 'Asignacion Permanente',
                            'Entrada' => 'Asignacion Temporal',
                            'Traslado' => 'Renta Externa',
                            default => $search,
                        };
                        return $query->where('tipo_movimiento', $mapped);
                    }, isIndividual: true),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => '● ' . ($state ?? 'Pendiente'))
                    ->color(fn (?string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Autorizado' => 'success',
                        'Rechazado' => 'danger',
                        'Entregado', 'Completado' => 'info',
                        default => 'gray',
                    })
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
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Solicitud $record) => $record?->id_solicitud ? 'FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT) : 'Detalle de solicitud')
                    ->modalDescription(fn (?Solicitud $record) => new \Illuminate\Support\HtmlString(
                        'Solicitud de <b>' . e(match ($record?->tipo_movimiento) {
                            'Asignacion Permanente', 'Salida' => 'Salida',
                            'Asignacion Temporal', 'Entrada' => 'Entrada',
                            'Renta Externa', 'Traslado' => 'Traslado',
                            default => $record?->tipo_movimiento ?? 'Salida',
                        }) . '</b> · ' . e($record?->fecha_solicitud?->format('d/m/Y') ?? '—')
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
                        Tabs\Tab::make('informacion')
                            ->label('🔖 Información')
                            ->schema([
                                Section::make('ℹ️ Información de la solicitud')
                                    ->schema([
                                        TextEntry::make('id_solicitud')
                                            ->label('Folio')
                                            ->formatStateUsing(fn ($state) => $state ? 'FOLIO-' . str_pad($state, 5, '0', STR_PAD_LEFT) : '—')
                                            ->fontFamily('mono')
                                            ->weight(FontWeight::Bold),
                                        TextEntry::make('tipo_movimiento')
                                            ->label('Tipo')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'Asignacion Permanente', 'Salida' => 'Salida',
                                                'Asignacion Temporal', 'Entrada' => 'Entrada',
                                                'Renta Externa', 'Traslado' => 'Traslado',
                                                default => $state ?? '—',
                                            })
                                            ->color('info'),
                                        TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Pendiente' => 'warning',
                                                'Autorizado' => 'success',
                                                'Completado', 'Entregado' => 'info',
                                                'Rechazado' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('fecha_solicitud')
                                            ->label('Fecha')
                                            ->date('d/m/Y'),
                                    ])
                                    ->columns(2),

                                Section::make('👥 Participantes')
                                    ->schema([
                                        TextEntry::make('usuario.name')
                                            ->label('Solicitante')
                                            ->default('—'),
                                        TextEntry::make('autorizadoPor.name')
                                            ->label('Autorizado por')
                                            ->default('— Sin autorizar —'),
                                    ])
                                    ->columns(2),

                                Section::make('')
                                    ->schema([
                                        TextEntry::make('observaciones')
                                            ->label('Detalle (observaciones)')
                                            ->default('Sin observaciones registradas.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('historial')
                            ->label('🕘 Historial')
                            ->schema([
                                ViewEntry::make('historial')
                                    ->label('')
                                    ->view('filament.infolists.components.solicitud-historial')
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
            'index' => Pages\ListSolicituds::route('/'),
            'create' => Pages\CreateSolicitud::route('/create'),
            'edit' => Pages\EditSolicitud::route('/{record}/edit'),
        ];
    }
}
