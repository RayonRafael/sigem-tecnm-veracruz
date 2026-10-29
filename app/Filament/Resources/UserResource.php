<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'apellido_paterno', 'apellido_materno', 'email', 'num_control'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Datos personales y acceso')
                        ->icon('heroicon-m-user-circle')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nombre(s)')
                                ->placeholder('Ej. Juan Carlos')
                                ->required()
                                ->prefixIcon('heroicon-m-user')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('apellido_paterno')
                                ->label('Apellido paterno')
                                ->placeholder('Ej. Pérez')
                                ->prefixIcon('heroicon-m-user')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('apellido_materno')
                                ->label('Apellido materno')
                                ->placeholder('Ej. López')
                                ->prefixIcon('heroicon-m-user')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->label('Correo electrónico')
                                ->placeholder('Ej. usuario@tecnm.edu.mx')
                                ->email()
                                ->required()
                                ->prefixIcon('heroicon-m-envelope')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('password')
                                ->label('Contraseña')
                                ->placeholder('••••••••')
                                ->password()
                                ->prefixIcon('heroicon-m-key')
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->dehydrated(fn (?string $state): bool => filled($state))
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Datos institucionales y rol')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Forms\Components\TextInput::make('num_control')
                                ->label('Número de control')
                                ->placeholder('Ej. 21020123')
                                ->prefixIcon('heroicon-m-identification')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('carrera')
                                ->label('Carrera / área')
                                ->placeholder('Ej. Ingeniería en Sistemas Computacionales')
                                ->prefixIcon('heroicon-m-academic-cap')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('RFC')
                                ->label('Rfc (opcional)')
                                ->placeholder('Ej. PELJ900101XXX')
                                ->prefixIcon('heroicon-m-identification')
                                ->maxLength(13),
                            Forms\Components\Select::make('roles')
                                ->placeholder('Selecciona...')
                                ->label('Rol (Spatie)')
                                ->relationship('roles', 'name')
                                ->preload()
                                ->prefixIcon('heroicon-m-key')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                    $set('tipo_usuario', static::mapRoleToTipoUsuario($state));
                                }),
                            Forms\Components\Hidden::make('tipo_usuario')
                                ->default('Pendiente')
                                ->dehydrated(),
                            Forms\Components\ToggleButtons::make('activo')
                                ->label('Usuario activo')
                                ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                                ->inline()
                                ->default(true)
                                ->required()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->wrap(false)->limit(30)->grow(false)->label('Nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Correo electrónico')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('num_control')->wrap(false)->limit(30)->grow(false)
                    ->label('No. control')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_usuario')->wrap(false)->limit(30)->grow(false)
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
                Tables\Columns\TextColumn::make('activo')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_usuario')
                    ->options([
                        RoleEnum::ADMIN->value => RoleEnum::ADMIN->value,
                        RoleEnum::SERVICIO_TIPO->value => RoleEnum::SERVICIO_SOCIAL->value,
                        'Pendiente' => 'Pendiente',
                    ]),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?User $record) => $record?->name ?? 'Detalle de usuario')
                    ->modalDescription(fn (?User $record) => new \Illuminate\Support\HtmlString(
                        e($record?->email ?? '—') . ' · ' . e($record?->getRoleNames()->first() ?? $record?->tipo_usuario ?? 'Sin rol')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Editar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->alpineClickHandler(fn (?User $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_eliminar')
                            ->label('Eliminar')
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->hidden(fn (?User $record): bool => ! $record || $record->email === 'admin@tecnm.edu.mx')
                            ->alpineClickHandler(fn (?User $record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?User $record) => 'Editar: ' . ($record?->name ?? 'usuario'))
                    ->modalDescription(fn (?User $record) => new \Illuminate\Support\HtmlString(
                        e($record?->email ?? '—') . ' · ' . e($record?->getRoleNames()->first() ?? 'Sin rol')
                    ))
                    ->successNotificationTitle('Usuario actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->requiresConfirmation()->successNotificationTitle('Usuario eliminado')
                    ->modalHeading('Eliminar usuario')
                    ->modalDescription('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')
                    ->modalSubmitActionLabel('Sí, eliminar')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->tooltip('Eliminar')
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
                Tabs::make('Detalle')
                    ->tabs([
                        Tabs\Tab::make('Perfil')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Section::make('Datos personales')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Nombre')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('email')
                                            ->label('Correo')
                                            ->icon('heroicon-m-envelope')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_paterno')
                                            ->label('Apellido paterno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_materno')
                                            ->label('Apellido materno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Institucional y Roles')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                Section::make('Datos institucionales')
                                    ->schema([
                                        TextEntry::make('num_control')
                                            ->label('Número de control')
                                            ->icon('heroicon-m-identification')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('carrera')
                                            ->label('Carrera')
                                            ->icon('heroicon-m-academic-cap')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('RFC')
                                            ->label('RFC')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-identification')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Section::make('Rol y permisos')
                                    ->schema([
                                        TextEntry::make('roles.name')
                                            ->label('Rol')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Administrador', 'Admin' => 'warning',
                                                'Servicio Social', 'Servicio' => 'info',
                                                default => 'gray',
                                            })
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('tipo_usuario')
                                            ->label('Tipo de usuario')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? '● ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                RoleEnum::ADMIN->value => 'success',
                                                RoleEnum::SERVICIO_TIPO->value => 'warning',
                                                default => 'gray',
                                            })
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('activo')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => $state ? '● Activo' : '● Inactivo')
                                            ->color(fn ($state): string => $state ? 'success' : 'danger')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('created_at')
                                            ->label('Registro')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
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

    public static function mapRoleToTipoUsuario(?string $roleNameOrId): string
    {
        if (! $roleNameOrId) {
            return 'Pendiente';
        }

        $roleName = is_numeric($roleNameOrId)
            ? Role::find($roleNameOrId)?->name
            : $roleNameOrId;

        return match ($roleName) {
            'Administrador', 'Admin' => RoleEnum::ADMIN->value,
            'Servicio Social', 'Servicio' => RoleEnum::SERVICIO_TIPO->value,
            default => 'Pendiente',
        };
    }
}
