<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarcaMaterialResource\Pages;
use App\Models\MarcaMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MarcaMaterialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = MarcaMaterial::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Marcas';

    protected static ?string $modelLabel = 'marca';

    protected static ?string $pluralModelLabel = 'marcas';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 3;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre'];
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
                            ->placeholder('Ej. Dell, HP, Cisco')
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->tooltip('Ver detalle')->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Editar')->successNotificationTitle('Marca actualizada correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Marca eliminada'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->tabs([
                        Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('Datos de la marca')
                                    ->schema([
                                        TextEntry::make('nombre')->label('Nombre de la marca')->icon('heroicon-m-tag'),
                                        TextEntry::make('materiales_count')
                                            ->label('Materiales asociados')
                                            ->state(fn (MarcaMaterial $record): string => $record->materiales()->count() . ' registrados')
                                            ->icon('heroicon-m-cube'),
                                    ])->columns(2),
                            ]),
                    ]),
            ]);
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
