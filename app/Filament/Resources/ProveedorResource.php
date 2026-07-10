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
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_empresa')
                            ->label('Nombre de la Empresa')
                            ->required()
                            ->prefixIcon('heroicon-m-building-storefront')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('rfc')
                            ->label('RFC')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(13)
                            ->default(null),
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Proveedor Activo')
                            ->boolean()
                            ->inline()
                            ->default(true)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Información de Contacto')
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Forms\Components\TextInput::make('contacto_nombre')
                            ->label('Nombre del Contacto')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->prefixIcon('heroicon-m-device-phone-mobile')
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->prefixIcon('heroicon-m-envelope')
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
                Tables\Columns\TextColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre_empresa', 'asc')
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
                \Filament\Infolists\Components\Section::make('Datos de la Empresa')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nombre_empresa')->label('Empresa')->icon('heroicon-m-building-storefront'),
                        \Filament\Infolists\Components\TextEntry::make('rfc')->label('RFC')->fontFamily('mono')->icon('heroicon-m-identification'),
                        \Filament\Infolists\Components\TextEntry::make('activo')
                            ->label('Estado')
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Contacto')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('contacto_nombre')->label('Contacto')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('contacto_telefono')->label('Teléfono')->icon('heroicon-m-device-phone-mobile'),
                        \Filament\Infolists\Components\TextEntry::make('contacto_email')->label('Email')->icon('heroicon-m-envelope'),
                    ])->columns(3),
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
