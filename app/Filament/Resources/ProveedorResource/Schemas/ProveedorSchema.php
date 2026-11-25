<?php

namespace App\Filament\Resources\ProveedorResource\Schemas;

use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\Proveedor;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Illuminate\Support\HtmlString;

class ProveedorSchema
{
    public static function getFormSchema(): array
    {
        return [
            CatalogoBaseFormSchema::section('🏢 Datos del proveedor', 'heroicon-m-building-office-2', [
                Forms\Components\TextInput::make('nombre_empresa')
                    ->label('Nombre de la empresa')
                    ->placeholder('Ej. Soluciones Tecnológicas S.A. de C.V.')
                    ->required()
                    ->prefixIcon('heroicon-m-building-storefront')
                    ->maxLength(150)
                    ->rule(CatalogoBaseFormSchema::reglaNombreUnico(Proveedor::class, 'un proveedor', 'nombre_empresa')),
                Forms\Components\TextInput::make('rfc')
                    ->label('RFC')
                    ->placeholder('Ej. STE200101AAA')
                    ->prefixIcon('heroicon-m-identification')
                    ->maxLength(13)
                    ->default(null),
                Forms\Components\TextInput::make('direccion')
                    ->label('Dirección')
                    ->placeholder('Calle, número, colonia, CP')
                    ->prefixIcon('heroicon-m-map-pin')
                    ->maxLength(255)
                    ->default(null)
                    ->columnSpanFull(),
                Forms\Components\ToggleButtons::make('activo')
                    ->label('Proveedor activo')
                    ->boolean(trueLabel: 'Sí', falseLabel: 'No')
                    ->inline()
                    ->default(true)
                    ->required(),
            ], 2),
            CatalogoBaseFormSchema::section('Información de contacto', 'heroicon-m-phone', [
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
            ], 2),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre_empresa', 'Empresa', individualSearch: true, sortable: true),
            CatalogoBaseTableSchema::columnaTexto('rfc', 'RFC')->toggleable()->fontFamily('mono')->placeholder('—'),
            CatalogoBaseTableSchema::columnaTexto('contacto_nombre', 'Contacto')->toggleable()->placeholder('—'),
            CatalogoBaseTableSchema::columnaTexto('contacto_telefono', 'Teléfono')->toggleable()->placeholder('—'),
            CatalogoBaseTableSchema::columnaTexto('contacto_email', 'Correo')->toggleable()->placeholder('—'),
            CatalogoBaseTableSchema::columnaTexto('direccion', 'Dirección', limit: 35, hiddenByDefault: true)->placeholder('—'),
            Tables\Columns\TextColumn::make('activo')
                ->wrap(false)->limit(30)->grow(false)
                ->label('Estado')
                ->formatStateUsing(fn ($state) => $state ? 'Activo' : 'Inactivo')
                ->badge()
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle'),
            CatalogoBaseTableSchema::columnaFechaCreacion(),
        ];
    }

    public static function getTableActions(bool $soloLectura): array
    {
        return CatalogoBaseTableSchema::accionesConModalDetalle(
            viewHeading: fn (?Proveedor $record) => $record?->nombre_empresa ?? 'Detalle de proveedor',
            editHeading: fn (?Proveedor $record) => 'Editar: ' . ($record?->nombre_empresa ?? 'proveedor'),
            description: fn (?Proveedor $record) => new HtmlString(e($record?->contacto_email ?? '—') . ' · ID: #' . e($record?->getKey())),
            editSuccessMessage: 'Proveedor actualizado correctamente',
            soloLectura: $soloLectura,
        );
    }

    public static function getInfolistSections(): array
    {
        return [
            Section::make('🏢 Datos del proveedor')
                ->schema([
                    TextEntry::make('nombre_empresa')->label('Empresa')->icon('heroicon-m-building-office')->placeholder('Por capturar'),
                    TextEntry::make('rfc')->label('RFC')->fontFamily('mono')->icon('heroicon-m-identification')->placeholder('Por capturar'),
                    TextEntry::make('direccion')->label('Dirección')->icon('heroicon-m-map-pin')->placeholder('—')->columnSpanFull(),
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
                    TextEntry::make('contacto_nombre')->label('Contacto')->icon('heroicon-m-user')->placeholder('Por capturar'),
                    TextEntry::make('contacto_telefono')->label('Teléfono')->icon('heroicon-m-phone')->placeholder('Por capturar'),
                    TextEntry::make('contacto_email')->label('Correo')->icon('heroicon-m-envelope')->placeholder('Por capturar')->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
