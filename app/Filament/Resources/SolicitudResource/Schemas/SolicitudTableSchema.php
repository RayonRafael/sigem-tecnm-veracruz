<?php

namespace App\Filament\Resources\SolicitudResource\Schemas;

use App\Filament\Resources\SolicitudResource\Actions\SolicitudAcciones;
use App\Support\SigemPagination;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SolicitudTableSchema
{
    public static function getTable(Table $table, bool $esAdmin = true): Table
    {
        $columns = [
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
        ];

        if ($esAdmin) {
            $columns[] = Tables\Columns\TextColumn::make('usuario.name')
                ->label('Solicitante')
                ->searchable(isIndividual: true)
                ->sortable();
        }

        $columns[] = Tables\Columns\TextColumn::make('tipo_movimiento')
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
            }, isIndividual: true);

        $columns[] = Tables\Columns\TextColumn::make('estado')
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
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('estado', $search), isIndividual: true);

        $columns[] = Tables\Columns\TextColumn::make('fecha_solicitud')
            ->label('Fecha')
            ->date('d/m/Y')
            ->searchable(query: function (Builder $query, string $search): Builder {
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', trim($search), $m)) {
                    $search = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
                }

                return $query->where('fecha_solicitud', 'like', "%{$search}%");
            }, isIndividual: true)
            ->sortable();

        $table = $table
            ->columns($columns)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder($esAdmin ? 'Buscar por folio, solicitante o detalle...' : 'Buscar por folio o detalle...');

        if ($esAdmin) {
            $table
                ->defaultPaginationPageOption(fn () => SigemPagination::getPageOption())
                ->paginationPageOptions(fn () => SigemPagination::getPageOptions());
        } else {
            $table->defaultPaginationPageOption(10);
        }

        return $table
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
            ->actions(SolicitudAcciones::getTableActions($esAdmin))
            ->bulkActions(SolicitudAcciones::getBulkActions($esAdmin));
    }
}
