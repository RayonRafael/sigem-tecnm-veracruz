<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use Filament\Forms;

class ComponentCardsSchema
{
    use InventarioFormFields;

    public static function make(): array
    {
        return [
            // Card 1 para PC: CPU
            Forms\Components\Section::make('🗄️ CPU')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. B4HPS22')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.cpu_marca', fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc'),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder('ej. OptiPlex 7040')
                                ->prefixIcon('heroicon-m-tag')
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. Equipo completo, formateado')
                                ->rows(2)
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                                ->columnSpanFull(),
                        ]),

                    static::getDatosTecnicosSection(dehydratedCondition: fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc'),
                ])
                ->columnSpanFull(),

            // Card 2 para PC: Monitor (con checkbox "No tiene")
            Forms\Components\Section::make('🖥️ Monitor')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc')
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_monitor') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_monitor') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_monitor')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_monitor') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.monitor_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. PE17H9NQ800371J')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc' && ! $get('especificaciones.no_tiene_monitor'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.monitor_marca',
                                fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'pc' && ! $get('especificaciones.no_tiene_monitor'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor')
                            ),

                            Forms\Components\TextInput::make('especificaciones.monitor_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. E2216H')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.monitor_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor')
                            ),

                            Forms\Components\Textarea::make('especificaciones.monitor_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. DELL FLAT PANEL MONITOR Dell E2216H')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_monitor'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card 1 para All in One
            Forms\Components\Section::make('🖥️ All in One')
                ->visible(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. 3CG4251XYZ')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.marca', fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio'),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder('ej. ProOne 400 G5')
                                ->prefixIcon('heroicon-m-tag')
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. AIO HP + teclado y mouse')
                                ->rows(2)
                                ->dehydrated(fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio')
                                ->columnSpanFull(),
                        ]),

                    static::getDatosTecnicosSection(dehydratedCondition: fn (Forms\Get $get) => ($get('tipo_equipo') ?? 'pc') === 'aio'),
                ])
                ->columnSpanFull(),

            // Card Teclado (compartida para PC y AIO, con checkbox "No tiene")
            Forms\Components\Section::make('⌨️ Teclado')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_teclado') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_teclado') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_teclado')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_teclado') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.teclado_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. CN-0GDPPG-71581-…')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_teclado'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.teclado_marca',
                                fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_teclado'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado')
                            ),

                            Forms\Components\TextInput::make('especificaciones.teclado_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. KB216')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.teclado_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado')
                            ),

                            Forms\Components\Textarea::make('especificaciones.teclado_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. DELL USB KEYBOARD')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_teclado'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card Mouse (compartida para PC y AIO, con checkbox "No tiene")
            Forms\Components\Section::make('🖱️ Mouse')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->extraAttributes(fn (Forms\Get $get) => [
                    'class' => 'relative transition-all duration-200' . ($get('especificaciones.no_tiene_mouse') ? ' sigem-card-atenuada' : ''),
                    'style' => $get('especificaciones.no_tiene_mouse') ? 'opacity: 0.4;' : '',
                ])
                ->schema([
                    Forms\Components\Checkbox::make('especificaciones.no_tiene_mouse')
                        ->label('No tiene')
                        ->live()
                        ->extraAttributes([
                            'style' => 'position: absolute; top: 14px; right: 20px; width: auto; z-index: 20; pointer-events: auto !important; cursor: pointer;',
                        ]),

                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(fn (Forms\Get $get) => [
                            'style' => $get('especificaciones.no_tiene_mouse') ? 'pointer-events: none;' : '',
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('especificaciones.mouse_sn')
                                ->label('No. de serie (S/N)')
                                ->placeholder('ej. MOC5UL-7412')
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_mouse'))
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->maxLength(100),

                            static::getMarcaSelectField(
                                'especificaciones.mouse_marca',
                                fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']) && ! $get('especificaciones.no_tiene_mouse'),
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse')
                            ),

                            Forms\Components\TextInput::make('especificaciones.mouse_modelo')
                                ->label('Modelo')
                                ->placeholder('ej. MS116')
                                ->prefixIcon('heroicon-m-tag')
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->maxLength(100),

                            static::getEspecificacionesField(
                                'especificaciones.mouse_specs',
                                fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse')
                            ),

                            Forms\Components\Textarea::make('especificaciones.mouse_desc')
                                ->label('Descripción del equipo')
                                ->placeholder('ej. HP 125 WIRED MOUSE')
                                ->rows(2)
                                ->disabled(fn (Forms\Get $get) => (bool) $get('especificaciones.no_tiene_mouse'))
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),

            // Card para artículos individuales (lap, cpu, mon, tec, mou, imp, pro, nob, swi, otro)
            Forms\Components\Section::make(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                'lap' => '💻 Laptop',
                'cpu' => '🗄️ CPU',
                'mon' => '🖥 Monitor',
                'tec' => '⌨️ Teclado',
                'mou' => '🖱️ Mouse',
                'imp' => '🖨️ Impresora',
                'pro' => '📽️ Proyector',
                'nob' => '🔋 No-Break',
                'swi' => '🔌 Switch de red',
                'otro' => '📦 Otro artículo',
                default => '📦 Artículo de inventario',
            })
                ->visible(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->schema([
                            Forms\Components\TextInput::make('num_serie')
                                ->label('No. de serie (S/N)')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. PE17H9NQ800371J',
                                    'cpu' => 'ej. B4HPS22',
                                    'tec' => 'ej. CN-0GDPPG-71581-…',
                                    'mou' => 'ej. MOC5UL-7412',
                                    'lap' => 'ej. 5CD1234ABC',
                                    'imp' => 'ej. HP-LJ4103-ADM01',
                                    'pro' => 'ej. PROJ-001',
                                    'nob' => 'ej. NB-2024-001',
                                    'swi' => 'ej. SW-2024-001',
                                    default => 'ej. SN-001',
                                })
                                ->prefixIcon('heroicon-m-qr-code')
                                ->required(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                                ->dehydrated(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                                ->maxLength(100),

                            static::getMarcaSelectField('especificaciones.marca', fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio'])),

                            Forms\Components\TextInput::make('modelo')
                                ->label('Modelo')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. E2216H',
                                    'cpu' => 'ej. OptiPlex 7040',
                                    'tec' => 'ej. KB216',
                                    'mou' => 'ej. MS116',
                                    'lap' => 'ej. EliteBook 840 G7',
                                    'imp' => 'ej. LaserJet Pro MFP',
                                    'pro' => 'ej. EB-X51',
                                    'nob' => 'ej. Smart-UPS 1500',
                                    'swi' => 'ej. SG110-24',
                                    default => 'ej. Modelo',
                                })
                                ->prefixIcon('heroicon-m-tag')
                                ->dehydrated(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                                ->maxLength(100),

                            static::getEspecificacionesField('especificacion_texto'),

                            // Campo específico para Impresora
                            Forms\Components\Select::make('tipo_impresora')
                                ->label('Tipo de impresora')
                                ->options([
                                    'Láser' => 'Láser',
                                    'Inyección' => 'Inyección',
                                ])
                                ->placeholder('Selecciona...')
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'imp')
                                ->required(fn (Forms\Get $get) => $get('tipo_equipo') === 'imp'),

                            // Campo específico para Switch de red
                            Forms\Components\TextInput::make('especificaciones.num_puertos')
                                ->label('Número de puertos')
                                ->placeholder('ej. 24 puertos Gigabit PoE')
                                ->prefixIcon('heroicon-m-hashtag')
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'swi')
                                ->maxLength(50),

                            Forms\Components\Select::make('id_producto')
                                ->label('Artículo del catálogo (opcional)')
                                ->placeholder('Selecciona o deja que el sistema lo registre...')
                                ->relationship('material', 'nombre')
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get) => $get('tipo_equipo') === 'otro'),

                            Forms\Components\Textarea::make('observaciones_generales')
                                ->label('Descripción del equipo')
                                ->placeholder(fn (Forms\Get $get) => match ($get('tipo_equipo')) {
                                    'mon' => 'ej. DELL FLAT PANEL',
                                    'cpu' => 'ej. Equipo completo, formateado',
                                    'tec' => 'ej. DELL USB KEYBOARD',
                                    'mou' => 'ej. HP 125 WIRED MOUSE',
                                    'lap' => 'ej. Laptop HP EliteBook',
                                    'imp' => 'ej. Impresora Multifuncional HP',
                                    'pro' => 'ej. Epson PowerLite',
                                    'nob' => 'ej. APC No-Break',
                                    'swi' => 'ej. Cisco 24-Port',
                                    default => 'ej. Descripción del artículo',
                                })
                                ->rows(2)
                                ->dehydrated(fn (Forms\Get $get) => ! in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
                                ->columnSpanFull(),
                        ]),

                    // Datos técnicos expandibles para Laptop o CPU individual
                    static::getDatosTecnicosSection(dehydratedCondition: fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['lap', 'cpu']))
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['lap', 'cpu'])),

                    // Datos de red expandibles para Impresora o Switch de red
                    static::getDatosRedSection(dehydratedCondition: fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['imp', 'swi']))
                        ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo'), ['imp', 'swi'])),
                ])
                ->columnSpanFull(),
        ];
    }
}
