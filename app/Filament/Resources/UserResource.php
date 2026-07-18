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
    protected static bool $shouldRegisterNavigation = false;

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
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre(s)')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido Paterno')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido Materno')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Datos de Acceso')
                    ->icon('heroicon-m-lock-closed')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->prefixIcon('heroicon-m-key')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Datos Institucionales')
                    ->icon('heroicon-m-academic-cap')
                    ->schema([
                        Forms\Components\TextInput::make('num_control')
                            ->label('Número de Control')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('carrera')
                            ->label('Carrera / Área')
                            ->prefixIcon('heroicon-m-academic-cap')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('RFC')
                            ->label('RFC (Opcional)')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(13),
                    ])->columns(3),

                Forms\Components\Section::make('Estado y Rol')
                    ->icon('heroicon-m-shield-check')
                    ->schema([
                        Forms\Components\Select::make('tipo_usuario')
                            ->label('Tipo de Usuario (Sistema)')
                            ->options([
                                'Administrador' => 'Administrador',
                                'Servicio' => 'Servicio Social',
                                'Pendiente' => 'Pendiente',
                            ])
                            ->default('Pendiente')
                            ->prefixIcon('heroicon-m-shield-check')
                            ->required(),
                        Forms\Components\Select::make('roles')
                            ->label('Rol (Spatie)')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->prefixIcon('heroicon-m-key')
                            ->searchable(),
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Usuario Activo')
                            ->boolean()
                            ->inline()
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
                    ])
                    ->icons([
                        'heroicon-m-shield-check' => 'Administrador',
                        'heroicon-m-academic-cap' => 'Servicio',
                        'heroicon-m-clock' => 'Pendiente',
                    ]),
                Tables\Columns\TextColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
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
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
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

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make('Datos Personales')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name')->label('Nombre')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('apellido_paterno')->label('Apellido Paterno')->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('apellido_materno')->label('Apellido Materno')->icon('heroicon-m-user'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Datos de Acceso')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('email')->label('Correo Electrónico')->icon('heroicon-m-envelope'),
                    ])->columns(1),
                \Filament\Infolists\Components\Section::make('Datos Institucionales')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('num_control')->label('Número de Control')->icon('heroicon-m-identification'),
                        \Filament\Infolists\Components\TextEntry::make('carrera')->label('Carrera')->icon('heroicon-m-academic-cap'),
                        \Filament\Infolists\Components\TextEntry::make('RFC')->label('RFC')->fontFamily('mono')->icon('heroicon-m-identification'),
                    ])->columns(3),
                \Filament\Infolists\Components\Section::make('Estado y Rol')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('tipo_usuario')
                            ->label('Tipo de Usuario')
                            ->badge()
                            ->color(fn (string $state): string => match($state) {
                                'Administrador' => 'success',
                                'Servicio' => 'warning',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match($state) {
                                'Administrador' => 'heroicon-m-shield-check',
                                'Servicio' => 'heroicon-m-academic-cap',
                                default => 'heroicon-m-clock',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('roles.name')->label('Rol')->badge()->icon('heroicon-m-key'),
                        \Filament\Infolists\Components\TextEntry::make('activo')
                            ->label('Estado')
                            ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
