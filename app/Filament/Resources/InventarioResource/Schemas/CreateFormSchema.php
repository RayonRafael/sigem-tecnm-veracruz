<?php

namespace App\Filament\Resources\InventarioResource\Schemas;

use App\Models\Area;
use App\Models\Departamento;
use App\Support\UbicacionHelper;
use Filament\Forms;
use Illuminate\Support\HtmlString;

class CreateFormSchema
{
    public static function make(): array
    {
        return [
            // 1. "¿Qué vas a registrar?" (grid de 12 cards)
            Forms\Components\Section::make('¿Qué vas a registrar?')
                ->description('Selecciona el tipo de equipo a registrar')
                ->icon('heroicon-m-squares-2x2')
                ->schema([
                    Forms\Components\ViewField::make('tipo_equipo')
                        ->view('filament.forms.components.tipo-equipo-grid')
                        ->default('pc')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $map = [
                                'pc' => 'Computadora',
                                'aio' => 'All in One',
                                'lap' => 'Laptop',
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

                            // Ajustar subtipo en modo PC
                            if ($state === 'pc') {
                                $set('subtipo', 'CPU');
                            }
                        }),
                ])
                ->columnSpanFull(),

            // 2. Banner informativo (solo para pc y aio)
            Forms\Components\Placeholder::make('banner_informativo')
                ->label('')
                ->visible(fn (Forms\Get $get) => in_array($get('tipo_equipo') ?? 'pc', ['pc', 'aio']))
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

            // 3. 📍 Ubicación
            Forms\Components\Section::make('📍 Ubicación')
                ->description('Ubicación física estructurada del equipo dentro de la institución')
                ->icon('heroicon-m-map-pin')
                ->schema([
                    Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                        ->schema([
                            Forms\Components\Select::make('departamento_id')
                                ->label('Departamento')
                                ->placeholder('Selecciona departamento...')
                                ->options(fn (): array => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento')->toArray())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $set('area_id', null);
                                    $encargado = $state ? Departamento::find($state)?->encargado : null;
                                    $set('jefe_departamento', $encargado ?? 'Sin jefe asignado');
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-building-office'),

                            Forms\Components\TextInput::make('jefe_departamento')
                                ->label('Jefe del Departamento')
                                ->placeholder('Selecciona departamento...')
                                ->disabled()
                                ->dehydrated(false)
                                ->prefixIcon('heroicon-m-user'),

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
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                })
                                ->prefixIcon('heroicon-m-rectangle-group'),

                            Forms\Components\TextInput::make('especificaciones.aula')
                                ->label('Aula')
                                ->placeholder('ej. E101')
                                ->maxLength(5)
                                ->helperText('Letra del edificio + planta + número. Déjalo vacío si el equipo no está en un aula.')
                                ->prefixIcon('heroicon-m-academic-cap')
                                ->lazy()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    UbicacionHelper::actualizarDescriptiva($set, $get);
                                }),
                        ]),
                ])
                ->columnSpanFull(),

            // 4. Cards por componente
            ...ComponentCardsSchema::make(),

            // 5. Datos Administrativos
            Forms\Components\Section::make('⚙️ Datos Administrativos (opcional)')
                ->description('Detalles administrativos y de control interno')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\Grid::make(3)
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
                                ->required(),

                            Forms\Components\DatePicker::make('fecha_registro')
                                ->label('Fecha de registro')
                                ->default(now())
                                ->required(),
                        ]),

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
