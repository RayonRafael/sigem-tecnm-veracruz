<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ConCatalogoResource;
use App\Filament\Resources\ReceptorResource\Schemas\ReceptorSchema;
use App\Models\Receptor;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class ReceptorResource extends Resource
{
    use ConCatalogoResource;

    protected static ?string $model = Receptor::class;
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Receptores';
    protected static ?string $modelLabel = 'receptor';
    protected static ?string $pluralModelLabel = 'receptores';
    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['area.departamento']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'apellido_paterno', 'apellido_materno', 'tipo', 'num_control', 'cargo', 'email'];
    }

    public static function getTiposOptions(): array
    {
        return [
            'Docente' => 'Docente',
            'Administrativo' => 'Administrativo',
            'Auxiliar' => 'Auxiliar',
            'Alumno' => 'Alumno',
            'Externo' => 'Externo',
        ];
    }

    protected static function getFormSchema(): array { return ReceptorSchema::getFormSchema(); }
    protected static function getTableColumns(): array { return ReceptorSchema::getTableColumns(); }
    protected static function getTableActions(bool $soloLectura): array { return ReceptorSchema::getTableActions($soloLectura); }
    protected static function getInfolistSections(): array { return ReceptorSchema::getInfolistSections(); }
    protected static function infolistColumnSpanFull(): bool { return true; }
    protected static function getSearchPlaceholder(): string { return 'Buscar por nombre, cargo, área o correo...'; }
    protected static function getDeleteBulkSuccessMessage(): string { return 'Receptor eliminado'; }
}
