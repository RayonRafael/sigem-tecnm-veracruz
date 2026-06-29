<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'Proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores';
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Empresa')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_empresa')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('rfc')
                            ->label('RFC')
                            ->maxLength(13)
                            ->default(null),
                        Forms\Components\Toggle::make('activo')
                            ->label('Proveedor Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('contacto_nombre')
                            ->label('Nombre del Contacto')
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->maxLength(100)
                            ->default(null),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_nombre')
                    ->label('Contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_telefono')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
