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
                Forms\Components\Section::make('Datos del Receptor')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido Paterno')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido Materno')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel()
                            ->prefixIcon('heroicon-m-device-phone-mobile')
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\Select::make('id_area')
                            ->label('Área')
                            ->relationship('area', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-m-building-office'),
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
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Datos del Receptor')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('apellido_paterno')->label('Apellido Paterno')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('apellido_materno')->label('Apellido Materno')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('email')->label('Email')->icon('heroicon-m-envelope'),
                        \Filament\Infolists\Components\TextEntry::make('telefono')->label('Teléfono')->icon('heroicon-m-device-phone-mobile'),
                        \Filament\Infolists\Components\TextEntry::make('area.nombre')->label('Área')->icon('heroicon-m-building-office'),
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
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
