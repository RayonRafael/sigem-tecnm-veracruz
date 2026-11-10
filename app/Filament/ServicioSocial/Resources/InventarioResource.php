<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Enums\EstadoInventario;
use App\Filament\Resources\InventarioResource as AdminInventarioResource;
use App\Filament\ServicioSocial\Resources\InventarioResource\Pages;
use App\Models\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'activo';

    protected static ?string $pluralModelLabel = 'activos';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return AdminInventarioResource::getEloquentQuery();
    }

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->estado !== EstadoInventario::DadoDeBaja->value;
    }

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'create' || ($form->getLivewire() instanceof \Filament\Resources\Pages\CreateRecord)) {
            return $form
                ->schema(AdminInventarioResource::getMockupFormSchema());
        }

        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs(static::getEditFormTabs())
                    ->columnSpanFull(),
            ]);
    }

    public static function getEditFormTabs(): array
    {
        return AdminInventarioResource::getEditFormTabs();
    }

    public static function obtenerTipoEquipoNormalizado(?\App\Models\Inventario $record = null, ?Forms\Get $get = null): string
    {
        return AdminInventarioResource::obtenerTipoEquipoNormalizado($record, $get);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->selectable(true)
            ->columns(AdminInventarioResource::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por equipo, código, IP, MAC, marca...')
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([])
            ->modifyQueryUsing(fn (Builder $query, $livewire): Builder => AdminInventarioResource::aplicarFiltrosTabla($query, $livewire))
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('↓ Exportar')
                    ->color('gray')
                    ->action(fn ($livewire) => AdminInventarioResource::exportarInventarioExcel($livewire)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle', 'class' => 'icon-btn'])
                    ->slideOver()
                    ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categoría')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Modificar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->visible(true)
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()
                    ->authorize(true)
                    ->iconButton()
                    ->tooltip('Modificar')
                    ->extraAttributes(['title' => 'Modificar', 'class' => 'icon-btn'])
                    ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
                    ))
                    ->slideOver()
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalCancelActionLabel('Cancelar')
                    ->mutateRecordDataUsing(fn (array $data, Inventario $record): array => AdminInventarioResource::hidratarDatosEdicion($data, $record))
                    ->using(fn (Inventario $record, array $data): Inventario => AdminInventarioResource::guardarCambiosEquipo($record, $data))
                    ->successNotificationTitle('Registro actualizado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return AdminInventarioResource::infolist($infolist);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}
