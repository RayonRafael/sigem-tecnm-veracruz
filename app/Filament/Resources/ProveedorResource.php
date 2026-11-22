<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Resources\ProveedorResource\Schemas\ProveedorSchema;
use App\Models\Proveedor;
use Filament\Resources\Resource;

class ProveedorResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = Proveedor::class;
    protected static ?string $recordTitleAttribute = 'nombre_empresa';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'proveedor';
    protected static ?string $pluralModelLabel = 'proveedores';
    protected static ?int $navigationSort = 6;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_empresa', 'rfc', 'direccion', 'contacto_nombre'];
    }

    protected static function getFormSchema(): array { return ProveedorSchema::getFormSchema(); }
    protected static function getTableColumns(): array { return ProveedorSchema::getTableColumns(); }
    protected static function getTableActions(bool $soloLectura): array { return ProveedorSchema::getTableActions($soloLectura); }
    protected static function getInfolistSections(): array { return ProveedorSchema::getInfolistSections(); }
    protected static function infolistColumnSpanFull(): bool { return true; }
    protected static function getDefaultSortColumn(): string { return 'nombre_empresa'; }
    protected static function getSearchPlaceholder(): string { return 'Buscar por empresa, RFC, contacto o correo...'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Proveedor eliminado'; }
}
