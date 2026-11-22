<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Resources\MaterialResource\Schemas\MaterialSchema;
use App\Models\Material;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = Material::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Materiales';
    protected static ?string $modelLabel = 'material';
    protected static ?string $pluralModelLabel = 'materiales';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withStockDerivado()
            ->with(['marca', 'tipo', 'unidad']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'descripcion', 'modelo'];
    }

    protected static function getFormSchema(): array { return MaterialSchema::getFormSchema(); }
    protected static function getTableColumns(): array { return MaterialSchema::getTableColumns(); }
    protected static function getTableActions(bool $soloLectura): array { return MaterialSchema::getTableActions($soloLectura); }
    protected static function getInfolistTabs(): array { return MaterialSchema::getInfolistTabs(); }
    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre, modelo, marca o tipo...'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Material eliminado'; }
}
