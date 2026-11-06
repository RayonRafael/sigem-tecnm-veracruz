<?php

namespace App\Filament\Resources;

use App\Enums\EstadoInventario;
use App\Enums\RoleEnum;
use App\Filament\Resources\InventarioResource\Pages;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Inventario;
use App\Models\MarcaMaterial;
use App\Models\Salon;
use App\Models\TipoMaterial;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class InventarioResource extends Resource
{
    use \App\Filament\Concerns\ConModalesBajaCambio;
    use \App\Filament\Resources\InventarioResource\Schemas\InventarioFormFields;
    protected static ?string $model = Inventario::class;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'registro de inventario';

    protected static ?string $pluralModelLabel = 'registros de inventario';

    protected static ?string $navigationGroup = 'GestiÃ³n de inventario';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['material.tipo', 'material.marca', 'departamento', 'area.departamento', 'salon']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['num_serie', 'num_factura'];
    }

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'create' || ($form->getLivewire() instanceof \Filament\Resources\Pages\CreateRecord)) {
            return $form
                ->schema(static::getMockupFormSchema());
        }

        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs(static::getEditFormTabs())
                    ->columnSpanFull(),
            ]);
    }

    public static function getMockupFormSchema(): array
    {
        return \App\Filament\Resources\InventarioResource\Schemas\CreateFormSchema::make();
    }

    protected static function getComponentCardsSchema(): array
    {
        return \App\Filament\Resources\InventarioResource\Schemas\ComponentCardsSchema::make();
    }

    public static function actualizarUbicacionDescriptiva(Forms\Set $set, Forms\Get $get): void
    {
        \App\Support\UbicacionHelper::actualizarDescriptiva($set, $get);
    }

    public static function registrarMultiplesEquipos(array $data): Inventario
    {
        return \App\Filament\Resources\InventarioResource\Actions\RegistrarMultiplesEquipos::ejecutar($data);
    }

    public static function obtenerMensajeExito(?Inventario $record): string
    {
        if (! $record) {
            return 'Equipo registrado exitosamente';
        }

        $total = $record->especificaciones['_total_registrados'] ?? 1;
        $estacion = $record->estacion;

        if ($total > 1 && $estacion) {
            return "Se registraron {$total} equipos como estaciÃ³n {$estacion}";
        }

        return 'Equipo registrado exitosamente';
    }

    public static function obtenerTipoEquipoNormalizado(?Inventario $record = null, ?Forms\Get $get = null): string
    {
        if (! $record && $get) {
            $record = $get('record');
        }

        if (! $record) {
            $subtipo = strtolower(trim((string) ($get ? $get('subtipo') : '')));
            $nombre = strtolower(trim((string) ($get ? $get('nombre_equipo') : '')));
            $tipoMat = strtolower(trim((string) ($get ? $get('categoria_display') : '')));
            $combined = " {$subtipo} {$nombre} {$tipoMat} ";
            if (str_contains($combined, 'impresora') || str_contains($combined, 'multifuncional')) return 'imp';
            if (str_contains($combined, 'switch') || str_contains($combined, 'conmutador')) return 'swi';
            if (str_contains($combined, 'monitor') || str_contains($combined, 'pantalla')) return 'mon';
            if (str_contains($combined, 'all in one') || str_contains($combined, 'aio')) return 'aio';
            if (str_contains($combined, 'laptop') || str_contains($combined, 'notebook')) return 'lap';
            if (str_contains($combined, 'cpu') || str_contains($combined, 'computadora') || str_contains($combined, 'gabinete')) return 'cpu';
            return 'otro';
        }

        $subtipo = strtolower(trim((string) ($record->subtipo ?? '')));
        $matNombre = strtolower(trim((string) ($record->material?->nombre ?? '')));
        $matTipo = strtolower(trim((string) ($record->material?->tipo?->nombre ?? '')));
        $componente = strtolower(trim((string) ($record->especificaciones['componente'] ?? '')));
        $tipoEquipo = strtolower(trim((string) ($record->tipo_equipo ?? ($record->especificaciones['tipo_equipo'] ?? ''))));

        $combined = " {$subtipo} {$matNombre} {$matTipo} ";

        // 1. All in One
        if (
            str_contains($combined, 'all in one') ||
            str_contains($combined, 'all-in-one') ||
            str_contains($combined, 'aio') ||
            $tipoEquipo === 'aio'
        ) {
            return 'aio';
        }

        // 2. Laptop / PortÃ¡til
        if (
            str_contains($combined, 'laptop') ||
            str_contains($combined, 'notebook') ||
            str_contains($combined, 'portÃ¡til') ||
            str_contains($combined, 'portatil') ||
            $tipoEquipo === 'lap'
        ) {
            return 'lap';
        }

        // 3. Impresora
        if (
            str_contains($combined, 'impresora') ||
            str_contains($combined, 'multifuncional') ||
            $tipoEquipo === 'imp' ||
            filled($record->tipo_impresora) ||
            isset($record->especificaciones['tipo_impresora'])
        ) {
            return 'imp';
        }

        // 4. Switch de red
        if (
            str_contains($combined, 'switch') ||
            str_contains($combined, 'conmutador') ||
            $tipoEquipo === 'swi' ||
            isset($record->especificaciones['num_puertos'])
        ) {
            return 'swi';
        }

        // 5. Monitor / Pantalla
        if (
            str_contains($combined, 'monitor') ||
            str_contains($combined, 'pantalla') ||
            $componente === 'mon' ||
            $tipoEquipo === 'mon'
        ) {
            return 'mon';
        }

        // 6. Teclado
        if (
            str_contains($combined, 'teclado') ||
            str_contains($combined, 'keyboard') ||
            $componente === 'tec' ||
            $tipoEquipo === 'tec'
        ) {
            return 'periferico';
        }

        // 7. Mouse
        if (
            str_contains($combined, 'mouse') ||
            str_contains($combined, 'ratÃ³n') ||
            str_contains($combined, 'raton') ||
            $componente === 'mou' ||
            $tipoEquipo === 'mou'
        ) {
            return 'periferico';
        }

        // 8. Proyector
        if (
            str_contains($combined, 'proyector') ||
            str_contains($combined, 'caÃ±Ã³n') ||
            str_contains($combined, 'canon') ||
            $tipoEquipo === 'proy'
        ) {
            return 'periferico';
        }

        // 9. No-Break / Regulador / UPS
        if (
            str_contains($combined, 'no-break') ||
            str_contains($combined, 'nobreak') ||
            str_contains($combined, 'no break') ||
            str_contains($combined, 'regulador') ||
            str_contains($combined, 'ups') ||
            $tipoEquipo === 'nob'
        ) {
            return 'periferico';
        }

        // 10. CPU / PC / Gabinete / Computadora
        if (
            str_contains($combined, 'cpu') ||
            str_contains($combined, 'gabinete') ||
            str_contains($combined, 'computadora') ||
            str_contains($combined, 'escritorio') ||
            str_contains($combined, 'optiplex') ||
            str_contains($combined, 'prodesk') ||
            $componente === 'cpu' ||
            $tipoEquipo === 'cpu' ||
            $tipoEquipo === 'pc'
        ) {
            return 'cpu';
        }

        return 'otro';
    }

    public static function getEditFormTabs(): array
    {
        return [
            Forms\Components\Tabs\Tab::make('IdentificaciÃ³n y ubicaciÃ³n')
                ->icon('heroicon-m-identification')
                ->schema([
                    Forms\Components\Section::make('InformaciÃ³n del equipo')
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
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->modelo ?? $record?->material?->modelo ?? 'â€”')),

                                    Forms\Components\TextInput::make('num_serie')
                                        ->label('No. de serie (S/N)')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('subtipo')
                                        ->label('Subtipo')
                                        ->disabled()
                                        ->dehydrated(false),

                                    Forms\Components\TextInput::make('categoria_display')
                                        ->label('CategorÃ­a / Tipo de equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(fn (?Inventario $record = null, ?Forms\Get $get = null): int => in_array(static::obtenerTipoEquipoNormalizado($record, $get), ['imp', 'swi'], true) ? 1 : 2)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')),

                                    Forms\Components\TextInput::make('tipo_impresora')
                                        ->label('Tipo de impresora')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => static::obtenerTipoEquipoNormalizado($record, $get) === 'imp')
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->tipo_impresora ?? ($record?->especificaciones['tipo_impresora'] ?? 'â€”'))),

                                    Forms\Components\TextInput::make('num_puertos')
                                        ->label('NÃºmero de puertos')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => static::obtenerTipoEquipoNormalizado($record, $get) === 'swi')
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificaciones['num_puertos'] ?? 'â€”')),

                                    Forms\Components\TextInput::make('especificacion_texto')
                                        ->label('Especificaciones')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->especificacion_texto ?? ($record?->especificaciones['especificaciones'] ?? 'â€”')))
                                        ->columnSpan(2),

                                    Forms\Components\Textarea::make('observaciones_generales')
                                        ->label('DescripciÃ³n del equipo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->rows(2)
                                        ->columnSpan(2),
                                ]),
                        ]),

                    Forms\Components\Section::make('Datos tÃ©cnicos')
                        ->description('Hardware y configuraciÃ³n tÃ©cnica (solo lectura)')
                        ->collapsible()
                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(static::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio'], true))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('procesador')
                                        ->label('Procesador')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->procesador ?? ($record?->especificaciones['procesador'] ?? 'â€”'))),

                                    Forms\Components\TextInput::make('ram')
                                        ->label('Memoria RAM')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ram ?? ($record?->especificaciones['ram'] ?? 'â€”'))),

                                    Forms\Components\TextInput::make('disco')
                                        ->label('Disco duro')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->disco ?? ($record?->especificaciones['disco'] ?? 'â€”'))),

                                    Forms\Components\TextInput::make('sistema_operativo')
                                        ->label('Sistema operativo')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->sistema_operativo ?? ($record?->especificaciones['sistema_operativo'] ?? 'â€”'))),
                                ]),
                        ]),

                    Forms\Components\Section::make('UbicaciÃ³n y asignaciÃ³n')
                        ->description('UbicaciÃ³n fÃ­sica asignada del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('area_id')
                                        ->label('Ãrea')
                                        ->placeholder('Selecciona el Ã¡rea...')
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
                        ->description('ParÃ¡metros de conexiÃ³n a la red institucional')
                        ->visible(fn (?Inventario $record = null, ?Forms\Get $get = null): bool => in_array(static::obtenerTipoEquipoNormalizado($record, $get), ['cpu', 'lap', 'aio', 'imp', 'swi'], true) || filled($record?->ip_address) || filled($record?->mac_address))
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('ip_address')
                                        ->label('DirecciÃ³n IP')
                                        ->placeholder('Ej. 192.168.1.100')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->ip_address ?? ($record?->especificaciones['ip_address'] ?? ''))),

                                    Forms\Components\TextInput::make('mac_address')
                                        ->label('DirecciÃ³n MAC')
                                        ->placeholder('Ej. AA:BB:CC:DD:EE:FF')
                                        ->maxLength(50)
                                        ->afterStateHydrated(fn (Forms\Components\Component $component, ?Inventario $record) => $component->state($record?->mac_address ?? ($record?->especificaciones['mac_address'] ?? ''))),
                                ]),
                        ]),
                ]),

            Forms\Components\Tabs\Tab::make('Factura y proveedor')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Forms\Components\Section::make('Datos de facturaciÃ³n y proveedor')
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
                                        ->label('NÃºmero de factura')
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

            Forms\Components\Tabs\Tab::make('GarantÃ­a y observaciones')
                ->icon('heroicon-m-shield-check')
                ->schema([
                    Forms\Components\Section::make('Estado y garantÃ­a del equipo')
                        ->schema([
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\DatePicker::make('fecha_registro')
                                        ->label('Fecha de registro')
                                        ->default(now())
                                        ->prefixIcon('heroicon-m-clock'),

                                    Forms\Components\DatePicker::make('garantia_fecha_fin')
                                        ->label('Fin de garantÃ­a')
                                        ->prefixIcon('heroicon-m-shield-check'),

                                    Forms\Components\Select::make('garantia_estado')
                                        ->label('Estado de garantÃ­a')
                                        ->placeholder('Selecciona...')
                                        ->options([
                                            'vigente' => 'Vigente',
                                            'vencida' => 'Vencida',
                                            'sin_garantia' => 'Sin GarantÃ­a',
                                        ])
                                        ->default('sin_garantia'),

                                    Forms\Components\ToggleButtons::make('estado')
                                        ->label('Estado del activo')
                                        ->options([
                                            'Disponible' => 'Disponible',
                                            'Asignado' => 'Asignado',
                                            'Reasignado' => 'Reasignado',
                                            'En Mantenimiento' => 'En Mantenimiento',
                                            'DaÃ±ado' => 'DaÃ±ado',
                                            'Baja' => 'Baja',
                                        ])
                                        ->colors([
                                            'Disponible' => 'success',
                                            'Asignado' => 'info',
                                            'Reasignado' => 'reassign',
                                            'En Mantenimiento' => 'warning',
                                            'DaÃ±ado' => 'danger',
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

    public static function hidratarDatosEdicion(array $data, ?Inventario $record = null): array
    {
        if (! $record) {
            return $data;
        }

        $data['nombre_equipo'] = $record->material?->nombre ?? $record->subtipo ?? '';
        $data['marca'] = $record->especificaciones['marca']
            ?? $record->especificaciones['cpu_marca']
            ?? $record->material?->marca?->nombre
            ?? '';
        $data['aula'] = $record->salon?->nombre
            ?? ($record->especificaciones['aula'] ?? null)
            ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
            ?? '';
        $data['categoria_display'] = $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General';
        $data['especificacion_texto'] = $record->especificacion_texto ?? ($record->especificaciones['especificaciones'] ?? '');
        $data['procesador'] = $record->procesador ?? ($record->especificaciones['procesador'] ?? 'â€”');
        $data['ram'] = $record->ram ?? ($record->especificaciones['ram'] ?? 'â€”');
        $data['disco'] = $record->disco ?? ($record->especificaciones['disco'] ?? 'â€”');
        $data['sistema_operativo'] = $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? 'â€”');
        $data['tipo_impresora'] = $record->tipo_impresora ?? ($record->especificaciones['tipo_impresora'] ?? 'â€”');
        $data['num_puertos'] = $record->especificaciones['num_puertos'] ?? 'â€”';
        $data['ip_address'] = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? '');
        $data['mac_address'] = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? '');

        return $data;
    }

    public static function guardarCambiosEquipo(Inventario $record, array $data): Inventario
    {
        // 1. Nombre (Material/Equipo)
        if (isset($data['nombre_equipo']) && filled($data['nombre_equipo'])) {
            $nuevoNombre = trim($data['nombre_equipo']);
            $record->subtipo = $nuevoNombre;
            if ($record->material) {
                $record->material->update(['nombre' => $nuevoNombre]);
            }
        }

        // 2. Marca
        if (isset($data['marca']) && filled($data['marca'])) {
            $nuevaMarca = trim($data['marca']);
            $specs = $record->especificaciones ?? [];
            $specs['marca'] = $nuevaMarca;
            $record->especificaciones = $specs;

            $marcaModel = MarcaMaterial::firstOrCreate(['nombre' => $nuevaMarca]);
            if ($record->material) {
                $record->material->update(['id_marca' => $marcaModel->id_marca]);
            }
        }

        // 3. Aula
        if (isset($data['aula'])) {
            $nuevaAula = trim($data['aula']);
            $specs = $record->especificaciones ?? [];
            $specs['aula'] = $nuevaAula;
            $record->especificaciones = $specs;

            if ($record->area_id && filled($nuevaAula)) {
                $salon = Salon::where('area_id', $record->area_id)
                    ->where('nombre', $nuevaAula)
                    ->first();
                if ($salon) {
                    $record->salon_id = $salon->id;
                }
            }
        }

        // 4. IP & MAC
        if (isset($data['ip_address'])) {
            $record->ip_address = $data['ip_address'];
            $specs = $record->especificaciones ?? [];
            $specs['ip_address'] = $data['ip_address'];
            $record->especificaciones = $specs;
        }
        if (isset($data['mac_address'])) {
            $record->mac_address = $data['mac_address'];
            $specs = $record->especificaciones ?? [];
            $specs['mac_address'] = $data['mac_address'];
            $record->especificaciones = $specs;
        }

        // 5. Ãrea
        if (isset($data['area_id']) && filled($data['area_id'])) {
            $record->area_id = $data['area_id'];
            $area = Area::find($data['area_id']);
            if ($area && $area->id_departamento) {
                $record->departamento_id = $area->id_departamento;
            }
        }

        // 6. Fill other allowable fields
        $record->fill($data);
        $record->save();

        return $record;
    }

    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('departamento.nombre')
                ->label('Departamento')
                ->extraHeaderAttributes(['style' => 'min-width: 180px;'])
                ->extraCellAttributes(['style' => 'min-width: 180px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'])
                ->tooltip(fn (Inventario $record): ?string => $record->departamento?->nombre ?? $record->area?->departamento?->nombre)
                ->getStateUsing(function (Inventario $record): ?string {
                    return $record->departamento?->nombre
                        ?? $record->area?->departamento?->nombre
                        ?? ($record->ubicacion_fisica ? explode(' > ', $record->ubicacion_fisica)[0] ?? null : null)
                        ?? 'â€”';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                          ->orWhereHas('area.departamento', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                          ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('departamento', 'inventario.departamento_id', '=', 'departamento.id_departamento')
                        ->orderBy('departamento.nombre', $direction)
                        ->select('inventario.*');
                })
                ->placeholder('â€”'),

            Tables\Columns\TextColumn::make('equipo')
                ->label('Equipo')
                ->html()
                ->extraHeaderAttributes(['style' => 'min-width: 250px;'])
                ->extraCellAttributes(['style' => 'min-width: 250px; max-width: 320px;'])
                ->getStateUsing(function (Inventario $record): string {
                    $nombre = $record->material?->nombre ?? $record->subtipo ?? 'Equipo';
                    $cod = ! empty($record->codigo) ? $record->codigo : null;
                    if ($cod) {
                        $sub = str_starts_with($cod, 'ID: ') ? $cod : 'ID: ' . $cod;
                    } else {
                        $sub = 'ID: #' . ($record->id_inventario ?? $record->id);
                    }
                    $titleMain = e($nombre);
                    $titleSub = e($cod ?? ('#' . ($record->id_inventario ?? $record->id)));
                    return '<div class="td-main" title="' . $titleMain . '" style="font-weight:600;line-height:1.25;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . $titleMain . '</div>'
                        . '<div class="td-sub" title="' . $titleSub . '" style="font-size:11.5px;color:#6b7280;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' . e($sub) . '</div>';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('material', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                          ->orWhere('subtipo', 'like', "%{$search}%")
                          ->orWhere('codigo', 'like', "%{$search}%")
                          ->orWhere('num_serie', 'like', "%{$search}%")
                          ->orWhere('modelo', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                        ->orderBy('material.nombre', $direction)
                        ->select('inventario.*');
                }),

            Tables\Columns\TextColumn::make('categoria')
                ->label('CategorÃ­a')
                ->badge()
                ->color('gray')
                ->extraHeaderAttributes(['style' => 'min-width: 120px;'])
                ->extraCellAttributes(['style' => 'min-width: 120px;'])
                ->getStateUsing(fn (Inventario $record): string => $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('material.tipo', fn ($sub) => $sub->where('nombre', 'like', trim($search) . '%'))
                          ->orWhere('subtipo', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                        ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                        ->orderBy('tipo_material.nombre', $direction)
                        ->select('inventario.*');
                }),

            Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->html()
                ->extraHeaderAttributes(['style' => 'min-width: 130px;'])
                ->extraCellAttributes(['style' => 'min-width: 130px;'])
                ->getStateUsing(function (Inventario $record): string {
                    $estado = $record->estado ?? 'Disponible';
                    $colors = match ($estado) {
                        'Disponible' => [
                            'bg' => '#d1fae5',
                            'text' => '#065f46',
                            'border' => '#a7f3d0',
                            'dot' => '#059669',
                            'class' => 'ok',
                        ],
                        'En mantenimiento', 'En Mantenimiento' => [
                            'bg' => '#fef3c7',
                            'text' => '#92400e',
                            'border' => '#fde68a',
                            'dot' => '#d97706',
                            'class' => 'warn',
                        ],
                        'Cambio de Ã¡rea', 'Asignado', 'Reasignado' => [
                            'bg' => '#e0f2fe',
                            'text' => '#075985',
                            'border' => '#bae6fd',
                            'dot' => '#0284c7',
                            'class' => 'info',
                        ],
                        'Solicitud de baja' => [
                            'bg' => '#fef3c7',
                            'text' => '#b45309',
                            'border' => '#fde68a',
                            'dot' => '#f59e0b',
                            'class' => 'warn',
                        ],
                        'Dado de baja', 'DaÃ±ado', 'Baja' => [
                            'bg' => '#fee2e2',
                            'text' => '#991b1b',
                            'border' => '#fecaca',
                            'dot' => '#dc2626',
                            'class' => 'danger',
                        ],
                        default => [
                            'bg' => '#f3f4f6',
                            'text' => '#374151',
                            'border' => '#e5e7eb',
                            'dot' => '#9ca3af',
                            'class' => 'neutral',
                        ],
                    };

                    return '<span class="pill ' . $colors['class'] . '" style="display:inline-flex;align-items:center;gap:6px;padding:3px 9px;border-radius:99px;font-size:12px;font-weight:600;white-space:nowrap;background-color:' . $colors['bg'] . ';color:' . $colors['text'] . ';border:1px solid ' . $colors['border'] . ';">'
                        . '<span style="display:inline-block;width:6px;height:6px;border-radius:50%;background-color:' . $colors['dot'] . ';flex-shrink:0;"></span>'
                        . e($estado)
                        . '</span>';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where('estado', 'like', "%{$search}%");
                }, isIndividual: true)
                ->sortable(),

            Tables\Columns\TextColumn::make('area.nombre')
                ->label('Ãrea')
                ->extraHeaderAttributes(['style' => 'min-width: 180px;'])
                ->extraCellAttributes(['style' => 'min-width: 180px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'])
                ->tooltip(function (Inventario $record): ?string {
                    if ($record->area?->nombre) {
                        return $record->area->nombre;
                    }
                    if (! empty($record->ubicacion_fisica)) {
                        $parts = explode(' > ', $record->ubicacion_fisica);
                        return $parts[1] ?? $parts[0] ?? null;
                    }
                    return null;
                })
                ->getStateUsing(function (Inventario $record): ?string {
                    if ($record->area?->nombre) {
                        return $record->area->nombre;
                    }
                    if (! empty($record->ubicacion_fisica)) {
                        $parts = explode(' > ', $record->ubicacion_fisica);
                        return $parts[1] ?? $parts[0] ?? null;
                    }
                    return 'â€”';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('area', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                          ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                        ->orderBy('area.nombre', $direction)
                        ->select('inventario.*');
                })
                ->placeholder('â€”'),

            Tables\Columns\TextColumn::make('aula')
                ->label('Aula')
                ->extraHeaderAttributes(['style' => 'min-width: 80px;'])
                ->extraCellAttributes(['style' => 'min-width: 80px;'])
                ->getStateUsing(function (Inventario $record): string {
                    return $record->salon?->nombre
                        ?? ($record->especificaciones['aula'] ?? null)
                        ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                        ?? 'â€”';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->whereHas('salon', fn ($sub) => $sub->where('nombre', 'like', "%{$search}%"))
                          ->orWhere('especificaciones->aula', 'like', "%{$search}%")
                          ->orWhere('ubicacion_fisica', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->leftJoin('salones', 'inventario.salon_id', '=', 'salones.id')
                        ->orderBy('salones.nombre', $direction)
                        ->select('inventario.*');
                })
                ->placeholder('â€”'),

            Tables\Columns\TextColumn::make('num_serie')
                ->label('No. de serie')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'])
                ->tooltip(fn (?string $state): ?string => $state)
                ->searchable(isIndividual: true)
                ->sortable()
                ->placeholder('â€”'),

            Tables\Columns\TextColumn::make('ip_address')
                ->label('IP')
                ->extraHeaderAttributes(['style' => 'min-width: 120px;'])
                ->extraCellAttributes(['style' => 'min-width: 120px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? 'â€”')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('ip_address', 'like', "%{$search}%")
                          ->orWhere('especificaciones->ip_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('â€”'),

            Tables\Columns\TextColumn::make('mac_address')
                ->label('MAC')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? 'â€”')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('mac_address', 'like', "%{$search}%")
                          ->orWhere('especificaciones->mac_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('â€”'),
        ];
    }

    public static function aplicarFiltrosTabla(Builder $query, $livewire): Builder
    {
        $desde = $livewire->tableColumnSearches['fecha_desde'] ?? null;
        $hasta = $livewire->tableColumnSearches['fecha_hasta'] ?? null;
        if (filled($desde)) {
            $query->where(function (Builder $q) use ($desde) {
                $q->whereDate('fecha_registro', '>=', $desde)
                  ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '>=', $desde));
            });
        }
        if (filled($hasta)) {
            $query->where(function (Builder $q) use ($hasta) {
                $q->whereDate('fecha_registro', '<=', $hasta)
                  ->orWhere(fn ($sub) => $sub->whereNull('fecha_registro')->whereDate('created_at', '<=', $hasta));
            });
        }
        $estadoResumen = $livewire->tableFilters['estado']['value'] ?? null;
        if (filled($estadoResumen)) {
            $query->where('estado', $estadoResumen);
        }
        return $query;
    }

    public static function esAdmin(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('Administrador')
            || $user->hasRole('admin')
            || in_array($user->tipo_usuario, ['Administrador', 'Admin'], true)
            || ($user->can(RoleEnum::PERM_ACCESS_ADMIN) ?? false);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Buscar por equipo, cÃ³digo, IP, MAC, marca...')
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([])
            ->modifyQueryUsing(fn (Builder $query, $livewire): Builder => static::aplicarFiltrosTabla($query, $livewire))
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerÃ¡n aquÃ­.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('â†“ Exportar')
                    ->color('gray')
                    ->action(fn ($livewire) => self::exportarInventarioExcel($livewire)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Ver detalle')
                    ->extraAttributes(['title' => 'Ver detalle', 'class' => 'icon-btn'])
                    ->slideOver()
                    ->modalHeading(fn (?Inventario $record) => $record?->material?->nombre ?? $record?->subtipo ?? 'Detalle de equipo')
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? $record?->num_serie ?? 'â€”') . ' Â· ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categorÃ­a')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Modificar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de Ã¡rea'], true) && auth()->user()?->can('update', $record))
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_cambio')
                            ->label('Cambio')
                            ->color('info')
                            ->icon('heroicon-m-arrows-right-left')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de Ã¡rea', 'Solicitud de baja'], true))
                            ->modalHeading(fn () => static::esAdmin() ? 'Cambiar Ã¡rea de equipo' : 'Solicitar cambio de Ã¡rea')
                            ->modalDescription(fn () => static::esAdmin() ? 'Se actualizarÃ¡ la ubicaciÃ³n fÃ­sica del equipo de forma inmediata.' : 'La solicitud quedarÃ¡ registrada en espera de revisiÃ³n y aprobaciÃ³n por el Administrador.')
                            ->form(static::getModalCambioAreaFormSchema(static::esAdmin()))
                            ->modalSubmitActionLabel(fn () => static::esAdmin() ? 'Confirmar cambio' : 'Solicitar cambio')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                if (static::esAdmin()) {
                                    $record->cambiarAreaDirecto(
                                        $data['departamento_id'],
                                        $data['area_id'],
                                        $data['aula'] ?? null,
                                        $data['estacion'] ?? null,
                                        $data['motivo'] ?? null,
                                        auth()->id()
                                    );
                                    Notification::make()->title('Ãrea del equipo actualizada correctamente')->success()->send();
                                } else {
                                    $record->solicitarCambioArea(
                                        $data['departamento_id'],
                                        $data['area_id'],
                                        $data['aula'] ?? null,
                                        $data['estacion'] ?? null,
                                        $data['motivo'] ?? null,
                                        auth()->id()
                                    );
                                    Notification::make()->title('Solicitud de cambio de Ã¡rea enviada a revisiÃ³n')->warning()->send();
                                }
                            }),
                        Tables\Actions\Action::make('aprobar_cambio')
                            ->label('âœ“ Aprobar cambio')
                            ->color('success')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de Ã¡rea' && static::esAdmin())
                            ->requiresConfirmation()
                            ->modalHeading('Aprobar cambio de Ã¡rea')
                            ->modalDescription('Â¿Deseas autorizar el cambio de Ã¡rea solicitado? Se actualizarÃ¡ la ubicaciÃ³n del equipo.')
                            ->modalSubmitActionLabel('SÃ­, autorizar cambio')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record) {
                                $record->aprobarCambioArea(auth()->id());
                                Notification::make()->title('Cambio de Ã¡rea aprobado exitosamente')->success()->send();
                            }),
                        Tables\Actions\Action::make('rechazar_cambio')
                            ->label('âœ• Rechazar cambio')
                            ->color('danger')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de Ã¡rea' && static::esAdmin())
                            ->form([
                                Forms\Components\Textarea::make('motivo_rechazo')
                                    ->label('Motivo del rechazo')
                                    ->placeholder('Explica por quÃ© se rechaza el cambio de Ã¡rea...')
                                    ->required(),
                            ])
                            ->modalHeading('Rechazar cambio de Ã¡rea')
                            ->modalDescription('El equipo regresarÃ¡ al estado previo y la solicitud quedarÃ¡ cancelada.')
                            ->modalSubmitActionLabel('Confirmar rechazo')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                $record->rechazarCambioArea($data['motivo_rechazo'] ?? null, auth()->id());
                                Notification::make()->title('Solicitud de cambio de Ã¡rea rechazada')->warning()->send();
                            }),
                        Tables\Actions\Action::make('desde_detalle_baja')
                            ->label('Baja')
                            ->color('danger')
                            ->icon('heroicon-m-arrow-down-circle')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de Ã¡rea'], true))
                            ->modalHeading(fn () => static::esAdmin() ? 'Confirmar baja de equipo' : 'Solicitar baja de equipo')
                            ->modalDescription(fn () => static::esAdmin() ? 'El equipo pasarÃ¡ a estado Dado de baja de manera inmediata.' : 'La solicitud quedarÃ¡ registrada en espera de revisiÃ³n y aprobaciÃ³n por el Administrador.')
                            ->form(static::getModalBajaFormSchema())
                            ->modalSubmitActionLabel(fn () => static::esAdmin() ? 'Confirmar baja' : 'Solicitar baja')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                if (static::esAdmin()) {
                                    $record->darBajaDirecta($data['motivo_baja'], $data['observaciones_baja'], auth()->id());
                                    Notification::make()->title('Equipo dado de baja correctamente')->danger()->send();
                                } else {
                                    $record->solicitarBaja($data['motivo_baja'], $data['observaciones_baja'], auth()->id());
                                    Notification::make()->title('Solicitud de baja enviada a revisiÃ³n')->warning()->send();
                                }
                            }),
                        Tables\Actions\Action::make('aprobar_baja')
                            ->label('âœ“ Aprobar baja')
                            ->color('success')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && static::esAdmin())
                            ->requiresConfirmation()
                            ->modalHeading('Aprobar baja del equipo')
                            ->modalDescription('Â¿Deseas autorizar la baja definitiva de este equipo? El estado pasarÃ¡ a Dado de baja.')
                            ->modalSubmitActionLabel('SÃ­, autorizar baja')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record) {
                                $record->aprobarBaja(auth()->id());
                                Notification::make()->title('Baja aprobada exitosamente')->success()->send();
                            }),
                        Tables\Actions\Action::make('rechazar_baja')
                            ->label('âœ• Rechazar baja')
                            ->color('danger')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && static::esAdmin())
                            ->form([
                                Forms\Components\Textarea::make('motivo_rechazo')
                                    ->label('Motivo del rechazo')
                                    ->placeholder('Explica por quÃ© se rechaza la solicitud de baja...')
                                    ->required(),
                            ])
                            ->modalHeading('Rechazar solicitud de baja')
                            ->modalDescription('El equipo regresarÃ¡ al estado previo y la solicitud quedarÃ¡ cancelada.')
                            ->modalSubmitActionLabel('Confirmar rechazo')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                $record->rechazarBaja($data['motivo_rechazo'] ?? null, auth()->id());
                                Notification::make()->title('Solicitud de baja rechazada')->warning()->send();
                            }),
                        Tables\Actions\Action::make('reactivar_equipo')
                            ->label('Reactivar')
                            ->color('success')
                            ->icon('heroicon-m-arrow-path')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Dado de baja' && static::esAdmin())
                            ->requiresConfirmation()
                            ->modalHeading('Reactivar equipo')
                            ->modalDescription('El equipo volverÃ¡ al estado Disponible y se limpiarÃ¡ la informaciÃ³n de baja.')
                            ->modalSubmitActionLabel('SÃ­, reactivar')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record) {
                                $record->reactivar(auth()->id());
                                Notification::make()->title('Equipo reactivado y disponible')->success()->send();
                            }),
                    ]),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Modificar')
                    ->extraAttributes(['title' => 'Modificar', 'class' => 'icon-btn'])
                    ->modalHeading(fn (?Inventario $record) => 'Editar: ' . ($record?->material?->nombre ?? $record?->subtipo ?? 'equipo'))
                    ->modalDescription(fn (?Inventario $record) => new \Illuminate\Support\HtmlString(
                        'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' Â· ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
                    ))
                    ->slideOver()
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalCancelActionLabel('Cancelar')
                    ->mutateRecordDataUsing(fn (array $data, Inventario $record): array => static::hidratarDatosEdicion($data, $record))
                    ->using(fn (Inventario $record, array $data): Inventario => static::guardarCambiosEquipo($record, $data))
                    ->successNotificationTitle('Registro actualizado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('aprobarMasivo')
                        ->label('Aprobar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (): bool => auth()->user()->can('aprobaciones.aprobar'))
                        ->action(function (Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    if (! $record->aprobado) {
                                        $record->update(['aprobado' => true, 'estado_registro' => 'Aprobado', 'aprobado_por' => auth()->id(), 'fecha_aprobacion' => now()]);
                                    }
                                }
                            });
                            Notification::make()->title('Registros aprobados exitosamente')->success()->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Registro de inventario eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Detalle')
                    ->extraAttributes(['style' => 'display: flex; flex-direction: column;'])
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('InformaciÃ³n')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                Infolists\Components\Section::make('âš ï¸ InformaciÃ³n de baja')
                                    ->icon('heroicon-m-exclamation-triangle')
                                    ->visible(fn (?Inventario $record): bool => in_array($record?->estado, ['Dado de baja', 'Solicitud de baja', 'Baja'], true))
                                    ->schema([
                                        Infolists\Components\TextEntry::make('motivo_baja')
                                            ->label('Motivo de baja')
                                            ->badge()
                                            ->color('danger')
                                            ->placeholder('Sin motivo registrado'),
                                        Infolists\Components\TextEntry::make('fecha_baja')
                                            ->label('Fecha de baja')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('Pendiente de procesar'),
                                        Infolists\Components\TextEntry::make('usuarioBaja.name')
                                            ->label('Dado de baja / Solicitado por')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Sistema'),
                                        Infolists\Components\TextEntry::make('observaciones_baja')
                                            ->label('Observaciones de baja')
                                            ->columnSpanFull()
                                            ->placeholder('Sin observaciones registradas'),
                                    ])
                                    ->columns(3),

                                Infolists\Components\Section::make('IdentificaciÃ³n del Equipo')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('codigo')
                                            ->label('CÃ³digo')
                                            ->fontFamily('mono')
                                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                            ->getStateUsing(fn (Inventario $record): string => $record->codigo ?: ($record->num_serie ?: ('#' . $record->id_inventario)))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('material.nombre')
                                            ->label('Nombre del equipo')
                                            ->icon('heroicon-m-cube')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->nombre ?? $record->subtipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('marca_display')
                                            ->label('Marca')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->marca?->nombre ?? ($record->especificaciones['marca'] ?? null) ?? ($record->especificaciones['cpu_marca'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('modelo_display')
                                            ->label('Modelo')
                                            ->getStateUsing(fn (Inventario $record): string => $record->modelo ?? $record->material?->modelo ?? ($record->especificaciones['modelo'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_serie')
                                            ->label('No. de serie')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-qr-code')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('especificacion_texto_display')
                                            ->label('Especificaciones')
                                            ->getStateUsing(fn (Inventario $record): string => $record->especificacion_texto ?? ($record->especificaciones['especificaciones'] ?? null) ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('subtipo')
                                            ->label('Tipo de equipo')
                                            ->badge()
                                            ->color('primary')
                                            ->getStateUsing(fn (Inventario $record): string => $record->subtipo ?? $record->tipo_equipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('categoria_display')
                                            ->label('CategorÃ­a')
                                            ->badge()
                                            ->color('gray')
                                            ->getStateUsing(fn (Inventario $record): string => $record->material?->tipo?->nombre ?? $record->subtipo ?? 'Por capturar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('estado')
                                            ->label('Estado')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? 'â— ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Disponible' => 'success',
                                                'Asignado' => 'info',
                                                'Reasignado' => 'reassign',
                                                'En Mantenimiento', 'En mantenimiento' => 'warning',
                                                'Solicitud de baja' => 'warning',
                                                'Dado de baja', 'DaÃ±ado', 'Baja' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('sistema_operativo_display')
                                            ->label('Sistema operativo')
                                            ->getStateUsing(fn (Inventario $record): string => $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? null) ?? 'No aplica / Sin SO')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('tipo_propiedad')
                                            ->label('Propiedad')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? 'â— ' . $state : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'Propio' => 'primary',
                                                'Rentado' => 'gray',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('usuario.name')
                                            ->label('Registrado por')
                                            ->icon('heroicon-m-user')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('UbicaciÃ³n y AsignaciÃ³n')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('departamento_display')
                                            ->label('Departamento')
                                            ->icon('heroicon-m-building-office-2')
                                            ->getStateUsing(function (Inventario $record): string {
                                                $depto = $record->departamento ?? $record->area?->departamento;
                                                if ($depto) {
                                                    return $depto->encargado
                                                        ? "{$depto->nombre} (Encargado: {$depto->encargado})"
                                                        : $depto->nombre;
                                                }
                                                if ($record->ubicacion_fisica) {
                                                    return explode(' > ', $record->ubicacion_fisica)[0] ?? 'Por capturar';
                                                }
                                                return 'Por capturar';
                                            }),
                                        Infolists\Components\TextEntry::make('area_display')
                                            ->label('Ãrea')
                                            ->icon('heroicon-m-building-office')
                                            ->getStateUsing(function (Inventario $record): string {
                                                if ($record->area?->nombre) {
                                                    return $record->area->nombre;
                                                }
                                                if ($record->ubicacion_fisica) {
                                                    $parts = explode(' > ', $record->ubicacion_fisica);
                                                    return $parts[1] ?? $parts[0] ?? 'Por capturar';
                                                }
                                                return 'Por capturar';
                                            }),
                                        Infolists\Components\TextEntry::make('aula_display')
                                            ->label('Aula')
                                            ->icon('heroicon-m-academic-cap')
                                            ->getStateUsing(function (Inventario $record): string {
                                                return $record->salon?->nombre
                                                    ?? ($record->especificaciones['aula'] ?? null)
                                                    ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                                                    ?? 'â€”';
                                            }),
                                        Infolists\Components\TextEntry::make('estacion')
                                            ->label('EstaciÃ³n')
                                            ->fontFamily('mono')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('Individual (sin estaciÃ³n)'),
                                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                                            ->label('UbicaciÃ³n fÃ­sica descriptiva')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('fecha_asignacion')
                                            ->label('Fecha de asignaciÃ³n')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_cambio_area')
                                            ->label('Fecha de cambio de Ã¡rea')
                                            ->date('d/m/Y')
                                            ->icon('heroicon-m-calendar-days')
                                            ->placeholder('Por capturar'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Red y Conectividad')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('ip_address')
                                            ->label('DirecciÃ³n IP')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-globe-alt')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('mac_address')
                                            ->label('DirecciÃ³n MAC')
                                            ->fontFamily('mono')
                                            ->icon('heroicon-m-signal')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('procesador')
                                            ->label('Procesador')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->procesador ?? ($record->especificaciones['procesador'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('ram')
                                            ->label('Memoria RAM')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->ram ?? ($record->especificaciones['ram'] ?? null))
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('disco')
                                            ->label('Disco duro')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->disco ?? ($record->especificaciones['disco'] ?? null))
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                Infolists\Components\Section::make('Componentes de la EstaciÃ³n')
                                    ->collapsible()
                                    ->visible(fn (?Inventario $record): bool => filled($record?->estacion))
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('componentes_estacion')
                                            ->label('')
                                            ->getStateUsing(fn (?Inventario $record) => filled($record?->estacion)
                                                ? Inventario::with(['material.marca'])->where('estacion', $record->estacion)->orderBy('id_inventario')->get()
                                                : [])
                                            ->schema([
                                                Infolists\Components\TextEntry::make('nombre_componente')
                                                    ->label('Componente')
                                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->material?->nombre ?? $comp->subtipo ?? 'Componente'),
                                                Infolists\Components\TextEntry::make('codigo')
                                                    ->label('CÃ³digo')
                                                    ->fontFamily('mono')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->codigo ?: ('#' . $comp->id_inventario)),
                                                Infolists\Components\TextEntry::make('num_serie')
                                                    ->label('No. de serie (S/N)')
                                                    ->fontFamily('mono')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->num_serie ?: 'â€”'),
                                                Infolists\Components\TextEntry::make('marca_comp')
                                                    ->label('Marca')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->material?->marca?->nombre ?? ($comp->especificaciones['marca'] ?? 'â€”')),
                                                Infolists\Components\TextEntry::make('modelo_comp')
                                                    ->label('Modelo')
                                                    ->getStateUsing(fn (Inventario $comp): string => $comp->modelo ?? $comp->material?->modelo ?? ($comp->especificaciones['modelo'] ?? 'â€”')),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),
                                    ]),

                                Infolists\Components\Section::make('Observaciones')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('observaciones_generales')
                                            ->label('Observaciones generales')
                                            ->placeholder('Por capturar')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Historial')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('historial')
                                    ->label('')
                                    ->getStateUsing(fn (?Inventario $record) => $record
                                        ? \App\Models\HistorialEstado::with('usuario')
                                            ->where('id_inventario', $record->id_inventario)
                                            ->orderByDesc('fecha_cambio')
                                            ->orderByDesc('id_historial')
                                            ->get()
                                        : [])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('fecha_cambio')
                                            ->label('Fecha')
                                            ->icon('heroicon-m-calendar')
                                            ->dateTime('d/m/Y H:i'),
                                        Infolists\Components\TextEntry::make('usuario.name')
                                            ->label('Usuario')
                                            ->icon('heroicon-m-user')
                                            ->default('Administrador')
                                            ->getStateUsing(fn (\App\Models\HistorialEstado $entry): string => $entry->usuario?->name ?? 'Administrador'),
                                        Infolists\Components\TextEntry::make('motivo_cambio')
                                            ->label('Movimiento')
                                            ->icon('heroicon-m-arrow-path')
                                            ->columnSpanFull()
                                            ->placeholder('Sin motivo registrado'),
                                        Infolists\Components\TextEntry::make('ubicacion_fisica')
                                            ->label('UbicaciÃ³n registrada')
                                            ->icon('heroicon-m-map-pin')
                                            ->columnSpanFull()
                                            ->placeholder('Sin ubicaciÃ³n registrada')
                                            ->visible(fn (?string $state): bool => filled($state)),
                                        Infolists\Components\TextEntry::make('estado_anterior')
                                            ->label('Estado anterior')
                                            ->badge()
                                            ->placeholder('â€”')
                                            ->visible(fn (\App\Models\HistorialEstado $entry): bool => filled($entry->estado_anterior)),
                                        Infolists\Components\TextEntry::make('estado_nuevo')
                                            ->label('Estado nuevo')
                                            ->badge()
                                            ->visible(fn (\App\Models\HistorialEstado $entry): bool => filled($entry->estado_nuevo)),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->placeholder('Sin historial de cambios registrado'),
                            ]),

                        Infolists\Components\Tabs\Tab::make('DocumentaciÃ³n')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Infolists\Components\Section::make('Factura y proveedor')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('proveedor.nombre_empresa')
                                            ->label('Proveedor')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('num_factura')
                                            ->label('Factura')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('fecha_factura')
                                            ->label('Fecha de factura')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                        Infolists\Components\TextEntry::make('tipo_impresora')
                                            ->label('Tipo de impresora')
                                            ->badge()
                                            ->color('info')
                                            ->getStateUsing(fn (Inventario $record): ?string => $record->tipo_impresora ?? ($record->especificaciones['tipo_impresora'] ?? null))
                                            ->placeholder('No especificado')
                                            ->visible(fn (?Inventario $record): bool => filled($record?->tipo_impresora)
                                                || filled($record?->especificaciones['tipo_impresora'] ?? null)
                                                || ($record?->especificaciones['tipo_equipo'] ?? '') === 'imp'
                                                || str_contains(strtolower(($record?->subtipo ?? '') . ' ' . ($record?->tipo_equipo ?? '') . ' ' . ($record?->material?->nombre ?? '')), 'impresora')),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('GarantÃ­a')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('garantia_estado')
                                            ->label('Estado de garantÃ­a')
                                            ->badge()
                                            ->formatStateUsing(fn (?string $state): string => $state ? 'â— ' . ucfirst(str_replace('_', ' ', $state)) : 'Por capturar')
                                            ->color(fn (?string $state): string => match ($state) {
                                                'vigente' => 'success',
                                                'por_vencer' => 'warning',
                                                'vencida' => 'danger',
                                                default => 'gray',
                                            }),
                                        Infolists\Components\TextEntry::make('garantia_fecha_fin')
                                            ->label('Fin de garantÃ­a')
                                            ->date('d/m/Y')
                                            ->placeholder('Por capturar'),
                                    ])
                                    ->columns(2),

                                Infolists\Components\Section::make('Archivos adjuntos')
                                    ->collapsible()
                                    ->schema([
                                        Infolists\Components\TextEntry::make('archivos')
                                            ->label('')
                                            ->default('Sin archivos digitales adjuntos')
                                            ->getStateUsing(fn () => 'Sin archivos digitales adjuntos')
                                            ->color('gray'),
                                    ]),
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
            'index' => Pages\ListInventarios::route('/'),
            'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }

    public static function obtenerOpcionesFiltrosDashboard(): array
    {
        $categoriasFiltro = ['' => 'Todas'];
        try {
            $categoriasFiltro += TipoMaterial::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        $areasFiltro = ['' => 'Todas'];
        try {
            $areasFiltro += Area::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        return [$categoriasFiltro, $areasFiltro];
    }

    public static function obtenerInventariosDashboard(array $searches): Collection
    {
        $invQuery = Inventario::with(['material.marca', 'material.tipo', 'proveedor', 'usuario', 'area', 'departamento', 'salon']);

        if (filled($searches['num_serie'] ?? null)) {
            $termSerie = '%' . trim($searches['num_serie']) . '%';
            $invQuery->where(function ($q) use ($termSerie) {
                $q->where('num_serie', 'like', $termSerie)
                  ->orWhere('codigo', 'like', $termSerie);
            });
        }
        if (filled($searches['material_nombre'] ?? null)) {
            $termMat = '%' . trim($searches['material_nombre']) . '%';
            $invQuery->where(function ($q) use ($termMat) {
                $q->whereHas('material', fn ($m) => $m->where('nombre', 'like', $termMat))
                  ->orWhere('subtipo', 'like', $termMat);
            });
        }
        if (filled($searches['material_tipo_nombre'] ?? null)) {
            $invQuery->whereHas('material.tipo', fn ($q) => $q->where('nombre', trim($searches['material_tipo_nombre'])));
        }
        if (filled($searches['estado'] ?? null)) {
            $invQuery->where('estado', $searches['estado']);
        }
        if (filled($searches['ubicacion_fisica'] ?? null)) {
            $termArea = '%' . trim($searches['ubicacion_fisica']) . '%';
            $invQuery->where(function ($q) use ($termArea) {
                $q->where('ubicacion_fisica', 'like', $termArea)
                  ->orWhereHas('area', fn ($a) => $a->where('nombre', 'like', $termArea));
            });
        }

        if (filled($searches['busqueda'] ?? null)) {
            $term = '%' . trim($searches['busqueda']) . '%';
            $invQuery->where(function ($q) use ($term) {
                $q->where('num_serie', 'like', $term)
                    ->orWhere('codigo', 'like', $term)
                    ->orWhere('subtipo', 'like', $term)
                    ->orWhere('modelo', 'like', $term)
                    ->orWhere('ubicacion_fisica', 'like', $term)
                    ->orWhere('mac_address', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhereHas('area', fn ($a) => $a->where('nombre', 'like', $term))
                    ->orWhereHas('material', fn ($m) => $m->where('nombre', 'like', $term)
                        ->orWhereHas('marca', fn ($b) => $b->where('nombre', 'like', $term)));
            });
        }
        if (filled($searches['fecha_desde'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '>=', $searches['fecha_desde']);
        }
        if (filled($searches['fecha_hasta'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '<=', $searches['fecha_hasta']);
        }

        return $invQuery->latest('created_at')->take(50)->get();
    }

    public static function obtenerCategoriasTop(): array
    {
        $todasCategorias = Inventario::query()
            ->join('material', 'inventario.id_producto', '=', 'material.id_producto')
            ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
            ->selectRaw("COALESCE(tipo_material.nombre, 'General') as cat, count(*) as total")
            ->groupBy('cat')
            ->orderByDesc('total')
            ->pluck('total', 'cat')
            ->toArray();

        $top = [];
        $otras = 0;
        $idx = 0;
        foreach ($todasCategorias as $catNom => $catCant) {
            if ($idx < 8) {
                $top[$catNom] = $catCant;
            } else {
                $otras += $catCant;
            }
            $idx++;
        }
        if ($otras > 0) {
            $top['Otras categorÃ­as'] = $otras;
        }

        return $top;
    }

    public static function exportarInventarioExcel($livewire = null): ?\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = ($livewire && method_exists($livewire, 'getFilteredTableQuery'))
            ? $livewire->getFilteredTableQuery()
            : Inventario::query();

        $registros = $query
            ->with(['material.tipo', 'departamento', 'area.departamento', 'salon'])
            ->latest('created_at')
            ->get();

        $headers = [
            'Departamento',
            'CÃ³digo',
            'Equipo',
            'CategorÃ­a',
            'Estado',
            'Ãrea',
            'Aula',
            'No. de serie',
            'IP',
            'MAC',
        ];

        $rows = [];
        foreach ($registros as $record) {
            $depto = $record->departamento?->nombre
                ?? $record->area?->departamento?->nombre
                ?? ($record->ubicacion_fisica ? (explode(' > ', $record->ubicacion_fisica)[0] ?? null) : null)
                ?? 'â€”';

            $codigo = ! empty($record->codigo)
                ? $record->codigo
                : ($record->num_serie ?: ('#' . ($record->id_inventario ?? $record->id)));

            $equipo = $record->material?->nombre ?? $record->subtipo ?? 'Equipo';
            $categoria = $record->material?->tipo?->nombre ?? $record->subtipo ?? 'General';
            $estado = $record->estado ?? 'Disponible';

            $area = $record->area?->nombre;
            if (! $area && ! empty($record->ubicacion_fisica)) {
                $parts = explode(' > ', $record->ubicacion_fisica);
                $area = $parts[1] ?? $parts[0] ?? null;
            }
            $area = $area ?: 'â€”';

            $aula = $record->salon?->nombre
                ?? ($record->especificaciones['aula'] ?? null)
                ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                ?? 'â€”';

            $numSerie = $record->num_serie ?: 'â€”';
            $ip = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? 'â€”';
            $mac = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? 'â€”';

            $rows[] = [
                $depto,
                $codigo,
                $equipo,
                $categoria,
                $estado,
                $area,
                $aula,
                $numSerie,
                $ip,
                $mac,
            ];
        }

        if (empty($rows)) {
            $rows[] = ['Sin registros', 'â€”', 'â€”', 'â€”', 'â€”', 'â€”', 'â€”', 'â€”', 'â€”', 'â€”'];
        }

        $fileName = 'inventario-sigem-' . now()->format('Y-m-d-His') . '.xlsx';

        return \App\Support\SigemExcelExporter::export(
            'Inventario de Equipos y Materiales',
            $headers,
            $rows,
            $fileName
        );
    }
}
