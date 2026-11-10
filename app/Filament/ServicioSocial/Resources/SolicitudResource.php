<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;
use App\Models\Solicitud;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
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

    protected static ?int $navigationSort = 1;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\SolicitudResource::form($form);
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
            ->searchPlaceholder('Buscar por folio o detalle...')
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
                            'Asignacion Permanente' => 'Asignación Permanente',
                            'Asignacion Temporal' => 'Asignación Temporal',
                            'Renta Externa' => 'Renta Externa',
                            default => $record?->tipo_movimiento ?? 'Solicitud',
                        }) . '</b> · ' . e($record?->fecha_solicitud?->format('d/m/Y') ?? '—')
                    ))
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?Solicitud $record) => 'Editar Solicitud ' . ($record?->id_solicitud ? 'FOLIO-' . str_pad($record->id_solicitud, 5, '0', STR_PAD_LEFT) : ''))
                    ->successNotificationTitle('Solicitud actualizada exitosamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\SolicitudResource::infolist($infolist);
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['usuario', 'receptor.area.departamento', 'autorizadoPor'])
            ->where('id_usuario', auth()->id());
    }
}
