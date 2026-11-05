<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MaterialResource\Pages;
use App\Models\Material;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Material::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Materiales';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiales';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return \App\Filament\Resources\MaterialResource::getEloquentQuery();
    }

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\MaterialResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('modelo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('marca.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_actual')->wrap(false)->limit(30)->grow(false)
                    ->label('Stock actual')
                    ->numeric()
                    // Se ordena por el stock derivado, no por la columna fisica obsoleta.
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw(\App\Models\Material::stockDerivadoSql().' '.$direction)),
                Tables\Columns\TextColumn::make('stock_minimo')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Stock mín.')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado de stock')
                    ->getStateUsing(fn ($record) => $record->stock_actual < $record->stock_minimo ? 'Stock bajo' : 'Normal')
                    ->badge()
                    ->colors([
                        'danger' => 'Stock bajo',
                        'success' => 'Normal',
                    ])
                    ->icons([
                        'heroicon-m-exclamation-triangle' => 'Stock bajo',
                        'heroicon-m-check-circle' => 'Normal',
                    ]),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->searchPlaceholder('Buscar por nombre, modelo, marca o tipo...')
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->slideOver()
                    ->modalHeading(fn (?Material $record) => 'Editar: ' . ($record?->nombre ?? 'material'))
                    ->modalDescription(fn (?Material $record) => new \Illuminate\Support\HtmlString(
                        e($record?->tipo?->nombre ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->successNotificationTitle('Material actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\MaterialResource::infolist($infolist);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
