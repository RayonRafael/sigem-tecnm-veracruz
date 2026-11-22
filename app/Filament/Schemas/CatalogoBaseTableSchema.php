<?php

namespace App\Filament\Schemas;

use Closure;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CatalogoBaseTableSchema
{
    public static function columnaTexto(
        string $name,
        string $label,
        bool $individualSearch = false,
        bool $sortable = false,
        int $limit = 30,
        bool $hiddenByDefault = false,
    ): Tables\Columns\TextColumn {
        $column = Tables\Columns\TextColumn::make($name)
            ->wrap(false)
            ->limit($limit)
            ->grow(false)
            ->label($label);

        if ($hiddenByDefault) {
            $column->toggleable(isToggledHiddenByDefault: true);
        }

        if ($individualSearch) {
            $column->searchable(isIndividual: true);
        } else {
            $column->searchable();
        }

        if ($sortable) {
            $column->sortable();
        }

        return $column;
    }

    public static function columnaFechaCreacion(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->wrap(false)
            ->limit(30)
            ->grow(false)
            ->label('Fecha de creación')
            ->dateTime('d/m/Y')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * @return array<int, Tables\Actions\Action>
     */
    public static function accionesEstandar(string $editSuccessMessage, bool $soloLectura = false): array
    {
        $edit = Tables\Actions\EditAction::make()
            ->iconButton()
            ->tooltip('Editar');

        if ($soloLectura) {
            $edit->slideOver();
        } else {
            $edit->successNotificationTitle($editSuccessMessage);
        }

        return [
            Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
            $edit,
            Tables\Actions\DeleteAction::make()
                ->iconButton()
                ->tooltip('Eliminar')
                ->extraAttributes(['title' => 'Eliminar'])
                ->successNotificationTitle('Registro eliminado correctamente'),
        ];
    }

    /**
     * @return array<int, Tables\Actions\Action>
     */
    public static function accionesConModalDetalle(
        Closure $viewHeading,
        Closure $editHeading,
        Closure $description,
        string $editSuccessMessage,
        bool $soloLectura = false,
    ): array {
        $view = Tables\Actions\ViewAction::make()
            ->iconButton()
            ->tooltip('Ver detalle')
            ->extraAttributes(['title' => 'Ver detalle'])
            ->slideOver()
            ->modalHeading($viewHeading)
            ->modalDescription($description)
            ->modalCancelActionLabel('Cerrar');

        if (! $soloLectura) {
            $view->extraModalFooterActions([
                Tables\Actions\Action::make('desde_detalle_editar')
                    ->label('Editar')
                    ->color('warning')
                    ->icon('heroicon-m-pencil-square')
                    ->alpineClickHandler(fn ($record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                Tables\Actions\Action::make('desde_detalle_eliminar')
                    ->label('Eliminar')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->alpineClickHandler(fn ($record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
            ]);
        }

        $edit = Tables\Actions\EditAction::make()
            ->iconButton()
            ->tooltip('Editar')
            ->extraAttributes(['title' => 'Editar'])
            ->modalHeading($editHeading)
            ->modalDescription($description)
            ->successNotificationTitle($editSuccessMessage);

        if ($soloLectura) {
            $edit->slideOver();
        }

        return [
            $view,
            $edit,
            Tables\Actions\DeleteAction::make()
                ->iconButton()
                ->tooltip('Eliminar')
                ->extraAttributes(['title' => 'Eliminar'])
                ->successNotificationTitle('Registro eliminado correctamente'),
        ];
    }

    /**
     * @param  array<int, \Filament\Tables\Columns\Column>  $columns
     * @param  array<int, Tables\Actions\Action>  $actions
     */
    public static function build(
        Table $table,
        array $columns,
        string $defaultSort,
        string $searchPlaceholder,
        bool $usaTrashedFilter,
        bool $striped,
        bool $usaPaginacionDefault,
        array $actions,
        string $deleteBulkSuccessMessage,
        bool $soloLectura = false,
    ): Table {
        $table = $table
            ->columns($columns)
            ->defaultSort($defaultSort, 'asc');

        if ($striped) {
            $table->striped();
        }

        $table->searchPlaceholder($searchPlaceholder);

        if ($usaPaginacionDefault) {
            $table->defaultPaginationPageOption(10);
        }

        if ($usaTrashedFilter) {
            $table->filters([TrashedFilter::make()]);
        }

        $bulkActions = $soloLectura
            ? []
            : [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle($deleteBulkSuccessMessage),
                ]),
            ];

        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->actions($actions)
            ->bulkActions($bulkActions);
    }
}
