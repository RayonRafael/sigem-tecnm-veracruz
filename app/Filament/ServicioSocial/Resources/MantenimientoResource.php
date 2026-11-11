<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;
use App\Models\Mantenimiento;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
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

    protected static ?int $navigationSort = 2;

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\MantenimientoResource::form($form);
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
            ->searchPlaceholder('Buscar por folio, equipo o falla...')
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
                    ->extraModalFooterActions(\App\Filament\Resources\MantenimientoResource::getAccionesPdfMantenimiento(true)),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?Mantenimiento $record) => 'Editar Mantenimiento #' . ($record?->id_mantenimiento ? str_pad($record->id_mantenimiento, 4, '0', STR_PAD_LEFT) : '—'))
                    ->successNotificationTitle('Mantenimiento actualizado exitosamente'),

                ...\App\Filament\Resources\MantenimientoResource::getAccionesPdfMantenimiento(false),
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
        return parent::getEloquentQuery()
            ->with(['inventario.material'])
            ->where('id_usuario_solicita', auth()->id());
    }
}
