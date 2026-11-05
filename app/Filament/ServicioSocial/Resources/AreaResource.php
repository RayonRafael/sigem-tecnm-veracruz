<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\AreaResource\Pages;
use App\Models\Area;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AreaResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Area::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Áreas';

    protected static ?string $modelLabel = 'área';

    protected static ?string $pluralModelLabel = 'áreas';

    public static function getEloquentQuery(): Builder
    {
        return \App\Filament\Resources\AreaResource::getEloquentQuery();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function hasRecordTitle(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return \App\Filament\Resources\AreaResource::form($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('departamento.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Departamento')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->searchPlaceholder('Buscar por nombre o departamento...')
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Editar')->slideOver(),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\AreaResource::infolist($infolist);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
