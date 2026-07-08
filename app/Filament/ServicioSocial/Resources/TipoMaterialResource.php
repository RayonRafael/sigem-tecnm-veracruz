<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\TipoMaterialResource\Pages;
use App\Filament\ServicioSocial\Resources\TipoMaterialResource\RelationManagers;
use App\Models\TipoMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TipoMaterialResource extends Resource
{
    protected static ?string $model = TipoMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?string $navigationLabel = 'Tipos de Material';
    protected static ?string $modelLabel = 'Tipo de Material';
    protected static ?string $pluralModelLabel = 'Tipos de Material';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
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
