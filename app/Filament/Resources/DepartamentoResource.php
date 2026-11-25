<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\Departamento;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;

class DepartamentoResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = Departamento::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Departamentos';
    protected static ?string $modelLabel = 'departamento';
    protected static ?string $pluralModelLabel = 'departamentos';
    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array { return ['nombre', 'encargado']; }

    protected static function getFormSchema(): array
    {
        return [CatalogoBaseFormSchema::section('📁 Datos del departamento', 'heroicon-m-building-office-2', [
            CatalogoBaseFormSchema::campoNombre('Nombre', 'Ej. Departamento de Ciencias Básicas', uniqueModel: Departamento::class, uniqueLabel: 'un departamento'),
            Forms\Components\TextInput::make('encargado')->label('Jefe del Departamento')->placeholder('Ej. Dr. Juan Pérez')->helperText('Jefe o responsable del departamento')->nullable()->prefixIcon('heroicon-m-user')->maxLength(100),
        ])];
    }

    protected static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true, sortable: true),
            CatalogoBaseTableSchema::columnaTexto('encargado', 'Jefe del Departamento', individualSearch: true, sortable: true)->placeholder('—'),
        ];
    }

    protected static function getInfolistSections(): array
    {
        return [Section::make('📁 Datos del departamento')->schema([
            TextEntry::make('nombre')->label('Nombre del departamento')->icon('heroicon-m-building-office-2'),
            TextEntry::make('encargado')->label('Jefe del Departamento')->placeholder('—')->icon('heroicon-m-user'),
            TextEntry::make('areas_count')->label('Áreas adscritas')->state(fn (Departamento $record): string => $record->areas()->count() . ' registradas')->icon('heroicon-m-map-pin'),
        ])->columns(2)];
    }

    protected static function usaTrashedFilter(): bool { return false; }
    protected static function usaStriped(): bool { return false; }
    protected static function usaPaginacionDefault(): bool { return false; }
    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre del departamento...'; }
    protected static function getEditSuccessMessage(): string { return 'Departamento actualizado correctamente'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Departamento eliminado'; }
}
