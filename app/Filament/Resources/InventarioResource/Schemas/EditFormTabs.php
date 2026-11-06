<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Filament\Resources\InventarioResource;
use App\Models\Area;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use Filament\Forms;

class EditFormTabs
{
    public static function make(): array
    {
        return [
            Forms\Components\Tabs\Tab::make('Identificación y ubicación')
                ->icon('heroicon-m-identification')
                ->schema([
                    Forms\Components\Section::make('Información del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('nombre_equipo')
                                        ->label('Nombre (Material/Equipo)')
                                        ->placeholder('Ej. Impresora HP LaserJet Pro')
                                        ->required()
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->nombre ?? $record?->subtipo ?? ''))
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('marca')
                                        ->label('Marca')
                                        ->placeholder('Selecciona la marca...')
                                        ->options(fn (): array => MarcaMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificaciones['marca'] ?? $record?->especificaciones['cpu_marca'] ?? $record?->material?->marca?->nombre ?? '')),

                                    Forms\Components\TextInput::make('modelo')
                                        ->label('Modelo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->modelo ?? $record?->material?->modelo ?? '—')),

                                    Forms\Components\TextInput::make('num_serie')
                                        ->label('No. de serie (S/N)')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('subtipo')
                                        ->label('Subtipo')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('categoria_display')
                                        ->label('Categoría / Tipo de equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(fn (?Inventario $record = null, ?Forms\Get $get = null): int => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['imp', 'swi'], true) ? 1 : 2)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')),

                                    Forms\Components\TextInput::make('tipo_impresora')
                                        ->label('Tipo de impresora')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => InventarioResource::obtenerTipoEquipoNormalizado($record, $get) === 'imp')
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->tipo_impresora ?? ($record?->especificaciones['tipo_impresora'] ?? '—'))),

                                    Forms\Components\TextInput::make('num_puertos')
                                        ->label('Número de puertos')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => InventarioResource::obtenerTipoEquipoNormalizado($record, $get) === 'swi')
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificaciones['num_puertos'] ?? '—')),

                                    Forms\Components\TextInput::make('especificacion_texto')
                                        ->label('Especificaciones')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificacion_texto ?? ($record?->especificaciones['especificaciones'] ?? '—')))
                                        ->columnSpan(2),

                                    Forms\Components\Textarea::make('observaciones_generales')
                                        ->label('Descripción del equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->rows(2)
                                        ->columnSpan(2),
                                ]),
                        ]),

                    Forms\Components\Section::make('Datos técnicos')
                        ->description('Hardware y configuración técnica (solo lectura)')
                        ->collapsible()
                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('procesador')
                                        ->label('Procesador')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->procesador ?? ($record?->especificaciones['procesador'] ?? '—'))),

                                    Forms\Components\TextInput::make('ram')
                                        ->label('Memoria RAM')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ram ?? ($record?->especificaciones['ram'] ?? '—'))),

                                    Forms\Components\TextInput::make('disco')
                                        ->label('Disco duro')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->disco ?? ($record?->especificaciones['disco'] ?? '—'))),

                                    Forms\Components\TextInput::make('sistema_operativo')
                                        ->label('Sistema operativo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->sistema_operativo ?? ($record?->especificaciones['sistema_operativo'] ?? '—'))),
                                ]),
                        ]),

                    Forms\Components\Section::make('Ubicación y asignación')
                        ->description('Ubicación física asignada del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('area_id')
                                        ->label('Área')
                                        ->placeholder('Selecciona el área...')
                                        ->options(fn (): array => Area::orderBy('nombre')->pluck('nombre', 'id_area')->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\TextInput::make('aula')
                                        ->label('Aula')
                                        ->placeholder('Ej. E101')
                                        ->maxLength(5)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->salon?->nombre ?? ($record?->especificaciones['aula'] ?? null) ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record?->ubicacion_fisica ?? '', $m) ? $m[1] : null) ?? '')),
                                ]),
                        ]),

                    Forms\Components\Section::make('Red y conectividad')
                        ->description('Parámetros de conexión a la red institucional')
                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio', 'imp', 'swi'], true) || filled($record?->ip_address) || filled($record?->mac_address))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('ip_address')
                                        ->label('Dirección IP')
                                        ->placeholder('Ej. 192.168.1.100')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ip_address ?? ($record?->especificaciones['ip_address'] ?? ''))),

                                    Forms\Components\TextInput::make('mac_address')
                                        ->label('Dirección MAC')
                                        ->placeholder('Ej. AA:BB:CC:DD:EE:FF')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->mac_address ?? ($record?->especificaciones['mac_address'] ?? ''))),
                                ]),
                        ]),
                ]),

            Forms\Components\Tabs\Tab::make('Factura y proveedor')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Forms\Components\Section::make('Datos de facturación y proveedor')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\ToggleButtons::make('tipo_propiedad')
                                        ->options([
                                            'Propio' => 'Propio',
                                            'Rentado' => 'Rentado',
                                        ])
                                        ->colors([
                                            'Propio' => 'primary',
                                            'Rentado' => 'gray',
                                        ])
                                        ->inline()
                                        ->default('Propio')
                                        ->live(),

                                    Forms\Components\Select::make('id_proveedor')
                                        ->label('Proveedor')
                                        ->placeholder('Selecciona...')
                                        ->relationship('proveedor', 'nombre_empresa')
                                        ->searchable()
                                        ->preload()
                                        ->prefixIcon('heroicon-m-truck')
                                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),

                                    Forms\Components\TextInput::make('num_factura')
                                        ->label('Número de factura')
                                        ->placeholder('Ej. FAC-2026-001')
                                        ->prefixIcon('heroicon-m-hashtag')
                                        ->maxLength(100),

                                    Forms\Components\DatePicker::make('fecha_factura')
                                        ->label('Fecha de factura')
                                        ->prefixIcon('heroicon-m-calendar'),
                                ]),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_inicio_renta')
                                        ->label('Inicio de renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),

                                    Forms\Components\DatePicker::make('fecha_fin_renta')
                                        ->label('Fin de renta')
                                        ->prefixIcon('heroicon-m-calendar-days'),

                                    Forms\Components\Textarea::make('observaciones_renta')
                                        ->label('Observaciones de renta')
                                        ->placeholder('Ej. Contrato de arrendamiento...')
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),
                        ]),
                ]),

            Forms\Components\Tabs\Tab::make('Garantía y observaciones')
                ->icon('heroicon-m-shield-check')
                ->schema([
                    Forms\Components\Section::make('Estado y garantía del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_registro')
                                        ->label('Fecha de registro')
                                        ->default(now())
                                        ->prefixIcon('heroicon-m-clock'),

                                    Forms\Components\DatePicker::make('garantia_fecha_fin')
                                        ->label('Fin de garantía')
                                        ->prefixIcon('heroicon-m-shield-check'),

                                    Forms\Components\Select::make('garantia_estado')
                                        ->label('Estado de garantía')
                                        ->placeholder('Selecciona...')
                                        ->options([
                                            'vigente' => 'Vigente',
                                            'vencida' => 'Vencida',
                                            'sin_garantia' => 'Sin Garantía',
                                        ])
                                        ->default('sin_garantia'),

                                    Forms\Components\ToggleButtons::make('estado')
                                        ->label('Estado del activo')
                                        ->options([
                                            'Disponible' => 'Disponible',
                                            'Asignado' => 'Asignado',
                                            'Reasignado' => 'Reasignado',
                                            'En Mantenimiento' => 'En Mantenimiento',
                                            'Dañado' => 'Dañado',
                                            'Baja' => 'Baja',
                                        ])
                                        ->colors([
                                            'Disponible' => 'success',
                                            'Asignado' => 'info',
                                            'Reasignado' => 'reassign',
                                            'En Mantenimiento' => 'warning',
                                            'Dañado' => 'danger',
                                            'Baja' => 'gray',
                                        ])
                                        ->inline(),
                                ]),

                            Forms\Components\Textarea::make('observaciones_adicionales')
                                ->label('Observaciones adicionales')
                                ->placeholder('Notas u observaciones sobre el estado...')
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->observaciones_generales))
                                ->dehydrated(false)
                                ->rows(2),
                        ]),
                ]),
        ];
    }
}
