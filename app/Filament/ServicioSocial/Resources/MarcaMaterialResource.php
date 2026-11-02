<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\MarcaMaterialResource\Pages;
use App\Models\MarcaMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MarcaMaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = MarcaMaterial::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?string $navigationLabel = 'Marcas de material';

    protected static ?string $modelLabel = 'marca';

    protected static ?string $pluralModelLabel = 'marcas';

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
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la marca')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre de la marca')
                            ->required()
                            ->prefixIcon('heroicon-m-tag')
                            ->maxLength(100),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->searchPlaceholder('Buscar por nombre de la marca...')
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
        return \App\Filament\Resources\MarcaMaterialResource::infolist($infolist);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarcaMaterials::route('/'),
            'create' => Pages\CreateMarcaMaterial::route('/create'),
            'edit' => Pages\EditMarcaMaterial::route('/{record}/edit'),
        ];
    }
}
