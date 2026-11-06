<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use Filament\Forms;

trait ConModalesBajaCambio
{
    /**
     * Esquema del formulario modal para Cambio de Área.
     */
    public static function getModalCambioAreaFormSchema(bool $esAdmin = true): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->visible(fn () => $esAdmin)
                ->schema([
                    Forms\Components\TextInput::make('departamento_actual')
                        ->label('Departamento actual')
                        ->default(fn (?Inventario $record) => $record?->departamento?->nombre ?? 'Sin departamento')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('area_actual')
                        ->label('Área actual')
                        ->default(fn (?Inventario $record) => $record?->area?->nombre ?? 'Sin área')
                        ->disabled()
                        ->dehydrated(false),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('departamento_id')
                        ->label('Nuevo Departamento')
                        ->options(fn () => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('area_id', null)),
                    Forms\Components\Select::make('area_id')
                        ->label('Nueva Área')
                        ->options(fn (Forms\Get $get) => $get('departamento_id')
                            ? Area::where('id_departamento', $get('departamento_id'))->orderBy('nombre')->pluck('nombre', 'id_area')
                            : [])
                        ->required(),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('aula')
                        ->label('Nueva Aula')
                        ->placeholder('ej. E201')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('estacion')
                        ->label('Nueva Estación')
                        ->placeholder('ej. AC001')
                        ->maxLength(50),
                ]),
            Forms\Components\Textarea::make('motivo')
                ->label('Motivo del cambio')
                ->placeholder('Explicación detallada del motivo del cambio...')
                ->required(),
        ];
    }

    /**
     * Esquema del formulario modal para Baja de Equipo.
     */
    public static function getModalBajaFormSchema(): array
    {
        return [
            Forms\Components\Select::make('motivo_baja')
                ->label('Motivo de baja')
                ->options([
                    'Daño irreparable' => 'Daño irreparable',
                    'Obsolescencia' => 'Obsolescencia',
                    'Pérdida' => 'Pérdida',
                    'Robo' => 'Robo',
                    'Otro' => 'Otro',
                ])
                ->required(),
            Forms\Components\Textarea::make('observaciones_baja')
                ->label('Observaciones detalladas')
                ->placeholder('Explicación detallada del motivo de la baja...')
                ->required(),
        ];
    }
}
