<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Models\Area;
use App\Models\Departamento;
use App\Models\Proveedor;
use App\Support\UbicacionHelper;
use Filament\Forms;
use Illuminate\Support\HtmlString;

class CreateFormSchema
{
    use InventarioFormFields;

    public static function make(): array
    {
        return [
            // =================================================================
            // SECCIÓN 1: 📦 DATOS DEL EQUIPO (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('📦 Datos del equipo')
                ->description('Selecciona el tipo de equipo y captura los datos principales del activo')
                ->icon('heroicon-m-squares-2x2')
                ->schema([
                    Forms\Components\ViewField::make('tipo_equipo')
                        ->view('filament.forms.components.tipo-equipo-grid')
                        ->default('pc')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $map = [
                                'pc' => 'CPU',
                                'aio' => 'All in One',
                                'lap' => 'Computadora Portátil',
                                'cpu' => 'Computadora de Escritorio',
                                'mon' => 'Monitor',
                                'tec' => 'Teclado',
                                'mou' => 'Mouse',
                                'imp' => 'Impresora',
                                'pro' => 'Proyector',
                                'nob' => 'No-Break',
                                'swi' => 'Switch de red',
                                'otro' => 'Otro artículo',
                            ];
                            $set('subtipo', $map[$state] ?? $state);
                        }),

                    Forms\Components\Placeholder::make('banner_informativo')
                        ->label('')
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio'], true))
                        ->content(function (Forms\Get $get) {
                            $tipo = $get('tipo_equipo') ?? 'pc';
                            $texto = match ($tipo) {
                                'pc' => 'Se registrarán <strong>monitor, teclado, mouse y CPU</strong> como un solo equipo. Si le falta alguno, marca <strong>«No tiene»</strong>.',
                                'aio' => 'Se registrará el <strong>All in One</strong> con su teclado y mouse como un solo equipo. Si le falta alguno, marca <strong>«No tiene»</strong>.',
                                default => '',
                            };

                            return new HtmlString('
                                <div style="background-color: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af; padding: 0.875rem 1.25rem; border-radius: 0.5rem; font-size: 0.9375rem; display: flex; align-items: center; gap: 0.625rem;">
                                    <span style="flex-shrink: 0; font-size: 1.125rem; line-height: 1;">💡</span>
                                    <span style="white-space: normal; line-height: 1.5; word-break: normal;">' . $texto . '</span>
                                </div>
                            ');
                        })
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                        ->schema([
                            Forms\Components\Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'Reasignado' => 'Reasignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Dañado' => 'Dañado',
                                    'Baja' => 'Baja',
                                ])
                                ->default('Disponible')
                                ->required(),

                            Forms\Components\Select::make('tipo_propiedad')
                                ->label('Tipo de propiedad')
                                ->options([
                                    'Propio' => 'Propio',
                                    'Rentado' => 'Rentado',
                                ])
                                ->default('Propio')
                                ->live()
                                ->required(),
                        ]),
                ])
                ->columnSpanFull(),

            // Cards por componente (parte de Datos del equipo, siempre desplegadas)
            ...ComponentCardsSchema::make(),

            // =================================================================
            // SECCIÓN 2: 📍 UBICACIÓN (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('📍 Ubicación')
                ->description('Ubicación física estructurada y receptor asignado al equipo')
                ->icon('heroicon-m-map-pin')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                        ->schema([
                            Forms\Components\Select::make('departamento_id')
                                ->label('Departamento')
                                ->placeholder('Selecciona departamento...')
                                ->options(fn (): array => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $set('area_id', null);
                                    $set('aula', null);
                                    $set('especificaciones.aula', null);
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-building-office'),

                            Forms\Components\Select::make('area_id')
                                ->label('Área')
                                ->placeholder(fn (Forms\Get $get) => $get('departamento_id') ? 'Selecciona área...' : 'Elige departamento primero')
                                ->options(function (Forms\Get $get): array {
                                    $deptoId = $get('departamento_id');
                                    if (! $deptoId) {
                                        return [];
                                    }

                                    return Area::where('id_departamento', $deptoId)
                                        ->orderBy('nombre')
                                        ->pluck('nombre', 'id_area')
                                        ->toArray();
                                })
                                ->searchable()
                                ->live()
                                ->disabled(fn (Forms\Get $get) => blank($get('departamento_id')))
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $set('aula', null);
                                    $set('especificaciones.aula', null);
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-rectangle-group'),

                            Forms\Components\TextInput::make('aula')
                                ->label('Aula')
                                ->placeholder('ej. E101')
                                ->maxLength(50)
                                ->helperText('Letra del edificio + planta + número. Déjalo vacío si el equipo no está en un aula.')
                                ->prefixIcon('heroicon-m-academic-cap')
                                ->lazy()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $set('especificaciones.aula', $state);
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                }),

                            static::getReceptorSelectField(),

                            Forms\Components\Hidden::make('especificaciones.aula'),
                        ]),
                ])
                ->columnSpanFull(),

            // =================================================================
            // SECCIÓN 3: 🔧 ESPECIFICACIONES TÉCNICAS (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('🔧 Especificaciones técnicas')
                ->description('Detalles técnicos de hardware y conectividad de red del equipo')
                ->icon('heroicon-m-cpu-chip')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap', 'cpu', 'imp', 'swi'], true))
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                        ->schema([
                            Forms\Components\TextInput::make('ram')
                                ->label('Memoria RAM')
                                ->placeholder('ej. 8 GB DDR4')
                                ->prefixIcon('heroicon-m-circle-stack')
                                ->maxLength(100)
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap', 'cpu'], true)),

                            Forms\Components\TextInput::make('procesador')
                                ->label('Procesador')
                                ->placeholder('ej. Intel Core i5-8500')
                                ->prefixIcon('heroicon-m-cpu-chip')
                                ->maxLength(100)
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap', 'cpu'], true)),

                            Forms\Components\TextInput::make('disco')
                                ->label('Disco duro')
                                ->placeholder('ej. 256 GB SSD')
                                ->prefixIcon('heroicon-m-circle-stack')
                                ->maxLength(100)
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap', 'cpu'], true)),

                            Forms\Components\TextInput::make('sistema_operativo')
                                ->label('Sistema operativo')
                                ->placeholder('ej. Windows 11 Pro')
                                ->prefixIcon('heroicon-m-computer-desktop')
                                ->maxLength(100)
                                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio', 'lap', 'cpu'], true)),

                            Forms\Components\TextInput::make('ip_address')
                                ->label('Dirección IP')
                                ->placeholder('192.168.1.__')
                                ->prefixIcon('heroicon-m-globe-alt')
                                ->maxLength(50),

                            Forms\Components\TextInput::make('mac_address')
                                ->label('Dirección MAC')
                                ->placeholder('AA:BB:CC:DD:EE:FF')
                                ->prefixIcon('heroicon-m-signal')
                                ->maxLength(50),
                        ]),
                ])
                ->columnSpanFull(),

            // =================================================================
            // SECCIÓN 4: 💰 DATOS ADMINISTRATIVOS (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('💰 Datos administrativos')
                ->description('Detalles administrativos, facturación, proveedor y garantía')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_factura')
                                ->label('Factura')
                                ->placeholder('Ej. FAC-2026-001')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->maxLength(100),

                            Forms\Components\Select::make('id_proveedor')
                                ->label('Proveedor')
                                ->placeholder('Selecciona proveedor...')
                                ->options(fn (): array => Proveedor::orderBy('nombre_empresa')->pluck('nombre_empresa', 'id_proveedor')->toArray())
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->prefixIcon('heroicon-m-truck'),

                            Forms\Components\DatePicker::make('fecha_factura')
                                ->label('Fecha de compra / factura')
                                ->prefixIcon('heroicon-m-calendar'),

                            Forms\Components\DatePicker::make('fecha_registro')
                                ->label('Fecha de registro')
                                ->default(now())
                                ->live()
                                ->prefixIcon('heroicon-m-clock')
                                ->required(),

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
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                        ->schema([
                            Forms\Components\DatePicker::make('fecha_inicio_renta')
                                ->label('Inicio de renta')
                                ->prefixIcon('heroicon-m-calendar-days'),

                            static::getRentaFechaFinField(),

                            Forms\Components\Textarea::make('observaciones_renta')
                                ->label('Observaciones de renta')
                                ->placeholder('Ej. Contrato de arrendamiento...')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('tipo_propiedad') === 'Rentado'),

                    Forms\Components\Hidden::make('subtipo')
                        ->default('Computadora'),

                    Forms\Components\Hidden::make('ubicacion_fisica'),

                    Forms\Components\Hidden::make('id_usuario')
                        ->default(fn () => auth()->id()),
                ])
                ->columnSpanFull(),
        ];
    }
}
