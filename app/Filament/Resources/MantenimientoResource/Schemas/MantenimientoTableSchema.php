<?php

namespace App\Filament\Resources\MantenimientoResource\Schemas;

use App\Filament\Resources\MantenimientoResource\Actions\MantenimientoAcciones;
use App\Models\Mantenimiento;
use App\Support\SigemPagination;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MantenimientoTableSchema
{
    public static function getTable(Table $table, bool $esAdmin = true): Table
    {
        $columns = [
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
        ];

        if ($esAdmin) {
            $columns[] = Tables\Columns\TextColumn::make('nombre_tecnico')
                ->label('Técnico')
                ->searchable(isIndividual: true)
                ->sortable();
        }

        $columns[] = Tables\Columns\TextColumn::make('tipo_mantenimiento')
            ->label('Tipo')
            ->badge()
            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : '—')
            ->colors([
                'success' => 'Preventivo',
                'danger' => 'Correctivo',
                'primary' => 'Mejora',
            ])
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('tipo_mantenimiento', $search), isIndividual: true);

        $columns[] = Tables\Columns\TextColumn::make('estado')
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
            ->searchPlaceholder($esAdmin ? 'Buscar por folio, equipo, técnico o falla...' : 'Buscar por folio, equipo o falla...');

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
            ->actions(MantenimientoAcciones::getTableActions($esAdmin))
            ->bulkActions(MantenimientoAcciones::getBulkActions($esAdmin));
    }
}
