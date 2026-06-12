<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Filament\Resources\MaterialResource\RelationManagers;
use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(150),
                Forms\Components\Textarea::make('descripcion')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('modelo')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('id_unidad')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_marca')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('id_tipodematerial')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('requiere_control_individual')
                    ->required(),
                Forms\Components\TextInput::make('stock_actual')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('stock_minimo')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_unidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_marca')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('id_tipodematerial')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requiere_control_individual')
                    ->boolean(),
                Tables\Columns\TextColumn::make('stock_actual')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_minimo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListMaterials::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
