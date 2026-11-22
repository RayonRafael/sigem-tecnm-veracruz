<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MantenimientoResource\Actions\MantenimientoAcciones;
use App\Filament\Resources\MantenimientoResource\Pages;
use App\Filament\Resources\MantenimientoResource\Schemas\MantenimientoFormSchema;
use App\Filament\Resources\MantenimientoResource\Schemas\MantenimientoInfolistSchema;
use App\Filament\Resources\MantenimientoResource\Schemas\MantenimientoTableSchema;
use App\Models\Mantenimiento;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
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

    protected static ?int $navigationSort = 3;

    protected static function esPanelServicioSocial(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inventario.material']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['descripcion_falla', 'descripcion_trabajo', 'nombre_tecnico', 'observaciones'];
    }

    public static function form(Form $form): Form
    {
        return MantenimientoFormSchema::getSchema($form);
    }

    public static function table(Table $table): Table
    {
        return MantenimientoTableSchema::getTable($table, ! static::esPanelServicioSocial());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return MantenimientoInfolistSchema::getInfolist($infolist);
    }

    public static function getCompletarFormSchema(): array
    {
        return MantenimientoFormSchema::getCompletarFormSchema();
    }

    public static function completarMantenimiento(Mantenimiento $record, array $data = []): void
    {
        MantenimientoAcciones::completarMantenimiento($record, $data);
    }

    public static function puedeDescargarOrdenTrabajo(?Mantenimiento $record): bool
    {
        return MantenimientoAcciones::puedeDescargarOrdenTrabajo($record);
    }

    public static function puedeDescargarListaVerificacion(?Mantenimiento $record): bool
    {
        return MantenimientoAcciones::puedeDescargarListaVerificacion($record);
    }

    /**
     * Construye las acciones de descarga PDF (Formato 2 y Formato 3) para filas o slide-over.
     *
     * @return array<int, \Filament\Tables\Actions\Action>
     */
    public static function getAccionesPdfMantenimiento(bool $enSlideOver = false): array
    {
        return MantenimientoAcciones::getAccionesPdfMantenimiento($enSlideOver);
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
}
