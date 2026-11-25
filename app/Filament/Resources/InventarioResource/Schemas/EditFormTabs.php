<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Filament\Resources\InventarioResource;
use App\Filament\Schemas\CatalogoBaseFormSchema;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Proveedor;
use App\Support\SigemUniqueValidator;
use App\Support\SubformSlideOverHelper;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;

class EditFormTabs
{
    use InventarioFormFields;

    /**
     * Retorna las 4 secciones de edición SIEMPRE DESPLEGADAS (sin pestañas ni collapsibles):
     * 1. Datos del equipo -> 2. Ubicación -> 3. Especificaciones técnicas -> 4. Datos administrativos.
     */
    public static function make(): array
    {
        return [
            // =================================================================
            // SECCIÓN 1: 📦 DATOS DEL EQUIPO (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('📦 Datos del equipo')
                ->description('Los datos principales del activo')
                ->icon('heroicon-m-identification')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('codigo')
                                ->label('Código')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefixIcon('heroicon-m-qr-code')
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->codigo ?: ('#' . $record?->id_inventario))),

                            Forms\Components\TextInput::make('categoria_display')
                                ->label('Tipo de material / Categoría')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')),

                            Forms\Components\TextInput::make('nombre_equipo')
                                ->label('Nombre del equipo')
                                ->placeholder('Ej. Impresora HP LaserJet Pro')
                                ->maxLength(100)
                                ->required()
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->nombre ?? $record?->subtipo ?? ''))
                                ->columnSpan(2),

                            Forms\Components\Select::make('marca')
                                ->label('Marca')
                                ->placeholder('Selecciona la marca...')
                                ->options(fn (): array => MarcaMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray())
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    SubformSlideOverHelper::headerNav(),
                                    Forms\Components\TextInput::make('nombre')
                                        ->label('Nombre de la marca')
                                        ->placeholder('Ej. Dell, HP, Lenovo...')
                                        ->required()
                                        ->maxLength(100)
                                        ->live(onBlur: true)
                                        ->rule(SigemUniqueValidator::reglaCatalogoUnico(MarcaMaterial::class, 'una marca', 'nombre', false)),
                                    CatalogoBaseFormSchema::alertaSimilitudCatalogo('nombre', MarcaMaterial::class, 'una marca', 'nombre', false),
                                    SubformSlideOverHelper::catalogTip('La marca estará disponible inmediatamente en el catálogo.'),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    $clean = trim($data['nombre']);
                                    $existente = SigemUniqueValidator::buscarPorValorNormalizado(MarcaMaterial::class, 'nombre', $clean);
                                    if ($existente instanceof MarcaMaterial) {
                                        if ($existente->trashed()) {
                                            $existente->restore();
                                        }

                                        return $existente->nombre;
                                    }
                                    $marca = MarcaMaterial::create(['nombre' => $clean]);

                                    return $marca->nombre;
                                })
                                ->createOptionAction(function (Action $action) {
                                    return SubformSlideOverHelper::configureAction(
                                        $action,
                                        '✏️ Agregar marca como nueva opción',
                                        '+ Agregar',
                                        'Marca creada correctamente'
                                    );
                                })
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificaciones['marca'] ?? $record?->especificaciones['cpu_marca'] ?? $record?->material?->marca?->nombre ?? '')),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->maxLength(100)
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->modelo ?? $record?->material?->modelo ?? '')),

                            Forms\Components\TextInput::make('num_serie')
                                ->label('Número de serie (S/N)')
                                ->maxLength(100)
                                ->live(onBlur: true)
                                ->rule(static::getNumSerieUniqueRule())
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->num_serie ?? '')),

                            static::getNumSerieSimilarAlert('num_serie'),

                            Forms\Components\TextInput::make('subtipo')
                                ->label('Subtipo')
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\Select::make('estado')
                                ->label('Estado')
                                ->options([
                                    'Disponible' => 'Disponible',
                                    'Asignado' => 'Asignado',
                                    'Reasignado' => 'Reasignado',
                                    'En Mantenimiento' => 'En Mantenimiento',
                                    'Cambio de área' => 'Cambio de área',
                                    'Solicitud de baja' => 'Solicitud de baja',
                                    'Dañado' => 'Dañado',
                                    'Dado de baja' => 'Dado de baja',
                                    'Baja' => 'Baja',
                                    'Devuelto a Proveedor' => 'Devuelto a Proveedor',
                                ])
                                ->required(),

                            Forms\Components\Select::make('tipo_propiedad')
                                ->label('Tipo de propiedad')
                                ->options([
                                    'Propio' => 'Propio',
                                    'Rentado' => 'Rentado',
                                ])
                                ->default('Propio')
                                ->live(),

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
                                ->label('Especificaciones generales')
                                ->maxLength(255)
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificacion_texto ?? ($record?->especificaciones['especificaciones'] ?? '')))
                                ->columnSpan(2),
                        ]),
                ])
                ->columnSpanFull(),

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
                                ->placeholder('Selecciona el departamento...')
                                ->options(fn (): array => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->prefixIcon('heroicon-m-building-office')
                                ->afterStateHydrated(function (Forms\Components\Select $component, ?Inventario $record): void {
                                    if ($record && blank($component->getState())) {
                                        $component->state($record->departamento_id ?? $record->area?->id_departamento);
                                    }
                                })
                                ->afterStateUpdated(function (Forms\Set $set): void {
                                    $set('area_id', null);
                                    $set('aula', null);
                                }),

                            Forms\Components\Select::make('area_id')
                                ->label('Área')
                                ->placeholder('Selecciona el área...')
                                ->options(function (Forms\Get $get, ?Inventario $record = null): array {
                                    $deptoId = $get('departamento_id') ?? $record?->departamento_id ?? $record?->area?->id_departamento;

                                    return $deptoId
                                        ? Area::where('id_departamento', $deptoId)->orderBy('nombre')->pluck('nombre', 'id_area')->toArray()
                                        : Area::orderBy('nombre')->pluck('nombre', 'id_area')->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('aula', null))
                                ->prefixIcon('heroicon-m-rectangle-group'),

                            Forms\Components\TextInput::make('aula')
                                ->label('Aula')
                                ->placeholder('Ej. E101')
                                ->maxLength(50)
                                ->prefixIcon('heroicon-m-academic-cap')
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->aula ?? $record?->salon?->nombre ?? ($record?->especificaciones['aula'] ?? null) ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record?->ubicacion_fisica ?? '', $m) ? $m[1] : null) ?? '')),

                            static::getReceptorSelectField(),
                        ]),
                ])
                ->columnSpanFull(),

            // =================================================================
            // SECCIÓN 3: 🔧 ESPECIFICACIONES TÉCNICAS (siempre desplegada)
            // =================================================================
            Forms\Components\Section::make('🔧 Especificaciones técnicas')
                ->description('Detalles técnicos de hardware y conectividad de red del equipo')
                ->icon('heroicon-m-cpu-chip')
                ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio', 'imp', 'swi'], true)
                    || filled($record?->ram)
                    || filled($record?->procesador)
                    || filled($record?->disco)
                    || filled($record?->ip_address)
                    || filled($record?->mac_address))
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('ram')
                                ->label('Memoria RAM')
                                ->placeholder('Ej. 8 GB DDR4')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-circle-stack')
                                ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true) || filled($record?->ram))
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ram ?? ($record?->especificaciones['ram'] ?? ''))),

                            Forms\Components\TextInput::make('procesador')
                                ->label('Procesador')
                                ->placeholder('Ej. Intel Core i5-8500')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-cpu-chip')
                                ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true) || filled($record?->procesador))
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->procesador ?? ($record?->especificaciones['procesador'] ?? ''))),

                            Forms\Components\TextInput::make('disco')
                                ->label('Disco duro')
                                ->placeholder('Ej. 256 GB SSD')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-circle-stack')
                                ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true) || filled($record?->disco))
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->disco ?? ($record?->especificaciones['disco'] ?? ''))),

                            Forms\Components\TextInput::make('sistema_operativo')
                                ->label('Sistema operativo')
                                ->placeholder('Ej. Windows 11 Pro')
                                ->maxLength(100)
                                ->prefixIcon('heroicon-m-computer-desktop')
                                ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(InventarioResource::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true) || filled($record?->sistema_operativo))
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->sistema_operativo ?? ($record?->especificaciones['sistema_operativo'] ?? ''))),

                            Forms\Components\TextInput::make('ip_address')
                                ->label('Dirección IP')
                                ->placeholder('Ej. 192.168.1.100')
                                ->maxLength(50)
                                ->prefixIcon('heroicon-m-globe-alt')
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ip_address ?? ($record?->especificaciones['ip_address'] ?? ''))),

                            Forms\Components\TextInput::make('mac_address')
                                ->label('Dirección MAC')
                                ->placeholder('Ej. AA:BB:CC:DD:EE:FF')
                                ->maxLength(50)
                                ->prefixIcon('heroicon-m-signal')
                                ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->mac_address ?? ($record?->especificaciones['mac_address'] ?? ''))),
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
                    Forms\Components\Grid::make(2)
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
                        ]),

                    Forms\Components\Grid::make(2)
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

                    Forms\Components\Textarea::make('observaciones_adicionales')
                        ->label('Observaciones adicionales')
                        ->placeholder('Notas u observaciones sobre el estado del equipo...')
                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->observaciones_generales))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }
}
