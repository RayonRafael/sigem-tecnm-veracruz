<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Concerns\ConModalesBajaCambio;
use App\Filament\Resources\InventarioResource\Actions\EditarEquipoAction;
use App\Filament\Resources\InventarioResource\Actions\RegistrarMultiplesEquipos;
use App\Filament\Resources\InventarioResource\Pages;
use App\Filament\Resources\InventarioResource\Schemas\ComponentCardsSchema;
use App\Filament\Resources\InventarioResource\Schemas\CreateFormSchema;
use App\Filament\Resources\InventarioResource\Schemas\EditFormTabs;
use App\Filament\Resources\InventarioResource\Schemas\InfolistSchema;
use App\Filament\Resources\InventarioResource\Schemas\InventarioFormFields;
use App\Filament\Resources\InventarioResource\Schemas\InventarioTableSchema;
use App\Models\Inventario;
use App\Services\InventarioDashboardQuery;
use App\Support\SigemExcelExporter;
use App\Support\UbicacionHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventarioResource extends Resource
{
    use ConModalesBajaCambio;
    use InventarioFormFields;

    protected static ?string $model = Inventario::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'registro de inventario';

    protected static ?string $pluralModelLabel = 'registros de inventario';

    protected static ?string $navigationGroup = 'Gestión de inventario';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['material.tipo', 'material.marca', 'departamento', 'area.departamento', 'salon']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['num_serie', 'num_factura'];
    }

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'create' || ($form->getLivewire() instanceof CreateRecord)) {
            return $form->schema(static::getMockupFormSchema());
        }

        return $form->schema(static::getEditFormTabs());
    }

    public static function getMockupFormSchema(): array
    {
        return CreateFormSchema::make();
    }

    protected static function getComponentCardsSchema(): array
    {
        return ComponentCardsSchema::make();
    }

    public static function getEditFormTabs(): array
    {
        return EditFormTabs::make();
    }

    public static function actualizarUbicacionDescriptiva(Forms\Set $set, Forms\Get $get): void
    {
        UbicacionHelper::actualizarDescriptiva($set, $get);
    }

    public static function registrarMultiplesEquipos(array $data): Inventario
    {
        return RegistrarMultiplesEquipos::ejecutar($data);
    }

    public static function obtenerMensajeExito(?Inventario $record): string
    {
        if (! $record) {
            return 'Equipo registrado exitosamente';
        }

        if ($record->tipo_propiedad === 'Rentado' && $record->renta_fecha_fin) {
            return 'Equipo registrado como rentado. Vence el ' . $record->renta_fecha_fin->format('d/m/Y');
        }

        $total = $record->especificaciones['_total_registrados'] ?? 1;
        $estacion = $record->estacion;

        if ($total > 1 && $estacion) {
            return "Se registraron {$total} equipos como estación {$estacion}";
        }

        return 'Equipo registrado exitosamente';
    }

    public static function obtenerTipoEquipoNormalizado(?Inventario $record = null, ?Forms\Get $get = null): string
    {
        return EditarEquipoAction::obtenerTipoEquipoNormalizado($record, $get);
    }

    public static function hidratarDatosEdicion(array $data, ?Inventario $record = null): array
    {
        return EditarEquipoAction::hidratarDatosEdicion($data, $record);
    }

    public static function guardarCambiosEquipo(Inventario $record, array $data): Inventario
    {
        return EditarEquipoAction::guardarCambiosEquipo($record, $data);
    }

    public static function getTableColumns(): array
    {
        return InventarioTableSchema::getColumns();
    }

    public static function aplicarFiltrosTabla(Builder $query, $livewire): Builder
    {
        return InventarioTableSchema::aplicarFiltros($query, $livewire);
    }

    public static function esAdmin(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrador')
            || $user->hasRole('admin')
            || in_array($user->tipo_usuario, ['Administrador', 'Admin'], true)
            || ($user->can(RoleEnum::PERM_ACCESS_ADMIN) ?? false);
    }

    public static function table(Table $table): Table
    {
        return InventarioTableSchema::getTable($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InfolistSchema::make($infolist);
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

    public static function obtenerOpcionesFiltrosDashboard(): array
    {
        return InventarioDashboardQuery::obtenerOpcionesFiltros();
    }

    public static function obtenerInventariosDashboardQuery(
        array $searches = [],
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        return InventarioDashboardQuery::obtenerQuery($searches, $sortColumn, $sortDirection);
    }

    public static function obtenerInventariosDashboard(array $searches): Collection
    {
        return InventarioDashboardQuery::obtenerInventarios($searches);
    }

    public static function obtenerCategoriasTop(): array
    {
        return InventarioDashboardQuery::obtenerCategoriasTop();
    }

    public static function exportarInventarioExcel($livewire = null): ?BinaryFileResponse
    {
        return SigemExcelExporter::exportarInventario($livewire);
    }
}
