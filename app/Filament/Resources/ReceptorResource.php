<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceptorResource\Pages;
use App\Models\Receptor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReceptorResource extends Resource
{
    protected static ?string $model = Receptor::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Receptores';
    protected static ?string $modelLabel = 'Receptor';
    protected static ?string $pluralModelLabel = 'Receptores';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Receptor')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido Paterno')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido Materno')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\Select::make('id_area')
                            ->label('Área')
                            ->relationship('area', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_paterno')
                    ->label('Apellido Paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_materno')
                    ->label('Apellido Materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.nombre')
                    ->label('Área')
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
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
