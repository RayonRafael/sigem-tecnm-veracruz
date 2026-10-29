<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReceptorResource\Pages;
use App\Models\Receptor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ReceptorResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Receptor::class;

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Receptores';

    protected static ?string $modelLabel = 'receptor';

    protected static ?string $pluralModelLabel = 'receptores';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 7;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'apellido_paterno', 'apellido_materno', 'cargo', 'email'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del receptor')
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_paterno')
                            ->label('Apellido paterno')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('apellido_materno')
                            ->label('Apellido materno')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('cargo')
                            ->label('Cargo / Puesto')
                            ->placeholder('Ej. Ing. de Sistemas')
                            ->maxLength(100)
                            ->prefixIcon('heroicon-m-briefcase'),
                        Forms\Components\TextInput::make('email')->label('Correo electrónico')
                            ->label('Correo electrónico')
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
                        Forms\Components\Select::make('id_area')->placeholder('Selecciona...')
                            ->label('Área')
                            ->relationship('area', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-m-building-office')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre del área')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Select::make('id_departamento')->placeholder('Selecciona...')
                                    ->label('Departamento')
                                    ->options(\App\Models\Departamento::pluck('nombre', 'id_departamento'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Nombre')
                    ->description(fn (Receptor $record): ?string => $record->cargo)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cargo')->wrap(false)->limit(30)->grow(false)
                    ->label('Cargo / Puesto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apellido_paterno')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Apellido paterno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('apellido_materno')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Apellido materno')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Correo electrónico')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('area.nombre')->wrap(false)->limit(30)->grow(false)
                    ->label('Área')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre', 'asc')
            ->striped()
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle'])
                    ->slideOver()
                    ->modalHeading(fn (?Receptor $record) => trim(($record?->nombre ?? '') . ' ' . ($record?->apellido_paterno ?? '') . ' ' . ($record?->apellido_materno ?? '')) ?: 'Detalle de receptor')
                    ->modalDescription(fn (?Receptor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->cargo ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Editar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->alpineClickHandler(fn (?Receptor $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_eliminar')
                            ->label('Eliminar')
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->alpineClickHandler(fn (?Receptor $record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()->iconButton()->tooltip('Editar')->successNotificationTitle('Receptor actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Receptor eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalle')
                    ->tabs([
                        Tab::make('Información')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Section::make('Datos personales')
                                    ->schema([
                                        TextEntry::make('nombre')
                                            ->label('Nombre')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_paterno')
                                            ->label('Apellido paterno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('apellido_materno')
                                            ->label('Apellido materno')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('cargo')
                                            ->label('Cargo')
                                            ->icon('heroicon-m-briefcase')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('telefono')
                                            ->label('Teléfono')
                                            ->icon('heroicon-m-phone')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('email')
                                            ->label('Correo')
                                            ->icon('heroicon-m-envelope')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make('Ubicación')
                                    ->schema([
                                        TextEntry::make('area.departamento.nombre')
                                            ->label('Departamento')
                                            ->icon('heroicon-m-building-office')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        TextEntry::make('area.nombre')
                                            ->label('Área')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
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
            'index' => Pages\ListReceptors::route('/'),
            'create' => Pages\CreateReceptor::route('/create'),
            'edit' => Pages\EditReceptor::route('/{record}/edit'),
        ];
    }
}
