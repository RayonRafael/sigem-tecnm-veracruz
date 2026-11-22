<?php

namespace App\Filament\Concerns;

use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use Filament\Forms\Form;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;

trait ConCatalogoResource
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogos';
    }

    protected static function esSoloLectura(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return CatalogoBaseFormSchema::build($form, static::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        $soloLectura = static::esSoloLectura();

        return CatalogoBaseTableSchema::build(
            table: $table,
            columns: static::getTableColumns(),
            defaultSort: static::getDefaultSortColumn(),
            searchPlaceholder: static::getSearchPlaceholder(),
            usaTrashedFilter: static::usaTrashedFilter(),
            striped: static::usaStriped(),
            usaPaginacionDefault: static::usaPaginacionDefault(),
            actions: static::getTableActions($soloLectura),
            deleteBulkSuccessMessage: static::getDeleteBulkSuccessMessage(),
            soloLectura: $soloLectura,
        );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $tabs = Tabs::make('Detalle')->tabs(static::getInfolistTabs());

        if (static::infolistColumnSpanFull()) {
            $tabs->columnSpanFull();
        }

        return $infolist->schema([$tabs]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        $base = preg_replace('/Resource$/', '', class_basename(static::class));
        $ns = static::class . '\\Pages\\';

        return [
            'index' => ($ns . "List{$base}s")::route('/'),
            'create' => ($ns . "Create{$base}")::route('/create'),
            'edit' => ($ns . "Edit{$base}")::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    protected static function getFormSchema(): array
    {
        return [];
    }

    /**
     * @return array<int, \Filament\Tables\Columns\Column>
     */
    protected static function getTableColumns(): array
    {
        return [];
    }

    /**
     * @return array<int, Tab>
     */
    protected static function getInfolistTabs(): array
    {
        return [
            Tab::make('Información')
                ->icon('heroicon-m-tag')
                ->schema(static::getInfolistSections()),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    protected static function getInfolistSections(): array
    {
        return [];
    }

    protected static function infolistColumnSpanFull(): bool
    {
        return false;
    }

    protected static function getDefaultSortColumn(): string
    {
        return 'nombre';
    }

    protected static function getSearchPlaceholder(): string
    {
        return 'Buscar...';
    }

    protected static function usaTrashedFilter(): bool
    {
        return true;
    }

    protected static function usaStriped(): bool
    {
        return true;
    }

    protected static function usaPaginacionDefault(): bool
    {
        return true;
    }

    protected static function getEditSuccessMessage(): string
    {
        return 'Registro actualizado correctamente';
    }

    protected static function getDeleteBulkSuccessMessage(): string
    {
        return 'Registro eliminado';
    }

    /**
     * @return array<int, \Filament\Tables\Actions\Action>
     */
    protected static function getTableActions(bool $soloLectura): array
    {
        return CatalogoBaseTableSchema::accionesEstandar(static::getEditSuccessMessage(), $soloLectura);
    }
}
