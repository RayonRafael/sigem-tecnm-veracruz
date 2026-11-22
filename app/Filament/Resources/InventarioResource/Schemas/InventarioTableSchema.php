<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Filament\Resources\InventarioResource;
use App\Filament\Resources\InventarioResource\Actions\EditarEquipoAction;
use App\Filament\Resources\InventarioResource\Actions\VerDetalleAction;
use App\Models\Inventario;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemPagination;
use App\Support\SigemPdfGenerator;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventarioTableSchema
{
    public static function getColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('departamento.nombre')
                ->label('Departamento')
                ->extraHeaderAttributes(['style' => 'min-width: 180px;'])
                ->extraCellAttributes(['style' => 'min-width: 180px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'])
                ->tooltip(fn (Inventario $record): ?string => $record->departamento?->nombre ?? $record->area?->departamento?->nombre)
                ->getStateUsing(function (Inventario $record): ?string {
                    return $record->departamento?->nombre
                        ?? $record->area?->departamento?->nombre
                        ?? ($record->ubicacion_fisica ? explode(' > ', $record->ubicacion_fisica)[0] ?? null : null)
                        ?? '—';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                            ->orWhereHas('area.departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                            ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('departamento', 'inventario.departamento_id', '=', 'departamento.id_departamento')
                        ->orderBy('departamento.nombre', $direction)
                        ->select('inventario.*');
                })
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('equipo')
                ->label('Equipo')
                ->html()
                ->extraHeaderAttributes(['style' => 'min-width: 250px;'])
                ->extraCellAttributes(['style' => 'min-width: 250px; max-width: 320px;'])
                ->getStateUsing(function (Inventario $record): string {
                    $nombre = $record->material?->nombre ?? $record->subtipo ?? 'Equipo';
                    $cod = ! empty($record->codigo) ? $record->codigo : null;
                    if ($cod) {
                        $sub = str_starts_with($cod, 'ID: ') ? $cod : 'ID: ' . $cod;
                    } else {
                        $sub = 'ID: #' . ($record->id_inventario ?? $record->id);
                    }
                    $titleMain = e($nombre);
                    $titleSub = e($cod ?? ('#' . ($record->id_inventario ?? $record->id)));

                    return '<div class="td-main" title="' . $titleMain . '" style="font-weight:600;line-height:1.25;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $titleMain . '</div>'
                        . '<div class="td-sub" title="' . $titleSub . '" style="font-size:11.5px;color:#6b7280;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . e($sub) . '</div>';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('material', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                            ->orWhere('subtipo', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%")
                            ->orWhere('num_serie', 'like', "%{$search}%")
                            ->orWhere('modelo', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                        ->orderBy('material.nombre', $direction)
                        ->select('inventario.*');
                }),

            Tables\Columns\TextColumn::make('categoria')
                ->label('Categoría')
                ->badge()
                ->color('gray')
                ->extraHeaderAttributes(['style' => 'min-width: 120px;'])
                ->extraCellAttributes(['style' => 'min-width: 120px;'])
                ->getStateUsing(fn (Inventario $record): string => $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('material.tipo', fn ($sub) => $sub->where('nombre', 'like', trim($search) . '%'))
                            ->orWhere('subtipo', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                        ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                        ->orderBy('tipo_material.nombre', $direction)
                        ->select('inventario.*');
                }),

            Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->html()
                ->extraHeaderAttributes(['style' => 'min-width: 130px;'])
                ->extraCellAttributes(['style' => 'min-width: 130px;'])
                ->getStateUsing(function (Inventario $record): string {
                    $estado = $record->estado ?? 'Disponible';
                    $colors = match ($estado) {
                        'Disponible' => ['bg' => '#d1fae5', 'text' => '#065f46', 'border' => '#a7f3d0', 'dot' => '#059669', 'class' => 'ok'],
                        'En mantenimiento', 'En Mantenimiento' => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fde68a', 'dot' => '#d97706', 'class' => 'warn'],
                        'Cambio de área', 'Asignado', 'Reasignado' => ['bg' => '#e0f2fe', 'text' => '#075985', 'border' => '#bae6fd', 'dot' => '#0284c7', 'class' => 'info'],
                        'Solicitud de baja' => ['bg' => '#fef3c7', 'text' => '#b45309', 'border' => '#fde68a', 'dot' => '#f59e0b', 'class' => 'warn'],
                        'Dado de baja', 'Dañado', 'Baja' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fecaca', 'dot' => '#dc2626', 'class' => 'danger'],
                        default => ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#e5e7eb', 'dot' => '#9ca3af', 'class' => 'neutral'],
                    };

                    return '<span class="pill ' . $colors['class'] . '" style="display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap;background-color:' . $colors['bg'] . ';color:' . $colors['text'] . ';border:1px solid ' . $colors['border'] . ';">'
                        . '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background-color:' . $colors['dot'] . ';flex-shrink:0;"></span>'
                        . e($estado)
                        . '</span>';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where('estado', 'like', "%{$search}%");
                }, isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('area.nombre')
                ->label('Área')
                ->extraHeaderAttributes(['style' => 'min-width: 180px;'])
                ->extraCellAttributes(['style' => 'min-width: 180px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'])
                ->tooltip(function (Inventario $record): ?string {
                    if ($record->area?->nombre) {
                        return $record->area->nombre;
                    }
                    if (! empty($record->ubicacion_fisica)) {
                        $parts = explode(' > ', $record->ubicacion_fisica);

                        return $parts[1] ?? $parts[0] ?? null;
                    }

                    return null;
                })
                ->getStateUsing(function (Inventario $record): ?string {
                    if ($record->area?->nombre) {
                        return $record->area->nombre;
                    }
                    if (! empty($record->ubicacion_fisica)) {
                        $parts = explode(' > ', $record->ubicacion_fisica);

                        return $parts[1] ?? $parts[0] ?? null;
                    }

                    return '—';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('area', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                            ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                        ->orderBy('area.nombre', $direction)
                        ->select('inventario.*');
                })
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('aula')
                ->label('Aula')
                ->extraHeaderAttributes(['style' => 'min-width: 80px;'])
                ->extraCellAttributes(['style' => 'min-width: 80px;'])
                ->getStateUsing(function (Inventario $record): string {
                    return $record->aula
                        ?? $record->salon?->nombre
                        ?? ($record->especificaciones['aula'] ?? null)
                        ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                        ?? '—';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('inventario.aula', 'like', "%{$search}%")
                            ->orWhereHas('salon', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                            ->orWhere('especificaciones->aula', 'like', "%{$search}%")
                            ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    $dir = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

                    return $query->leftJoin('salones', 'inventario.salon_id', '=', 'salones.id')
                        ->orderByRaw("COALESCE(NULLIF(inventario.aula, ''), salones.nombre) {$dir}")
                        ->select('inventario.*');
                })
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('num_serie')
                ->label('No. de serie')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'])
                ->tooltip(fn (?string $state): ?string => $state)
                ->searchable(isIndividual: true)
                ->sortable()
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('ip_address')
                ->label('IP')
                ->extraHeaderAttributes(['style' => 'min-width: 120px;'])
                ->extraCellAttributes(['style' => 'min-width: 120px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? '—')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('ip_address', 'like', "%{$search}%")
                            ->orWhere('especificaciones->ip_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('mac_address')
                ->label('MAC')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? '—')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('mac_address', 'like', "%{$search}%")
                            ->orWhere('especificaciones->mac_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('—'),
        ];
    }

    public static function aplicarFiltros(Builder $query, $livewire): Builder
    {
        $desde = $livewire->tableColumnSearches['fecha_desde'] ?? null;
        $hasta = $livewire->tableColumnSearches['fecha_hasta'] ?? null;
        if (filled($desde)) {
            $query->where(function (Builder $q) use ($desde) {
                $q->whereDate('fecha_registro', '>=', $desde)
                    ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '>=', $desde));
            });
        }
        if (filled($hasta)) {
            $query->where(function (Builder $q) use ($hasta) {
                $q->whereDate('fecha_registro', '<=', $hasta)
                    ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '<=', $hasta));
            });
        }
        $estadoResumen = $livewire->tableFilters['estado']['value'] ?? null;
        if (filled($estadoResumen)) {
            $query->where('estado', $estadoResumen);
        }

        return $query;
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->columns(static::getColumns())
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por equipo, código, IP, MAC, marca...')
            ->defaultPaginationPageOption(fn () => SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => SigemPagination::getPageOptions())
            ->filters([])
            ->modifyQueryUsing(fn (Builder $query, $livewire): Builder => static::aplicarFiltros($query, $livewire))
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('↓ Exportar')
                    ->color('gray')
                    ->action(fn ($livewire) => InventarioResource::exportarInventarioExcel($livewire)),
            ])
            ->actions([
                VerDetalleAction::make(),
                EditarEquipoAction::make(),
                VerDetalleAction::checklistCambioAreaAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('aprobarMasivo')
                        ->label('Aprobar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()->can('aprobaciones.aprobar'))
                        ->action(function (Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if (! $record->aprobado) {
                                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                                    }
                                }
                            });
                            Notification::make()->title('Registros aprobados exitosamente')->success()->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('formatosBajaLote')
                        ->label('📄 Formatos de Baja (lote)')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $bajas = SigemBajaFormatHelper::filtrarInventariosEnBaja($records);
                            if ($error = SigemBajaFormatHelper::mensajeErrorLote($bajas)) {
                                Notification::make()
                                    ->title($error)
                                    ->warning()
                                    ->send();

                                return null;
                            }

                            return SigemPdfGenerator::descargarFormatosBajaLote($bajas);
                        }),
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Registro de inventario eliminado'),
                ]),
            ]);
    }
}
