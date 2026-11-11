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

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'num_serie';

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $modelLabel = 'registro de inventario';

    protected static ?string $pluralModelLabel = 'registros de inventario';

    protected static ?string $navigationGroup = 'Gestión de inventario';

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
            return "Se registraron {$total} equipos como estación {$estacion}";
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

        // 2. Laptop / Portátil
        if (
            str_contains($combined, 'laptop') ||
            str_contains($combined, 'notebook') ||
            str_contains($combined, 'portátil') ||
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
            str_contains($combined, 'ratón') ||
            str_contains($combined, 'raton') ||
            $componente === 'mou' ||
            $tipoEquipo === 'mou'
        ) {
            return 'periferico';
        }

        // 8. Proyector
        if (
            str_contains($combined, 'proyector') ||
            str_contains($combined, 'cañón') ||
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
        return \App\Filament\Resources\InventarioResource\Schemas\EditFormTabs::make();
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
        $data['procesador'] = $record->procesador ?? ($record->especificaciones['procesador'] ?? '—');
        $data['ram'] = $record->ram ?? ($record->especificaciones['ram'] ?? '—');
        $data['disco'] = $record->disco ?? ($record->especificaciones['disco'] ?? '—');
        $data['sistema_operativo'] = $record->sistema_operativo ?? ($record->especificaciones['sistema_operativo'] ?? '—');
        $data['tipo_impresora'] = $record->tipo_impresora ?? ($record->especificaciones['tipo_impresora'] ?? '—');
        $data['num_puertos'] = $record->especificaciones['num_puertos'] ?? '—';
        $data['ip_address'] = $record->ip_address ?? ($record->especificaciones['ip_address'] ?? '');
        $data['mac_address'] = $record->mac_address ?? ($record->especificaciones['mac_address'] ?? '');

        return $data;
    }

    public static function guardarCambiosEquipo(Inventario $record, array $data): Inventario
    {
        return DB::transaction(function () use ($record, $data) {
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

            // 5. Área
            if (isset($data['area_id']) && filled($data['area_id'])) {
                $record->area_id = $data['area_id'];
                $area = Area::find($data['area_id']);
                if ($area && $area->id_departamento) {
                    $record->departamento_id = $area->id_departamento;
                }
            }

            // 6. Datos técnicos, modelo y número de serie
            $specs = $record->especificaciones ?? [];
            foreach (['num_serie', 'modelo', 'procesador', 'ram', 'disco', 'sistema_operativo'] as $campo) {
                if (array_key_exists($campo, $data)) {
                    $val = filled($data[$campo]) ? trim((string) $data[$campo]) : null;
                    $record->{$campo} = $val;
                    if ($val !== null) {
                        $specs[$campo] = $val;
                    } else {
                        unset($specs[$campo]);
                    }
                }
            }
            if (isset($data['modelo']) && filled($data['modelo']) && $record->material) {
                $record->material->update(['modelo' => trim((string) $data['modelo'])]);
            }
            $record->especificaciones = $specs;

            // 7. Fill other allowable fields
            $record->fill($data);
            $record->save();

            return $record;
        });
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
                        ?? '—';
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
                ->placeholder('—'),

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
                ->label('Categoría')
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
                        'Cambio de área', 'Asignado', 'Reasignado' => [
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
                        'Dado de baja', 'Dañado', 'Baja' => [
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
                ->label('Área')
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
                    return '—';
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
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('aula')
                ->label('Aula')
                ->extraHeaderAttributes(['style' => 'min-width: 80px;'])
                ->extraCellAttributes(['style' => 'min-width: 80px;'])
                ->getStateUsing(function (Inventario $record): string {
                    return $record->salon?->nombre
                        ?? ($record->especificaciones['aula'] ?? null)
                        ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $record->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                        ?? '—';
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
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('num_serie')
                ->label('No. de serie')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;'])
                ->tooltip(fn (?string $state): ?string => $state)
                ->searchable(isIndividual: true)
                ->sortable()
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('ip_address')
                ->label('IP')
                ->extraHeaderAttributes(['style' => 'min-width: 120px;'])
                ->extraCellAttributes(['style' => 'min-width: 120px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->ip_address ?? ($record->especificaciones['ip_address'] ?? null) ?? '—')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('ip_address', 'like', "%{$search}%")
                          ->orWhere('especificaciones->ip_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('—'),

            Tables\Columns\TextColumn::make('mac_address')
                ->label('MAC')
                ->extraHeaderAttributes(['style' => 'min-width: 150px;'])
                ->extraCellAttributes(['style' => 'min-width: 150px; white-space: nowrap;'])
                ->getStateUsing(fn (Inventario $record): string => $record->mac_address ?? ($record->especificaciones['mac_address'] ?? null) ?? '—')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $q) use ($search) {
                        $q->where('mac_address', 'like', "%{$search}%")
                          ->orWhere('especificaciones->mac_address', 'like', "%{$search}%");
                    });
                }, isIndividual: true)
                ->sortable()
                ->placeholder('—'),
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
            ->searchPlaceholder('Buscar por equipo, código, IP, MAC, marca...')
            ->defaultPaginationPageOption(fn () => \App\Support\SigemPagination::getPageOption())
            ->paginationPageOptions(fn () => \App\Support\SigemPagination::getPageOptions())
            ->filters([])
            ->modifyQueryUsing(fn (Builder $query, $livewire): Builder => static::aplicarFiltrosTabla($query, $livewire))
            ->emptyStateHeading('No hay registros')
            ->emptyStateDescription('Cuando se creen registros, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->recordUrl(null)
            ->recordAction(null)
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('↓ Exportar')
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
                        'ID: ' . e($record?->codigo ?? $record?->num_serie ?? '—') . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'Sin categoría')
                    ))
                    ->modalCancelActionLabel('Cerrar')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('desde_detalle_editar')
                            ->label('Modificar')
                            ->color('warning')
                            ->icon('heroicon-m-pencil-square')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de área'], true) && auth()->user()?->can('update', $record))
                            ->alpineClickHandler(fn (?Inventario $record) => "\$wire.replaceMountedTableAction('edit', '{$record?->getKey()}')"),
                        Tables\Actions\Action::make('desde_detalle_cambio')
                            ->label('Cambio')
                            ->color('info')
                            ->icon('heroicon-m-arrows-right-left')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Cambio de área', 'Solicitud de baja'], true))
                            ->modalHeading(fn () => static::esAdmin() ? 'Cambiar área de equipo' : 'Solicitar cambio de área')
                            ->modalDescription(fn () => static::esAdmin() ? 'Se actualizará la ubicación física del equipo de forma inmediata.' : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
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
                                    Notification::make()->title('Área del equipo actualizada correctamente')->success()->send();
                                } else {
                                    $record->solicitarCambioArea(
                                        $data['departamento_id'],
                                        $data['area_id'],
                                        $data['aula'] ?? null,
                                        $data['estacion'] ?? null,
                                        $data['motivo'] ?? null,
                                        auth()->id()
                                    );
                                    Notification::make()->title('Solicitud de cambio de área enviada a revisión')->warning()->send();
                                }
                            }),
                        Tables\Actions\Action::make('aprobar_cambio')
                            ->label('✓ Aprobar cambio')
                            ->color('success')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de área' && static::esAdmin())
                            ->requiresConfirmation()
                            ->modalHeading('Aprobar cambio de área')
                            ->modalDescription('¿Deseas autorizar el cambio de área solicitado? Se actualizará la ubicación del equipo.')
                            ->modalSubmitActionLabel('Sí, autorizar cambio')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record) {
                                $record->aprobarCambioArea(auth()->id());
                                Notification::make()->title('Cambio de área aprobado exitosamente')->success()->send();
                            }),
                        Tables\Actions\Action::make('rechazar_cambio')
                            ->label('✕ Rechazar cambio')
                            ->color('danger')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Cambio de área' && static::esAdmin())
                            ->form([
                                Forms\Components\Textarea::make('motivo_rechazo')
                                    ->label('Motivo del rechazo')
                                    ->placeholder('Explica por qué se rechaza el cambio de área...')
                                    ->required(),
                            ])
                            ->modalHeading('Rechazar cambio de área')
                            ->modalDescription('El equipo regresará al estado previo y la solicitud quedará cancelada.')
                            ->modalSubmitActionLabel('Confirmar rechazo')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                $record->rechazarCambioArea($data['motivo_rechazo'] ?? null, auth()->id());
                                Notification::make()->title('Solicitud de cambio de área rechazada')->warning()->send();
                            }),
                        Tables\Actions\Action::make('desde_detalle_baja')
                            ->label('Baja')
                            ->color('danger')
                            ->icon('heroicon-m-arrow-down-circle')
                            ->visible(fn (?Inventario $record) => $record && !in_array($record->estado, ['Dado de baja', 'Solicitud de baja', 'Cambio de área'], true))
                            ->modalHeading(fn () => static::esAdmin() ? 'Confirmar baja de equipo' : 'Solicitar baja de equipo')
                            ->modalDescription(fn () => static::esAdmin() ? 'El equipo pasará a estado Dado de baja de manera inmediata.' : 'La solicitud quedará registrada en espera de revisión y aprobación por el Administrador.')
                            ->form(static::getModalBajaFormSchema())
                            ->modalSubmitActionLabel(fn () => static::esAdmin() ? 'Confirmar baja' : 'Solicitar baja')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record, array $data) {
                                $datosAdicionales = static::extraerDatosAdicionalesBaja($data);
                                if (static::esAdmin()) {
                                    $record->darBajaDirecta($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                                    Notification::make()->title('Equipo dado de baja correctamente')->danger()->send();
                                } else {
                                    $record->solicitarBaja($data['motivo_baja'], $data['observaciones_baja'], auth()->id(), $datosAdicionales);
                                    Notification::make()->title('Solicitud de baja enviada a revisión')->warning()->send();
                                }
                            }),
                        Tables\Actions\Action::make('aprobar_baja')
                            ->label('✓ Aprobar baja')
                            ->color('success')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && static::esAdmin())
                            ->requiresConfirmation()
                            ->modalHeading('Aprobar baja del equipo')
                            ->modalDescription('¿Deseas autorizar la baja definitiva de este equipo? El estado pasará a Dado de baja.')
                            ->modalSubmitActionLabel('Sí, autorizar baja')
                            ->modalCancelActionLabel('Cancelar')
                            ->action(function (Inventario $record) {
                                $record->aprobarBaja(auth()->id());
                                Notification::make()->title('Baja aprobada exitosamente')->success()->send();
                            }),
                        Tables\Actions\Action::make('rechazar_baja')
                            ->label('✕ Rechazar baja')
                            ->color('danger')
                            ->visible(fn (?Inventario $record) => $record && $record->estado === 'Solicitud de baja' && static::esAdmin())
                            ->form([
                                Forms\Components\Textarea::make('motivo_rechazo')
                                    ->label('Motivo del rechazo')
                                    ->placeholder('Explica por qué se rechaza la solicitud de baja...')
                                    ->required(),
                            ])
                            ->modalHeading('Rechazar solicitud de baja')
                            ->modalDescription('El equipo regresará al estado previo y la solicitud quedará cancelada.')
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
                            ->modalDescription('El equipo volverá al estado Disponible y se limpiará la información de baja.')
                            ->modalSubmitActionLabel('Sí, reactivar')
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
                        'ID: ' . e($record?->codigo ?? ('#' . $record?->id_inventario)) . ' · ' . e($record?->material?->tipo?->nombre ?? $record?->subtipo ?? 'General')
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
                    Tables\Actions\BulkAction::make('formatosBajaLote')
                        ->label('📄 Formatos de Baja (lote)')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $bajas = \App\Support\SigemBajaFormatHelper::filtrarInventariosEnBaja($records);
                            if ($bajas->isEmpty()) {
                                Notification::make()
                                    ->title('No hay equipos dados de baja o con solicitud de baja en la selección')
                                    ->warning()
                                    ->send();
                                return null;
                            }

                            return \App\Support\SigemPdfGenerator::descargarFormatosBajaLote($bajas);
                        }),
                    Tables\Actions\DeleteBulkAction::make()->successNotificationTitle('Registro de inventario eliminado'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return \App\Filament\Resources\InventarioResource\Schemas\InfolistSchema::make($infolist);
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

        $departamentosFiltro = ['' => 'Todos'];
        try {
            $departamentosFiltro += Departamento::orderBy('nombre')->pluck('nombre', 'nombre')->toArray();
        } catch (\Throwable $e) {}

        return [$categoriasFiltro, $areasFiltro, $departamentosFiltro];
    }

    public static function obtenerInventariosDashboardQuery(
        array $searches = [],
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        $invQuery = Inventario::query()
            ->with(['material.marca', 'material.tipo', 'proveedor', 'usuario', 'area', 'departamento', 'salon'])
            ->select('inventario.*');

        // Filtro Departamento
        $deptoSearch = $searches['departamento'] ?? $searches['departamento_nombre'] ?? null;
        if (filled($deptoSearch)) {
            $termDepto = '%' . trim((string) $deptoSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termDepto) {
                $q->whereHas('departamento', fn ($sub) => $sub->where('nombre', 'like', $termDepto))
                  ->orWhereHas('area.departamento', fn ($sub) => $sub->where('nombre', 'like', $termDepto))
                  ->orWhere('ubicacion_fisica', 'like', $termDepto);
            });
        }

        // Filtro Equipo
        $equipoSearch = $searches['equipo'] ?? $searches['material_nombre'] ?? null;
        if (filled($equipoSearch)) {
            $termEquipo = '%' . trim((string) $equipoSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termEquipo) {
                $q->whereHas('material', fn ($sub) => $sub->where('nombre', 'like', $termEquipo))
                  ->orWhere('subtipo', 'like', $termEquipo)
                  ->orWhere('codigo', 'like', $termEquipo)
                  ->orWhere('num_serie', 'like', $termEquipo)
                  ->orWhere('modelo', 'like', $termEquipo);
            });
        }

        // Filtro Categoría
        $catSearch = $searches['categoria'] ?? $searches['material_tipo_nombre'] ?? null;
        if (filled($catSearch)) {
            $catTrim = trim((string) $catSearch);
            $invQuery->where(function (Builder $q) use ($catTrim) {
                $q->whereHas('material.tipo', fn ($sub) => $sub->where('nombre', 'like', $catTrim . '%'))
                  ->orWhere('subtipo', 'like', "%{$catTrim}%");
            });
        }

        // Filtro Estado
        if (filled($searches['estado'] ?? null)) {
            $invQuery->where('estado', trim((string) $searches['estado']));
        }

        // Filtro Área
        $areaSearch = $searches['area'] ?? $searches['ubicacion_fisica'] ?? null;
        if (filled($areaSearch)) {
            $termArea = '%' . trim((string) $areaSearch) . '%';
            $invQuery->where(function (Builder $q) use ($termArea) {
                $q->whereHas('area', fn ($sub) => $sub->where('nombre', 'like', $termArea))
                  ->orWhere('ubicacion_fisica', 'like', $termArea);
            });
        }

        // Filtro Aula
        if (filled($searches['aula'] ?? null)) {
            $termAula = '%' . trim((string) $searches['aula']) . '%';
            $invQuery->where(function (Builder $q) use ($termAula) {
                $q->whereHas('salon', fn ($sub) => $sub->where('nombre', 'like', $termAula))
                  ->orWhere('especificaciones->aula', 'like', $termAula)
                  ->orWhere('ubicacion_fisica', 'like', $termAula);
            });
        }

        // Filtro No. de Serie
        if (filled($searches['num_serie'] ?? null)) {
            $termSerie = '%' . trim((string) $searches['num_serie']) . '%';
            $invQuery->where(function (Builder $q) use ($termSerie) {
                $q->where('num_serie', 'like', $termSerie)
                  ->orWhere('codigo', 'like', $termSerie);
            });
        }

        // Filtro IP
        if (filled($searches['ip_address'] ?? null)) {
            $termIp = '%' . trim((string) $searches['ip_address']) . '%';
            $invQuery->where(function (Builder $q) use ($termIp) {
                $q->where('ip_address', 'like', $termIp)
                  ->orWhere('especificaciones->ip_address', 'like', $termIp);
            });
        }

        // Filtro MAC
        if (filled($searches['mac_address'] ?? null)) {
            $termMac = '%' . trim((string) $searches['mac_address']) . '%';
            $invQuery->where(function (Builder $q) use ($termMac) {
                $q->where('mac_address', 'like', $termMac)
                  ->orWhere('especificaciones->mac_address', 'like', $termMac);
            });
        }

        // Búsqueda general
        if (filled($searches['busqueda'] ?? null)) {
            $term = '%' . trim((string) $searches['busqueda']) . '%';
            $invQuery->where(function (Builder $q) use ($term) {
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

        // Fechas
        if (filled($searches['fecha_desde'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '>=', $searches['fecha_desde']);
        }
        if (filled($searches['fecha_hasta'] ?? null)) {
            $invQuery->whereDate('fecha_registro', '<=', $searches['fecha_hasta']);
        }

        // Ordenamiento
        $direction = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        return match ($sortColumn) {
            'departamento' => $invQuery->leftJoin('departamento', 'inventario.departamento_id', '=', 'departamento.id_departamento')
                ->orderBy('departamento.nombre', $direction),
            'equipo' => $invQuery->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                ->orderBy('material.nombre', $direction),
            'categoria' => $invQuery->leftJoin('material', 'inventario.id_producto', '=', 'material.id_producto')
                ->leftJoin('tipo_material', 'material.id_tipodematerial', '=', 'tipo_material.id_tipodematerial')
                ->orderBy('tipo_material.nombre', $direction),
            'estado' => $invQuery->orderBy('estado', $direction),
            'area' => $invQuery->leftJoin('area', 'inventario.area_id', '=', 'area.id_area')
                ->orderBy('area.nombre', $direction),
            'aula' => $invQuery->leftJoin('salones', 'inventario.salon_id', '=', 'salones.id')
                ->orderBy('salones.nombre', $direction),
            'num_serie' => $invQuery->orderBy('num_serie', $direction),
            'ip_address' => $invQuery->orderBy('ip_address', $direction),
            'mac_address' => $invQuery->orderBy('mac_address', $direction),
            default => $invQuery->orderBy('inventario.created_at', $direction),
        };
    }

    public static function obtenerInventariosDashboard(array $searches): Collection
    {
        return static::obtenerInventariosDashboardQuery($searches)->take(50)->get();
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
            $top['Otras categorías'] = $otras;
        }

        return $top;
    }

    public static function exportarInventarioExcel($livewire = null): ?\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return \App\Support\SigemExcelExporter::exportarInventario($livewire);
    }
}
