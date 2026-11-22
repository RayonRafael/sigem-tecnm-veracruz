<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Resources\TipoMaterialResource\Schemas\TipoMaterialSchema;
use App\Models\TipoMaterial;
use Filament\Resources\Resource;

class TipoMaterialResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = TipoMaterial::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tipos de material';
    protected static ?string $modelLabel = 'tipo de material';
    protected static ?string $pluralModelLabel = 'tipos de material';
    protected static ?int $navigationSort = 4;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'familia'];
    }

    public static function getFamiliasOptions(): array
    {
        return [
            'Equipos completos' => 'Equipos completos',
            'Componentes internos' => 'Componentes internos',
            'Periféricos y pantallas' => 'Periféricos y pantallas',
            'Redes y telecomunicaciones' => 'Redes y telecomunicaciones',
            'Cableado y conectividad' => 'Cableado y conectividad',
            'Accesorios y almacenamiento' => 'Accesorios y almacenamiento',
            'Consumibles y limpieza' => 'Consumibles y limpieza',
            'Herramientas y mobiliario' => 'Herramientas y mobiliario',
        ];
    }

    protected static function getFormSchema(): array { return TipoMaterialSchema::getFormSchema(); }
    protected static function getTableColumns(): array { return TipoMaterialSchema::getTableColumns(); }
    protected static function getInfolistSections(): array { return TipoMaterialSchema::getInfolistSections(); }
    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre del tipo...'; }
    protected static function getEditSuccessMessage(): string { return 'Tipo de material actualizado correctamente'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Tipo de material eliminado'; }
}
