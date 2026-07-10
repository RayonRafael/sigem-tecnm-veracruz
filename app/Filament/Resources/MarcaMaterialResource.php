<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarcaMaterialResource\Pages;
use App\Models\MarcaMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MarcaMaterialResource extends Resource
{
    protected static ?string $model = MarcaMaterial::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Marcas';
    protected static ?string $modelLabel = 'Marca';
    protected static ?string $pluralModelLabel = 'Marcas';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Marca')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre de la Marca')
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
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Datos de la Marca')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-tag'),
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
