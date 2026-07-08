<?php

namespace App\Filament\ServicioSocial\Resources;

use App\Filament\ServicioSocial\Resources\ReceptorResource\Pages;
use App\Filament\ServicioSocial\Resources\ReceptorResource\RelationManagers;
use App\Models\Receptor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReceptorResource extends Resource
{
    protected static ?string $model = Receptor::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationLabel = 'Receptores';
    protected static ?string $modelLabel = 'Receptor';
    protected static ?string $pluralModelLabel = 'Receptores';

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
                Forms\Components\TextInput::make('apellido_paterno')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('apellido_materno')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('telefono')
                    ->tel()
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('id_area')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_area')
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
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
