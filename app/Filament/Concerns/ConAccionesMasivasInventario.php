<?php

namespace App\Filament\Concerns;

use App\Enums\EstadoInventario;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
use App\Models\Mantenimiento;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Models\Salon;
use App\Support\SigemBajaFormatHelper;
use App\Support\SigemExcelExporter;
use App\Support\SigemPdfGenerator;
use App\Support\UbicacionHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

trait ConAccionesMasivasInventario
{
    /**
     * IDs de los equipos seleccionados actualmente en la tabla.
     */
    public array $selectedRows = [];

    /**
     * Indicador de si el checkbox maestro está activado.
     */
    public bool $selectAll = false;

    /**
     * Controla la visibilidad del panel lateral de acciones masivas.
     */
    public bool $showSidePanel = false;

    /**
     * Vista interna del panel slide-over: 'principal', 'cambio', 'baja', 'modificar'
     */
    public string $slideOverStep = 'principal';

    /**
     * Campos de formulario interno para Cambio masivo
     */
    public ?string $cambioNuevoEstado = '';
    public ?string $cambioDepartamentoId = '';
    public ?string $cambioNuevaAreaId = '';
    public ?string $cambioAula = '';
    public ?string $cambioReceptorId = '';
    public ?string $cambioMotivoBaja = '';
    public ?string $cambioDescripcionBaja = '';
    public ?string $cambioDescripcionDano = '';
    public ?string $cambioNivelDano = 'Menor';
    public ?string $cambioTipoServicio = 'Preventivo';
    public ?string $cambioDescripcionFalla = '';
    public ?string $cambioProveedorId = '';
    public ?string $cambioMotivoDevolucion = '';
    public ?string $cambioNuevoTipoPropiedad = '';
    public ?string $cambioMotivo = '';

    /**
     * Campos de formulario interno para Baja masiva
     */
    public ?string $bajaMotivo = '';
    public ?string $bajaObservaciones = '';
    public ?string $bajaFolioSibsep = '';
    public ?string $bajaPosibleAreaUtilizacion = '';

    /**
     * Campos de formulario interno para Modificar todos
     */
    public ?string $modificarCampo = '';
    public ?string $modificarNuevoValor = '';

    /**
     * Se dispara cuando se actualiza la lista de filas seleccionadas.
     */
    public function updatedSelectedRows(): void
    {
        $this->showSidePanel = ! empty($this->selectedRows);
        if (empty($this->selectedRows)) {
            $this->slideOverStep = 'principal';
        }
    }

    /**
     * Alterna la selección de todos los registros que cumplen el filtro actual.
     */
    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedRows = $this->getFilteredTableQuery()
                ->pluck('inventario.id_inventario')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedRows = [];
        }

        $this->showSidePanel = ! empty($this->selectedRows);
        if (empty($this->selectedRows)) {
            $this->slideOverStep = 'principal';
        }
    }

    /**
     * Limpia la selección activa y regresa la vista del slide-over al paso principal.
     */
    public function deseleccionarTodos(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->showSidePanel = false;
        $this->slideOverStep = 'principal';
        $this->cambioNuevoEstado = '';
        $this->cambioDepartamentoId = '';
        $this->cambioNuevaAreaId = '';
        $this->cambioAula = '';
        $this->cambioReceptorId = '';
        $this->cambioMotivoBaja = '';
        $this->cambioDescripcionBaja = '';
        $this->cambioDescripcionDano = '';
        $this->cambioNivelDano = 'Menor';
        $this->cambioTipoServicio = 'Preventivo';
        $this->cambioDescripcionFalla = '';
        $this->cambioProveedorId = '';
        $this->cambioMotivoDevolucion = '';
        $this->cambioNuevoTipoPropiedad = '';
        $this->cambioMotivo = '';
        $this->bajaMotivo = '';
        $this->bajaObservaciones = '';
        $this->bajaFolioSibsep = '';
        $this->bajaPosibleAreaUtilizacion = '';
        $this->modificarCampo = '';
        $this->modificarNuevoValor = '';
    }

    /**
     * Cambia la vista interna del slide-over.
     */
    public function setSlideOverStep(string $step): void
    {
        $this->slideOverStep = $step;
        if ($step === 'cambio') {
            $this->cambioNuevoEstado = '';
            $this->cambioDepartamentoId = '';
            $this->cambioNuevaAreaId = '';
            $this->cambioAula = '';
            $this->cambioReceptorId = '';
            $this->cambioMotivoBaja = '';
            $this->cambioDescripcionBaja = '';
            $this->cambioDescripcionDano = '';
            $this->cambioNivelDano = 'Menor';
            $this->cambioTipoServicio = 'Preventivo';
            $this->cambioDescripcionFalla = '';
            $this->cambioProveedorId = '';
            $this->cambioMotivoDevolucion = '';
            $this->cambioNuevoTipoPropiedad = '';
            $this->cambioMotivo = '';
        } elseif ($step === 'baja') {
            $this->bajaMotivo = '';
            $this->bajaObservaciones = '';
            $this->bajaFolioSibsep = '';
            $this->bajaPosibleAreaUtilizacion = '';
        } elseif ($step === 'modificar') {
            $this->modificarCampo = '';
            $this->modificarNuevoValor = '';
        }
    }

    /**
     * Hook reactivo: Al cambiar departamento destino, limpia el área.
     */
    public function updatedCambioDepartamentoId($value): void
    {
        $this->cambioNuevaAreaId = '';
    }

    /**
     * Hook reactivo: Al cambiar área destino, autocompleta depto si está vacío.
     */
    public function updatedCambioNuevaAreaId($value): void
    {
        if (! empty($value)) {
            $area = Area::find($value);
            if ($area && empty($this->cambioDepartamentoId)) {
                $this->cambioDepartamentoId = (string) $area->id_departamento;
            }
        }
    }

    /**
     * Regresa la navegación interna del slide-over a la vista principal.
     */
    public function volverAPanelPrincipal(): void
    {
        $this->slideOverStep = 'principal';
    }

    /**
     * Devuelve una colección Eloquent de los registros seleccionados con relaciones cargadas.
     */
    public function obtenerRegistrosSeleccionados(): Collection
    {
        if (empty($this->selectedRows)) {
            return new Collection();
        }

        return Inventario::with(['material.tipo', 'departamento', 'area', 'salon'])
            ->whereIn('id_inventario', $this->selectedRows)
            ->get();
    }

    /**
     * Ejecuta el cambio masivo desde el formulario integrado en el slide-over.
     */
    public function ejecutarCambioSlideOver(): void
    {
        $data = [
            'nuevo_estado' => $this->cambioNuevoEstado ?: null,
            'nuevo_departamento_id' => $this->cambioDepartamentoId ?: null,
            'nueva_area_id' => $this->cambioNuevaAreaId ?: null,
            'nueva_aula' => $this->cambioAula ?: null,
            'nuevo_receptor_id' => $this->cambioReceptorId ?: null,
            'motivo_baja' => $this->cambioMotivoBaja ?: null,
            'descripcion_baja' => $this->cambioDescripcionBaja ?: null,
            'descripcion_dano' => $this->cambioDescripcionDano ?: null,
            'nivel_dano' => $this->cambioNivelDano ?: null,
            'tipo_servicio' => $this->cambioTipoServicio ?: null,
            'descripcion_falla' => $this->cambioDescripcionFalla ?: null,
            'proveedor_id' => $this->cambioProveedorId ?: null,
            'motivo_devolucion' => $this->cambioMotivoDevolucion ?: null,
            'nuevo_tipo_propiedad' => $this->cambioNuevoTipoPropiedad ?: null,
            'motivo' => $this->cambioMotivo ?: 'Cambio masivo',
        ];

        $this->procesarCambioMasivo($data);
    }

    /**
     * Ejecuta la baja masiva desde el formulario integrado en el slide-over.
     */
    public function ejecutarBajaSlideOver(): void
    {
        if (empty($this->bajaMotivo)) {
            Notification::make()->title('Debe seleccionar un motivo de baja')->danger()->send();
            return;
        }

        $data = [
            'motivo_baja' => $this->bajaMotivo,
            'observaciones_baja' => $this->bajaObservaciones ?: 'Baja masiva',
            'folio_sibsep' => $this->bajaFolioSibsep ?: null,
            'posible_area_utilizacion' => $this->bajaPosibleAreaUtilizacion ?: null,
        ];

        $this->procesarBajaMasiva($data);
    }

    /**
     * Ejecuta la modificación masiva desde el formulario integrado en el slide-over.
     */
    public function ejecutarModificarSlideOver(): void
    {
        if (empty($this->modificarCampo)) {
            Notification::make()->title('Debe seleccionar un campo a modificar')->danger()->send();
            return;
        }

        $data = [
            'campo_a_modificar' => $this->modificarCampo,
            'nuevo_estado' => $this->modificarCampo === 'estado' ? $this->modificarNuevoValor : null,
            'nueva_area_id' => $this->modificarCampo === 'area' ? $this->modificarNuevoValor : null,
            'nuevo_departamento_id' => $this->modificarCampo === 'departamento' ? $this->modificarNuevoValor : null,
            'nueva_ubicacion_fisica' => $this->modificarCampo === 'ubicacion_fisica' ? $this->modificarNuevoValor : null,
            'nuevo_tipo_propiedad' => $this->modificarCampo === 'tipo_propiedad' ? $this->modificarNuevoValor : null,
        ];

        $this->procesarModificarTodos($data);
    }

    /**
     * Modal: Modificar todos
     * Permite modificar masivamente Estado, Área, Departamento, Ubicación física o Tipo de propiedad.
     */
    public function modificarTodosAction(): Action
    {
        return Action::make('modificarTodos')
            ->label('Modificar todos')
            ->modalHeading('Modificar equipos seleccionados')
            ->modalDescription(fn () => 'Se actualizará el campo seleccionado en los ' . count($this->selectedRows) . ' equipos seleccionados.')
            ->modalSubmitActionLabel('Aplicar a todos')
            ->modalCancelActionLabel('Cancelar')
            ->form([
                Select::make('campo_a_modificar')
                    ->label('Campo a modificar')
                    ->options([
                        'estado' => 'Estado',
                        'area' => 'Área',
                        'departamento' => 'Departamento',
                        'ubicacion_fisica' => 'Ubicación física',
                        'tipo_propiedad' => 'Tipo de propiedad (Propio / Rentado)',
                    ])
                    ->required()
                    ->live(),

                Select::make('nuevo_estado')
                    ->label('Nuevo estado')
                    ->options(EstadoInventario::options())
                    ->visible(fn (Get $get) => $get('campo_a_modificar') === 'estado')
                    ->required(fn (Get $get) => $get('campo_a_modificar') === 'estado'),

                Select::make('nueva_area_id')
                    ->label('Nueva área')
                    ->options(fn () => Area::orderBy('nombre')->pluck('nombre', 'id_area'))
                    ->visible(fn (Get $get) => $get('campo_a_modificar') === 'area')
                    ->required(fn (Get $get) => $get('campo_a_modificar') === 'area'),

                Select::make('nuevo_departamento_id')
                    ->label('Nuevo departamento')
                    ->options(fn () => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento'))
                    ->visible(fn (Get $get) => $get('campo_a_modificar') === 'departamento')
                    ->required(fn (Get $get) => $get('campo_a_modificar') === 'departamento'),

                TextInput::make('nueva_ubicacion_fisica')
                    ->label('Nueva ubicación física')
                    ->placeholder('ej. Depto. de Sistemas > Oficina > Aula 101')
                    ->maxLength(150)
                    ->visible(fn (Get $get) => $get('campo_a_modificar') === 'ubicacion_fisica')
                    ->required(fn (Get $get) => $get('campo_a_modificar') === 'ubicacion_fisica'),

                Select::make('nuevo_tipo_propiedad')
                    ->label('Nuevo tipo de propiedad')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ])
                    ->visible(fn (Get $get) => $get('campo_a_modificar') === 'tipo_propiedad')
                    ->required(fn (Get $get) => $get('campo_a_modificar') === 'tipo_propiedad'),
            ])
            ->action(function (array $data) {
                $this->procesarModificarTodos($data);
            });
    }

    /**
     * Procesa la lógica de modificación masiva.
     */
    public function procesarModificarTodos(array $data): void
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados')->warning()->send();
            return;
        }

        $esAdmin = ! ($this instanceof \App\Filament\ServicioSocial\Pages\Dashboard);
        $userId = auth()->id() ?? \App\Models\User::where('email', 'admin@tecnm.edu.mx')->value('id') ?? \App\Models\User::first()?->id;
        $campo = $data['campo_a_modificar'];
        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($data, $campo, $esAdmin, $userId, &$aplicados, &$omitidos) {
            $records = Inventario::whereIn('id_inventario', $this->selectedRows)->get();

            foreach ($records as $record) {
                $estadoAnterior = $record->estado ?? EstadoInventario::Disponible->value;

                if ($campo === 'estado') {
                    $nuevoEstado = $data['nuevo_estado'];

                    if ($nuevoEstado === EstadoInventario::DadoDeBaja->value) {
                        if ($esAdmin) {
                            $record->darBajaDirecta('Modificación masiva', 'Actualización de estado masiva', $userId);
                        } else {
                            $record->solicitarBaja('Modificación masiva', 'Actualización de estado masiva', $userId);
                        }
                    } else {
                        $record->update(['estado' => $nuevoEstado]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $nuevoEstado,
                            'motivo_cambio' => "Modificación masiva de estado a {$nuevoEstado}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                    $aplicados++;
                } elseif ($campo === 'area') {
                    $areaId = $data['nueva_area_id'];
                    $area = Area::find($areaId);
                    $deptoId = $area?->id_departamento ?? $record->departamento_id;

                    $record->departamento_id = $deptoId;
                    $record->area_id = $areaId;
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $areaId, $record->aula ?: $record->salon_id, null);
                    if ($ubicacionNueva) {
                        $record->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
                    }
                    $record->save();

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => 'Modificación masiva de área: ' . ($area?->nombre ?? 'Nueva área'),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'departamento') {
                    $deptoId = $data['nuevo_departamento_id'];
                    $depto = Departamento::find($deptoId);

                    $record->departamento_id = $deptoId;
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $record->area_id, $record->aula ?: $record->salon_id, null);
                    if ($ubicacionNueva) {
                        $record->ubicacion_fisica = substr($ubicacionNueva, 0, 150);
                    }
                    $record->save();

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => 'Modificación masiva de departamento: ' . ($depto?->nombre ?? 'Nuevo depto'),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'ubicacion_fisica') {
                    $ubicacion = $data['nueva_ubicacion_fisica'];
                    $record->update(['ubicacion_fisica' => substr($ubicacion, 0, 150)]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $record->estado,
                        'motivo_cambio' => "Modificación masiva de ubicación: {$ubicacion}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                    $aplicados++;
                } elseif ($campo === 'tipo_propiedad') {
                    $nuevoTipo = $data['nuevo_tipo_propiedad'] ?? $this->modificarNuevoValor;
                    if (in_array($nuevoTipo, ['Propio', 'Rentado'], true)) {
                        $propiedadAnt = $record->tipo_propiedad ?? 'Propio';
                        $record->update(['tipo_propiedad' => $nuevoTipo]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $record->estado,
                            'motivo_cambio' => "Modificación masiva de propiedad: {$propiedadAnt} → {$nuevoTipo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                        $aplicados++;
                    }
                }
            }
        });

        $this->deseleccionarTodos();

        Notification::make()
            ->title("Modificación aplicada a {$aplicados} equipos" . ($omitidos > 0 ? " ({$omitidos} omitidos)" : ''))
            ->success()
            ->send();
    }

    /**
     * Modal: Cambio (masivo)
     * Aplica o solicita cambio de área / estado / tipo de propiedad para equipos seleccionados con campos contextuales.
     */
    public function cambioMasivoAction(): Action
    {
        return Action::make('cambioMasivo')
            ->label('Cambio (masivo)')
            ->modalHeading('Cambio masivo de área y estado')
            ->modalDescription(fn () => 'Aplica a los ' . count($this->selectedRows) . ' equipos seleccionados.')
            ->modalSubmitActionLabel('Aplicar cambio')
            ->modalCancelActionLabel('Cancelar')
            ->form([
                Select::make('nuevo_estado')
                    ->label('Nuevo estado')
                    ->placeholder('— sin cambio —')
                    ->options(array_merge(
                        EstadoInventario::options(),
                        ['Cambio de propiedad' => 'Cambio de propiedad']
                    ))
                    ->live(),

                // Contextual: Cambio de área
                Select::make('nuevo_departamento_id')
                    ->label('Nuevo departamento')
                    ->options(fn () => Departamento::orderBy('nombre')->pluck('nombre', 'id_departamento'))
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::CambioDeArea->value, EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value, null, ''], true))
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('nueva_area_id', null)),

                Select::make('nueva_area_id')
                    ->label('Nueva área')
                    ->placeholder('— sin cambio —')
                    ->options(function (Get $get) {
                        $deptoId = $get('nuevo_departamento_id');
                        return $deptoId
                            ? Area::where('id_departamento', $deptoId)->orderBy('nombre')->pluck('nombre', 'id_area')
                            : Area::orderBy('nombre')->pluck('nombre', 'id_area');
                    })
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::CambioDeArea->value, EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value, null, ''], true))
                    ->live(),

                TextInput::make('nueva_aula')
                    ->label('Nueva aula')
                    ->placeholder('ej. E101')
                    ->visible(fn (Get $get) => $get('nuevo_estado') === EstadoInventario::CambioDeArea->value),

                // Contextual: Cambio de propiedad
                Select::make('nuevo_tipo_propiedad')
                    ->label('Nueva propiedad')
                    ->placeholder('— Seleccionar —')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ])
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), ['Cambio de propiedad', null, ''], true)),

                // Contextual: Asignado / Reasignado
                Select::make('nuevo_receptor_id')
                    ->label('Nuevo receptor')
                    ->options(fn () => Receptor::orderBy('nombre')->get()->mapWithKeys(fn ($r) => [
                        $r->id_receptor => trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}"),
                    ]))
                    ->searchable()
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value], true)),

                // Contextual: Dado de baja / Solicitud de baja
                Select::make('motivo_baja')
                    ->label('Motivo de baja')
                    ->options(SigemBajaFormatHelper::motivosBajaOptions())
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::DadoDeBaja->value, EstadoInventario::SolicitudDeBaja->value], true)),

                TextInput::make('descripcion_baja')
                    ->label('Descripción de la baja')
                    ->placeholder('Descripción detallada de la baja...')
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::DadoDeBaja->value, EstadoInventario::SolicitudDeBaja->value], true)),

                // Contextual: Daño
                TextInput::make('descripcion_dano')
                    ->label('Descripción del daño')
                    ->placeholder('Detalle del daño presentado...')
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::Danado->value, 'Daño'], true)),

                Select::make('nivel_dano')
                    ->label('Nivel de daño')
                    ->options([
                        'Menor' => 'Menor',
                        'Mayor' => 'Mayor',
                    ])
                    ->default('Menor')
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::Danado->value, 'Daño'], true)),

                // Contextual: En Mantenimiento
                Select::make('tipo_servicio')
                    ->label('Tipo de servicio')
                    ->options([
                        'Preventivo' => 'Preventivo',
                        'Correctivo' => 'Correctivo',
                        'Mejora' => 'Mejora',
                    ])
                    ->default('Preventivo')
                    ->visible(fn (Get $get) => $get('nuevo_estado') === EstadoInventario::EnMantenimiento->value),

                Textarea::make('descripcion_falla')
                    ->label('Descripción de la falla')
                    ->placeholder('Falla o trabajo a realizar...')
                    ->visible(fn (Get $get) => $get('nuevo_estado') === EstadoInventario::EnMantenimiento->value),

                // Contextual: Devuelto a Proveedor
                Select::make('proveedor_id')
                    ->label('Proveedor')
                    ->options(fn () => Proveedor::orderBy('nombre_empresa')->pluck('nombre_empresa', 'id_proveedor'))
                    ->visible(fn (Get $get) => $get('nuevo_estado') === EstadoInventario::DevueltoAProveedor->value),

                Textarea::make('motivo_devolucion')
                    ->label('Motivo de la devolución')
                    ->placeholder('Motivo de la devolución...')
                    ->visible(fn (Get $get) => $get('nuevo_estado') === EstadoInventario::DevueltoAProveedor->value),

                Textarea::make('motivo')
                    ->label('Motivo del cambio')
                    ->placeholder('Motivo del cambio (opcional)...'),
            ])
            ->action(function (array $data) {
                $this->procesarCambioMasivo($data);
            });
    }

    /**
     * Procesa la lógica de cambio masivo de área, estado o tipo de propiedad con campos contextuales.
     */
    public function procesarCambioMasivo(array $data): void
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados')->warning()->send();
            return;
        }

        $esAdmin = ! ($this instanceof \App\Filament\ServicioSocial\Pages\Dashboard);
        $userId = auth()->id() ?? \App\Models\User::where('email', 'admin@tecnm.edu.mx')->value('id') ?? \App\Models\User::first()?->id;

        $nuevoEstado = ! empty($data['nuevo_estado']) ? $data['nuevo_estado'] : null;
        if ($nuevoEstado === 'Daño') {
            $nuevoEstado = EstadoInventario::Danado->value;
        }

        $nuevoDeptoId = ! empty($data['nuevo_departamento_id']) ? $data['nuevo_departamento_id'] : null;
        $nuevaAreaId = ! empty($data['nueva_area_id']) ? $data['nueva_area_id'] : null;
        $nuevaAula = ! empty($data['nueva_aula']) ? trim($data['nueva_aula']) : null;
        $nuevoReceptorId = ! empty($data['nuevo_receptor_id']) ? $data['nuevo_receptor_id'] : null;
        $motivoBaja = ! empty($data['motivo_baja']) ? $data['motivo_baja'] : 'Baja masiva';
        $descBaja = ! empty($data['descripcion_baja']) ? $data['descripcion_baja'] : 'Baja masiva';
        $descDano = ! empty($data['descripcion_dano']) ? $data['descripcion_dano'] : null;
        $nivelDano = ! empty($data['nivel_dano']) ? $data['nivel_dano'] : 'Menor';
        $tipoServicio = ! empty($data['tipo_servicio']) ? $data['tipo_servicio'] : 'Preventivo';
        $descFalla = ! empty($data['descripcion_falla']) ? $data['descripcion_falla'] : 'Falla reportada en cambio masivo';
        $proveedorId = ! empty($data['proveedor_id']) ? $data['proveedor_id'] : null;
        $motivoDevolucion = ! empty($data['motivo_devolucion']) ? $data['motivo_devolucion'] : 'Devolución masiva';
        $nuevoTipoPropiedad = ! empty($data['nuevo_tipo_propiedad']) ? $data['nuevo_tipo_propiedad'] : null;
        $motivo = ! empty($data['motivo']) ? $data['motivo'] : 'Cambio masivo';

        // Regla: Tipo de propiedad NO aplica en estados restrictivos
        $estadosSinTipoPropiedad = [
            EstadoInventario::DadoDeBaja->value,
            EstadoInventario::SolicitudDeBaja->value,
            EstadoInventario::EnMantenimiento->value,
            EstadoInventario::Danado->value,
            EstadoInventario::DevueltoAProveedor->value,
            'Baja',
            'Daño',
        ];
        if ($nuevoEstado && in_array($nuevoEstado, $estadosSinTipoPropiedad, true)) {
            $nuevoTipoPropiedad = null;
        }

        // Calcular propiedad actual de los equipos seleccionados
        $equipos = Inventario::whereIn('id_inventario', $this->selectedRows)->get();
        $propiedades = $equipos->pluck('tipo_propiedad')->unique()->filter();
        if ($propiedades->count() === 0) {
            $propiedadActual = 'Sin definir';
        } elseif ($propiedades->count() === 1) {
            $propiedadActual = $propiedades->first();  // "Propio" o "Rentado"
        } else {
            $propiedadActual = 'Mixto';  // Equipos con diferente propiedad
        }

        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use (
            $esAdmin, $userId, $nuevoEstado, $nuevoDeptoId, $nuevaAreaId, $nuevaAula,
            $nuevoReceptorId, $motivoBaja, $descBaja, $descDano, $nivelDano,
            $tipoServicio, $descFalla, $proveedorId, $motivoDevolucion, $nuevoTipoPropiedad,
            $motivo, $propiedadActual, &$aplicados, &$omitidos
        ) {
            $records = Inventario::whereIn('id_inventario', $this->selectedRows)->get();

            foreach ($records as $record) {
                // Filtrar equipos no aptos (ya dados de baja o con solicitud pendiente)
                if (in_array($record->estado, [
                    EstadoInventario::DadoDeBaja->value,
                    EstadoInventario::SolicitudDeBaja->value,
                    EstadoInventario::CambioDeArea->value,
                    'Baja',
                ], true)) {
                    $omitidos++;
                    continue;
                }

                $estadoAnterior = $record->estado ?? EstadoInventario::Disponible->value;

                // Destinos para ubicación
                $areaDestino = $nuevaAreaId;
                $deptoDestino = $nuevoDeptoId;
                $aulaDestino = $nuevaAula;

                // Resolver departamento a partir del área si no se indicó
                if ($areaDestino) {
                    $areaModel = Area::find($areaDestino);
                    if (! $deptoDestino && $areaModel) {
                        $deptoDestino = $areaModel->id_departamento;
                    }
                }

                // 1. MANEJO SEGÚN EL NUEVO ESTADO:
                if ($nuevoEstado === 'Cambio de propiedad') {
                    if ($nuevoTipoPropiedad) {
                        $record->update(['tipo_propiedad' => $nuevoTipoPropiedad]);
                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $record->estado,
                            'estado_nuevo' => $record->estado,  // Estado NO cambia
                            'motivo_cambio' => "Cambio de propiedad de {$propiedadActual} a: {$nuevoTipoPropiedad}. {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                } elseif ($nuevoEstado === EstadoInventario::CambioDeArea->value) {
                    $deptoFinal = $deptoDestino ?: $record->departamento_id;
                    $areaFinal = $areaDestino ?: $record->area_id;

                    if ($esAdmin) {
                        $record->cambiarAreaDirecto($deptoFinal, $areaFinal, null, null, $motivo, $userId);
                    } else {
                        $record->solicitarCambioArea($deptoFinal, $areaFinal, null, null, $motivo, $userId);
                    }

                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    $specs['cambio_origen_depto'] = $record->departamento?->nombre;
                    $specs['cambio_origen_area'] = $record->area?->nombre;
                    $specs['cambio_destino_depto'] = Departamento::find($deptoFinal)?->nombre;
                    $specs['cambio_destino_area'] = Area::find($areaFinal)?->nombre;
                    if ($aulaDestino) {
                        $specs['cambio_nueva_aula'] = $aulaDestino;
                        $specs['aula'] = $aulaDestino;
                        $record->aula = $aulaDestino;
                    }
                    $specs['cambio_motivo'] = $motivo;
                    $specs['fecha_cambio'] = now()->toDateString();
                    $record->salon_id = null; // salon_id se guarda como null al hacer cambio de área
                    $record->especificaciones = $specs;
                    $record->save();
                } elseif ($nuevoEstado === EstadoInventario::DadoDeBaja->value) {
                    if ($esAdmin) {
                        $record->darBajaDirecta($motivoBaja, $descBaja, $userId);
                    } else {
                        $record->solicitarBaja($motivoBaja, $descBaja, $userId);
                    }
                } elseif ($nuevoEstado === EstadoInventario::SolicitudDeBaja->value) {
                    $record->solicitarBaja($motivoBaja, $descBaja, $userId);
                } elseif ($nuevoEstado === EstadoInventario::EnMantenimiento->value) {
                    $record->update(['estado' => EstadoInventario::EnMantenimiento->value]);

                    $userModel = \App\Models\User::find($userId);
                    $tipoMant = in_array($tipoServicio, ['Preventivo', 'Correctivo', 'Mejora'], true) ? $tipoServicio : 'Preventivo';

                    Mantenimiento::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario_solicita' => $userId,
                        'tipo_mantenimiento' => $tipoMant,
                        'tipo_servicio' => 'Personal Técnico',
                        'nombre_tecnico' => $userModel?->name ?? 'Personal Técnico',
                        'descripcion_falla' => $descFalla,
                        'fecha_solicitud' => now(),
                        'fecha_inicio' => now(),
                        'estado' => 'Solicitado',
                        'observaciones' => $motivo,
                    ]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::EnMantenimiento->value,
                        'motivo_cambio' => "Enviado a mantenimiento masivo ({$tipoMant}): {$descFalla}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif ($nuevoEstado === EstadoInventario::Danado->value) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    if ($descDano) {
                        $specs['descripcion_dano'] = $descDano;
                    }
                    if ($nivelDano) {
                        $specs['nivel_dano'] = $nivelDano;
                    }

                    $record->update([
                        'estado' => EstadoInventario::Danado->value,
                        'especificaciones' => $specs,
                    ]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::Danado->value,
                        'motivo_cambio' => "Marcado como dañado ({$nivelDano}): " . ($descDano ?: $motivo),
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif ($nuevoEstado === EstadoInventario::DevueltoAProveedor->value) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    if ($motivoDevolucion) {
                        $specs['motivo_devolucion'] = $motivoDevolucion;
                    }

                    $updatePayload = [
                        'estado' => EstadoInventario::DevueltoAProveedor->value,
                        'especificaciones' => $specs,
                    ];
                    if ($proveedorId) {
                        $updatePayload['id_proveedor'] = $proveedorId;
                    }

                    $record->update($updatePayload);

                    $provNombre = $proveedorId ? (Proveedor::find($proveedorId)?->nombre_empresa ?? "Proveedor #{$proveedorId}") : 'Proveedor';
                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => EstadoInventario::DevueltoAProveedor->value,
                        'motivo_cambio' => "Devuelto a {$provNombre}: {$motivoDevolucion}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } elseif (in_array($nuevoEstado, [EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value], true)) {
                    $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                    $receptorNombre = '';
                    if ($nuevoReceptorId) {
                        $receptor = Receptor::find($nuevoReceptorId);
                        if ($receptor) {
                            $receptorNombre = trim("{$receptor->nombre} {$receptor->apellido_paterno} {$receptor->apellido_materno}");
                            $specs['id_receptor'] = $receptor->id_receptor;
                            $specs['receptor_nombre'] = $receptorNombre;
                        }
                    }

                    if ($areaDestino) {
                        $deptoFinal = $deptoDestino ?: ($record->departamento_id);
                        if ($esAdmin) {
                            $record->cambiarAreaDirecto($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        } else {
                            $record->solicitarCambioArea($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        }
                        $record->salon_id = null;
                    }

                    $record->update([
                        'estado' => $nuevoEstado,
                        'fecha_asignacion' => now(),
                        'especificaciones' => $specs,
                    ]);

                    $detalleAsignacion = $receptorNombre ? " a {$receptorNombre}" : '';
                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo' => $nuevoEstado,
                        'motivo_cambio' => "{$nuevoEstado}{$detalleAsignacion}: {$motivo}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
                } else {
                    // Sin cambio de estado, o 'Disponible'
                    if ($areaDestino) {
                        $deptoFinal = $deptoDestino ?: $record->departamento_id;

                        if ($esAdmin) {
                            $record->cambiarAreaDirecto($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        } else {
                            $record->solicitarCambioArea($deptoFinal, $areaDestino, null, null, $motivo, $userId);
                        }

                        $record->salon_id = null;
                        if ($aulaDestino) {
                            $specs = is_array($record->especificaciones) ? $record->especificaciones : [];
                            $specs['aula'] = $aulaDestino;
                            $record->aula = $aulaDestino;
                            $record->especificaciones = $specs;
                        }
                        $record->save();
                    }

                    if ($nuevoEstado && $record->estado !== EstadoInventario::CambioDeArea->value) {
                        $record->update(['estado' => $nuevoEstado]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $estadoAnterior,
                            'estado_nuevo' => $nuevoEstado,
                            'motivo_cambio' => "Cambio masivo de estado a {$nuevoEstado}: {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                }

                // 2. TIPO DE PROPIEDAD (para llamadas legacy o sin estado explícito 'Cambio de propiedad')
                if ($nuevoEstado !== 'Cambio de propiedad' && $nuevoTipoPropiedad && in_array($nuevoTipoPropiedad, ['Propio', 'Rentado'], true)) {
                    $propiedadAnt = $record->tipo_propiedad ?? 'Propio';
                    if ($propiedadAnt !== $nuevoTipoPropiedad) {
                        $record->update(['tipo_propiedad' => $nuevoTipoPropiedad]);

                        HistorialEstado::create([
                            'id_inventario' => $record->id_inventario,
                            'id_usuario' => $userId,
                            'estado_anterior' => $record->estado,
                            'estado_nuevo' => $record->estado,
                            'motivo_cambio' => "Cambio masivo de propiedad: {$propiedadAnt} → {$nuevoTipoPropiedad}: {$motivo}",
                            'ubicacion_fisica' => $record->ubicacion_fisica,
                            'fecha_cambio' => now(),
                        ]);
                    }
                }

                $aplicados++;
            }
        });

        $this->deseleccionarTodos();

        Notification::make()
            ->title("Cambio procesado para {$aplicados} equipos" . ($omitidos > 0 ? " ({$omitidos} omitidos por estar en baja o con solicitud)" : ''))
            ->success()
            ->send();
    }

    /**
     * Modal: Dar de baja (masivo)
     * Da de baja directa (Admin) o solicita baja (Servicio Social) en lote.
     */
    public function bajaMasivaAction(): Action
    {
        return Action::make('bajaMasiva')
            ->label('Dar de baja (masivo)')
            ->modalHeading('Dar de baja equipos seleccionados')
            ->modalDescription(fn () => ($this instanceof \App\Filament\ServicioSocial\Pages\Dashboard)
                ? 'Se enviará la solicitud de baja para los ' . count($this->selectedRows) . ' equipos seleccionados.'
                : 'Los ' . count($this->selectedRows) . ' equipos seleccionados pasarán a estado Dado de baja de forma inmediata.')
            ->modalSubmitActionLabel('Solicitar baja')
            ->modalCancelActionLabel('Cancelar')
            ->form(fn () => ConModalesBajaCambio::getModalBajaFormSchema(false))
            ->action(function (array $data) {
                $this->procesarBajaMasiva($data);
            });
    }

    /**
     * Procesa la lógica de dar de baja o solicitar baja masiva.
     */
    public function procesarBajaMasiva(array $data): void
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados')->warning()->send();
            return;
        }

        $esAdmin = ! ($this instanceof \App\Filament\ServicioSocial\Pages\Dashboard);
        $userId = auth()->id() ?? \App\Models\User::where('email', 'admin@tecnm.edu.mx')->value('id') ?? \App\Models\User::first()?->id;
        $motivo = $data['motivo_baja'];
        $obs = ! empty($data['observaciones_baja']) ? $data['observaciones_baja'] : 'Baja masiva';
        $datosAdicionales = ConModalesBajaCambio::extraerDatosAdicionalesBaja($data);
        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($esAdmin, $userId, $motivo, $obs, $datosAdicionales, &$aplicados, &$omitidos) {
            $records = Inventario::whereIn('id_inventario', $this->selectedRows)->get();

            foreach ($records as $record) {
                // Filtrar equipos no aptos (ya dados de baja o con solicitud)
                if (in_array($record->estado, [
                    EstadoInventario::DadoDeBaja->value,
                    EstadoInventario::SolicitudDeBaja->value,
                    EstadoInventario::CambioDeArea->value,
                    'Baja',
                ], true)) {
                    $omitidos++;
                    continue;
                }

                if ($esAdmin) {
                    $record->darBajaDirecta($motivo, $obs, $userId, $datosAdicionales);
                } else {
                    $record->solicitarBaja($motivo, $obs, $userId, $datosAdicionales);
                }

                $aplicados++;
            }
        });

        $this->deseleccionarTodos();

        Notification::make()
            ->title("Baja procesada para {$aplicados} equipos" . ($omitidos > 0 ? " ({$omitidos} omitidos por estar en baja o con solicitud)" : ''))
            ->success()
            ->send();
    }

    /**
     * Exporta a Excel (.xlsx) únicamente los registros seleccionados.
     */
    public function exportarSeleccion()
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados para exportar')->warning()->send();
            return null;
        }

        return SigemExcelExporter::exportarInventario($this, $this->selectedRows);
    }

    /**
     * Devuelve los registros seleccionados elegibles para Formato 4 (Dado de baja / Solicitud de baja).
     */
    public function obtenerRegistrosBajaSeleccionados(): \Illuminate\Support\Collection
    {
        return SigemBajaFormatHelper::filtrarInventariosEnBaja($this->obtenerRegistrosSeleccionados());
    }

    /**
     * Descarga en un PDF multipágina los Formatos de Baja (Formato 4) de los bienes en baja seleccionados.
     */
    public function descargarFormatosBajaLote()
    {
        $bajas = $this->obtenerRegistrosBajaSeleccionados();

        if ($error = SigemBajaFormatHelper::mensajeErrorLote($bajas)) {
            Notification::make()
                ->title($error)
                ->warning()
                ->send();

            return null;
        }

        if ($bajas->count() === 1) {
            return SigemPdfGenerator::descargarFormatoBaja($bajas->first());
        }

        return SigemPdfGenerator::descargarFormatosBajaLote($bajas);
    }
}
