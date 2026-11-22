<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\Area;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class AreaResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = Area::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Áreas';
    protected static ?string $modelLabel = 'área';
    protected static ?string $pluralModelLabel = 'áreas';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder { return parent::getEloquentQuery()->with(['departamento']); }
    public static function getGloballySearchableAttributes(): array { return ['nombre']; }

    protected static function getFormSchema(): array
    {
        return [CatalogoBaseFormSchema::section('📍 Datos del área', 'heroicon-m-map-pin', [
            CatalogoBaseFormSchema::campoNombre('Nombre del área', 'Ej. Laboratorio de Redes'),
            CatalogoBaseFormSchema::selectRelacionConCreacion('id_departamento', 'Departamento', 'departamento', 'heroicon-m-building-office-2', '✏️ Agregar departamento como nueva opción', 'Nombre del departamento', 'Ej. Sistemas y Computación', 'El departamento estará disponible inmediatamente en el catálogo.', 'Departamento creado correctamente', true),
        ], 2)];
    }

    protected static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true, sortable: true),
            CatalogoBaseTableSchema::columnaTexto('departamento.nombre', 'Departamento', sortable: true),
            CatalogoBaseTableSchema::columnaFechaCreacion(),
        ];
    }

    protected static function getInfolistSections(): array
    {
        return [Section::make('📍 Datos del área')->schema([
            TextEntry::make('nombre')->label('Nombre del área')->icon('heroicon-m-map-pin'),
            TextEntry::make('departamento.nombre')->label('Departamento')->icon('heroicon-m-building-office-2'),
        ])->columns(2)];
    }

    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre o departamento...'; }
    protected static function getEditSuccessMessage(): string { return 'Área actualizada correctamente'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Área eliminada'; }
}
