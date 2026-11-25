<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\MarcaMaterial;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;

class MarcaMaterialResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = MarcaMaterial::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Marcas';
    protected static ?string $modelLabel = 'marca';
    protected static ?string $pluralModelLabel = 'marcas';
    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array { return ['nombre']; }

    protected static function getFormSchema(): array
    {
        return [
            CatalogoBaseFormSchema::section('🏷 Datos de la marca', 'heroicon-m-tag', [
                CatalogoBaseFormSchema::campoNombre('Nombre de la marca', 'Ej. Dell, HP, Cisco', uniqueModel: MarcaMaterial::class, uniqueLabel: 'una marca'),
                CatalogoBaseFormSchema::alertaSimilitudCatalogo('nombre', MarcaMaterial::class, 'una marca'),
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
            Section::make('🏷 Datos de la marca')->schema([
                TextEntry::make('nombre')->label('Nombre de la marca')->icon('heroicon-m-tag'),
                TextEntry::make('materiales_count')->label('Materiales asociados')->state(fn (MarcaMaterial $record): string => $record->materiales()->count() . ' registrados')->icon('heroicon-m-cube'),
            ])->columns(2),
        ];
    }

    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre de la marca...'; }
    protected static function getEditSuccessMessage(): string { return 'Marca actualizada correctamente'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Marca eliminada'; }
}
