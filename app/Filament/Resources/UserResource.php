<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos Personales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre(s)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido Paterno')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido Materno')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Datos de Acceso')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Datos Institucionales')
                    ->schema([
                        Forms\Components\TextInput::make('num_control')
                            ->label('Número de Control')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('carrera')
                            ->label('Carrera / Área')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('RFC')
                            ->label('RFC (Opcional)')
                            ->maxLength(13),
                    ])->columns(3),

                Forms\Components\Section::make('Estado y Rol')
                    ->schema([
                        Forms\Components\Select::make('tipo_usuario')
                            ->label('Tipo de Usuario (Sistema)')
                            ->options([
                                'Administrador' => 'Administrador',
                                'Servicio' => 'Servicio Social',
                                'Pendiente' => 'Pendiente',
                            ])
                            ->default('Pendiente')
                            ->required(),
                        Forms\Components\Select::make('roles')
                            ->label('Rol (Spatie)')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable(),
                        Forms\Components\Toggle::make('activo')
                            ->label('Usuario Activo')
                            ->default(true)
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('num_control')
                    ->label('No. Control')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_usuario')
                    ->label('Tipo')
                    ->badge()
                    ->colors([
                        'success' => 'Administrador',
                        'warning' => 'Servicio',
                        'gray' => 'Pendiente',
                    ]),
                Tables\Columns\IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_usuario')
                    ->options([
                        'Administrador' => 'Administrador',
                        'Servicio' => 'Servicio Social',
                        'Pendiente' => 'Pendiente',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->hidden(fn (User $record): bool => $record->email === 'admin@tecnm.edu.mx'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->filter(fn (User $record) => $record->email !== 'admin@tecnm.edu.mx')->each->delete();
                        }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
