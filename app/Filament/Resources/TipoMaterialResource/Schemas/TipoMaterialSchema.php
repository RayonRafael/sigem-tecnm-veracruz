<?php

namespace App\Filament\Resources\TipoMaterialResource\Schemas;

use App\Filament\Resources\TipoMaterialResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Filament\Schemas\CatalogoBaseTableSchema;
use App\Models\TipoMaterial;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;

class TipoMaterialSchema
{
    public static function getFormSchema(): array
    {
        return [
            CatalogoBaseFormSchema::section('📦 Datos del tipo de material', 'heroicon-m-rectangle-stack', [
                CatalogoBaseFormSchema::campoNombre('Nombre del tipo', 'Ej. Equipo de Cómputo', 'heroicon-m-rectangle-stack', uniqueModel: TipoMaterial::class, uniqueLabel: 'un tipo de material'),
                Forms\Components\Select::make('familia')
                    ->label('Familia')
                    ->placeholder('Selecciona la familia...')
                    ->options(TipoMaterialResource::getFamiliasOptions())
                    ->nullable()
                    ->searchable()
                    ->prefixIcon('heroicon-m-tag'),
            ]),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            CatalogoBaseTableSchema::columnaTexto('nombre', 'Nombre', individualSearch: true, sortable: true),
            Tables\Columns\TextColumn::make('familia')
                ->label('Familia')
                ->badge()
                ->color('info')
                ->placeholder('—')
                ->searchable(isIndividual: true)
                ->sortable(),
            CatalogoBaseTableSchema::columnaFechaCreacion(),
        ];
    }

    public static function getInfolistSections(): array
    {
        return [
            Section::make('📦 Datos del tipo de material')
                ->schema([
                    TextEntry::make('nombre')->label('Nombre')->icon('heroicon-m-rectangle-stack'),
                    TextEntry::make('familia')->label('Familia')->badge()->color('info')->placeholder('—')->icon('heroicon-m-tag'),
                    TextEntry::make('materiales_count')
                        ->label('Materiales registrados')
                        ->state(fn (TipoMaterial $record): string => $record->materiales()->count() . ' registrados')
                        ->icon('heroicon-m-cube'),
                ])->columns(2),
        ];
    }
}
