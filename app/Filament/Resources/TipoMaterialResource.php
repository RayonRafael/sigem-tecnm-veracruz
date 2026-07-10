<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoMaterialResource\Pages;
use App\Models\TipoMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TipoMaterialResource extends Resource
{
    protected static ?string $model = TipoMaterial::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Tipos de material';
    protected static ?string $modelLabel = 'Tipo de material';
    protected static ?string $pluralModelLabel = 'Tipos de material';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Tipo de Material')
                    ->icon('heroicon-m-rectangle-stack')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre del Tipo')
                            ->required()
                            ->prefixIcon('heroicon-m-rectangle-stack')
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
                \Filament\Infolists\Components\Section::make('Datos del Tipo de Material')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-rectangle-stack'),
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
            'index' => Pages\ListTipoMaterials::route('/'),
            'create' => Pages\CreateTipoMaterial::route('/create'),
            'edit' => Pages\EditTipoMaterial::route('/{record}/edit'),
        ];
    }
}
