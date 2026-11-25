<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\UnidadMedida;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;

class UnidadMedidaResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = UnidadMedida::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-bars-3-bottom-left';
    protected static ?string $navigationLabel = 'Unidades de medida';
    protected static ?string $modelLabel = 'unidad de medida';
    protected static ?string $pluralModelLabel = 'unidades de medida';
    protected static ?int $navigationSort = 5;

    public static function getGloballySearchableAttributes(): array { return ['nombre']; }

    protected static function getFormSchema(): array
    {
        return [
            CatalogoBaseFormSchema::section('📏 Datos de la unidad de medida', 'heroicon-m-bars-3-bottom-left', [
                CatalogoBaseFormSchema::campoNombre('Nombre de la unidad', 'Ej. Pieza, Caja, Metro', 'heroicon-m-bars-3-bottom-left', 50, UnidadMedida::class, 'una unidad de medida'),
                CatalogoBaseFormSchema::alertaSimilitudCatalogo('nombre', UnidadMedida::class, 'una unidad de medida'),
            ]),
        ];
    }

    protected static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true, sortable: true),
            CatalogoBaseTableSchema::columnaFechaCreacion(),
        ];
    }

    protected static function getInfolistSections(): array
    {
        return [
            Section::make('📏 Datos de la unidad de medida')->schema([
                TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-bars-3-bottom-left'),
                TextEntry::make('materiales_count')->label('Materiales vinculados')->state(fn (UnidadMedida $record): string => $record->materiales()->count() . ' vinculados')->icon('heroicon-m-cube'),
            ])->columns(2),
        ];
    }

    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre de la unidad...'; }
    protected static function getEditSuccessMessage(): string { return 'Unidad de medida actualizada correctamente'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Unidad de medida eliminada'; }
}
