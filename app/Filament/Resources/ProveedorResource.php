<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProveedorResource\Pages;
use App\Models\Proveedor;
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

class ProveedorResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Proveedor::class;

    protected static ?string $recordTitleAttribute = 'nombre_empresa';

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'proveedor';

    protected static ?string $pluralModelLabel = 'proveedores';

    protected static ?string $navigationGroup = 'Catálogos';

    protected static ?int $navigationSort = 6;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre_empresa', 'rfc', 'contacto_nombre'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la empresa')
                    ->icon('heroicon-m-building-office-2')
                    ->schema([
                        Forms\Components\TextInput::make('nombre_empresa')
                            ->label('Nombre de la empresa')
                            ->placeholder('Ej. Soluciones Tecnológicas S.A. de C.V.')
                            ->required()
                            ->prefixIcon('heroicon-m-building-storefront')
                            ->maxLength(150),
                        Forms\Components\TextInput::make('rfc')
                            ->label('Rfc')
                            ->placeholder('Ej. STE200101AAA')
                            ->prefixIcon('heroicon-m-identification')
                            ->maxLength(13)
                            ->default(null),
                        Forms\Components\ToggleButtons::make('activo')
                            ->label('Proveedor activo')
                            ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                            ->inline()
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Información de contacto')
                    ->icon('heroicon-m-phone')
                    ->schema([
                        Forms\Components\TextInput::make('contacto_nombre')
                            ->label('Nombre del contacto')
                            ->placeholder('Ej. Lic. María Gómez')
                            ->prefixIcon('heroicon-m-user')
                            ->maxLength(100)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_telefono')
                            ->label('Teléfono')
                            ->placeholder('Ej. 229 123 4567')
                            ->tel()
                            ->prefixIcon('heroicon-m-device-phone-mobile')
                            ->maxLength(20)
                            ->default(null),
                        Forms\Components\TextInput::make('contacto_email')
                            ->label('Correo electrónico')
                            ->placeholder('Ej. ventas@proveedor.com.mx')
                            ->email()
                            ->prefixIcon('heroicon-m-envelope')
                            ->maxLength(100)
                            ->default(null)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_empresa')->wrap(false)->limit(30)->grow(false)
                    ->label('Empresa')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfc')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Rfc')
                    ->fontFamily('mono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_nombre')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Contacto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_telefono')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contacto_email')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('activo')->wrap(false)->limit(30)->grow(false)
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
                Tables\Columns\TextColumn::make('created_at')->wrap(false)->limit(30)->grow(false)->toggleable(isToggledHiddenByDefault: true)->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre_empresa', 'asc')
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
                    ->modalHeading(fn (?Proveedor $record) => $record?->nombre_empresa ?? 'Detalle de proveedor')
                    ->modalDescription(fn (?Proveedor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->contacto_email ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Editar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->alpineClickHandler(fn (?Proveedor $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_eliminar')
                            ->label('Eliminar')
                            ->color('danger')
                            ->icon('heroicon-m-trash')
                            ->alpineClickHandler(fn (?Proveedor $record) => "\$wire.replaceMountedTableAction('delete', '{$record?->getKey()}')"),
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar')
                    ->extraAttributes(['title' => 'Editar'])
                    ->modalHeading(fn (?Proveedor $record) => 'Editar: ' . ($record?->nombre_empresa ?? 'proveedor'))
                    ->modalDescription(fn (?Proveedor $record) => new \Illuminate\Support\HtmlString(
                        e($record?->contacto_email ?? '—') . ' · ID: #' . e($record?->getKey())
                    ))
                    ->successNotificationTitle('Proveedor actualizado correctamente'),
                Tables\Actions\DeleteAction::make()->iconButton()->tooltip('Eliminar')->extraAttributes(['title' => 'Eliminar'])->successNotificationTitle('Registro eliminado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Proveedor eliminado'),
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
                                Section::make('Datos de la empresa')
                                    ->schema([
                                        TextEntry::make('nombre_empresa')
                                            ->label('Empresa')
                                            ->icon('heroicon-m-building-office')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('rfc')
                                            ->label('RFC')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-identification')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('activo')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn ($state): string => $state ? '● Activo' : '● Inactivo')
                                            ->color(fn ($state): string => $state ? 'success' : 'danger')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Section::make('Contacto')
                                    ->schema([
                                        TextEntry::make('contacto_nombre')
                                            ->label('Contacto')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('contacto_telefono')
                                            ->label('Teléfono')
                                            ->icon('heroicon-m-phone')
                                            ->placeholder('Por capturar'),
                                        TextEntry::make('contacto_email')
                                            ->label('Correo')
                                            ->icon('heroicon-m-envelope')
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
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}
