<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

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
                        Forms\Components\TextInput::make('name')->label('Nombre')
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
                        Forms\Components\TextInput::make('email')->label('Correo electrónico')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')->label('Contraseña')
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
                        Forms\Components\Select::make('tipo_usuario')->placeholder('Selecciona...')
                            ->label('Tipo de Usuario (Sistema)')
                            ->options([
                                RoleEnum::ADMIN->value => RoleEnum::ADMIN->value,
                                RoleEnum::SERVICIO_TIPO->value => RoleEnum::SERVICIO_SOCIAL->value,
                                'Pendiente' => 'Pendiente',
                            ])
                            ->default('Pendiente')
                            ->prefixIcon('heroicon-m-shield-check')
                            ->required(),
                        Forms\Components\Select::make('roles')->placeholder('Selecciona...')
                            ->label('Rol (Spatie)')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->prefixIcon('heroicon-m-key')
                            ->searchable(),
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Usuario Activo')
                            ->boolean(trueLabel: 'Sí', falseLabel: 'No')
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
                Tables\Columns\TextColumn::make('name')->label('Nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Correo electrónico')
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
                        'success' => RoleEnum::ADMIN->value,
                        'warning' => RoleEnum::SERVICIO_TIPO->value,
                        'gray' => 'Pendiente',
                    ])
                    ->icons([
                        'heroicon-m-shield-check' => RoleEnum::ADMIN->value,
                        'heroicon-m-academic-cap' => RoleEnum::SERVICIO_TIPO->value,
                        'heroicon-m-clock' => 'Pendiente',
                    ]),
                Tables\Columns\TextColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')->label('Fecha de creación')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_usuario')
                    ->options([
                        RoleEnum::ADMIN->value => RoleEnum::ADMIN->value,
                        RoleEnum::SERVICIO_TIPO->value => RoleEnum::SERVICIO_SOCIAL->value,
                        'Pendiente' => 'Pendiente',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton()->slideOver(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle('Usuario actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->requiresConfirmation()->successNotificationTitle('Usuario eliminado')
                    ->modalHeading('Eliminar usuario')
                    ->modalDescription('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->iconButton()
                    ->hidden(fn (User $record): bool => $record->email === 'admin@tecnm.edu.mx'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->filter(fn ($record) => $record->email !== 'admin@tecnm.edu.mx')->each->delete();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos Personales')
                    ->schema([
                        TextEntry::make('name')->label('Nombre')->icon('heroicon-m-user'),
                        TextEntry::make('apellido_paterno')->label('Apellido Paterno')->icon('heroicon-m-user'),
                        TextEntry::make('apellido_materno')->label('Apellido Materno')->icon('heroicon-m-user'),
                    ])->columns(3),
                Section::make('Datos de Acceso')
                    ->schema([
                        TextEntry::make('email')->label('Correo Electrónico')->icon('heroicon-m-envelope'),
                    ])->columns(1),
                Section::make('Datos Institucionales')
                    ->schema([
                        TextEntry::make('num_control')->label('Número de Control')->icon('heroicon-m-identification'),
                        TextEntry::make('carrera')->label('Carrera')->icon('heroicon-m-academic-cap'),
                        TextEntry::make('RFC')->label('RFC')->fontFamily('mono')->icon('heroicon-m-identification'),
                    ])->columns(3),
                Section::make('Estado y Rol')
                    ->schema([
                        TextEntry::make('tipo_usuario')
                            ->label('Tipo de Usuario')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                RoleEnum::ADMIN->value => 'success',
                                RoleEnum::SERVICIO_TIPO->value => 'warning',
                                default => 'gray',
                            })
                            ->icon(fn (string $state): string => match ($state) {
                                RoleEnum::ADMIN->value => 'heroicon-m-shield-check',
                                RoleEnum::SERVICIO_TIPO->value => 'heroicon-m-academic-cap',
                                default => 'heroicon-m-clock',
                            }),
                        TextEntry::make('roles.name')->label('Rol')->badge()->icon('heroicon-m-key'),
                        TextEntry::make('activo')
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
