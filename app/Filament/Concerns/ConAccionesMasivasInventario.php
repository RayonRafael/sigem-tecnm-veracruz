<?php

namespace App\Filament\Concerns;

use App\Enums\EstadoInventario;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\HistorialEstado;
use App\Models\Inventario;
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
    public ?string $cambioNuevaAreaId = '';
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
        $this->cambioNuevaAreaId = '';
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
            $this->cambioNuevaAreaId = '';
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
            'nueva_area_id' => $this->cambioNuevaAreaId ?: null,
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
        ];

        $this->procesarModificarTodos($data);
    }

    /**
     * Modal: Modificar todos
     * Permite modificar masivamente Estado, Área, Departamento o Ubicación física.
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
        $userId = auth()->id() ?? 1;
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
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $areaId, $record->salon_id, null);
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
                    $ubicacionNueva = UbicacionHelper::construir($deptoId, $record->area_id, $record->salon_id, null);
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
     * Aplica o solicita cambio de área / estado para equipos seleccionados.
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
                    ->options(EstadoInventario::options()),

                Select::make('nueva_area_id')
                    ->label('Nueva área')
                    ->placeholder('— sin cambio —')
                    ->options(fn () => Area::orderBy('nombre')->pluck('nombre', 'id_area')),

                Textarea::make('motivo')
                    ->label('Motivo del cambio')
                    ->placeholder('Motivo del cambio (opcional)...'),
            ])
            ->action(function (array $data) {
                $this->procesarCambioMasivo($data);
            });
    }

    /**
     * Procesa la lógica de cambio masivo de área o estado.
     */
    public function procesarCambioMasivo(array $data): void
    {
        if (empty($this->selectedRows)) {
            Notification::make()->title('No hay equipos seleccionados')->warning()->send();
            return;
        }

        $esAdmin = ! ($this instanceof \App\Filament\ServicioSocial\Pages\Dashboard);
        $userId = auth()->id() ?? 1;
        $nuevoEstado = ! empty($data['nuevo_estado']) ? $data['nuevo_estado'] : null;
        $nuevaAreaId = ! empty($data['nueva_area_id']) ? $data['nueva_area_id'] : null;
        $motivo = ! empty($data['motivo']) ? $data['motivo'] : 'Cambio masivo';
        $aplicados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($esAdmin, $userId, $nuevoEstado, $nuevaAreaId, $motivo, &$aplicados, &$omitidos) {
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

                if ($nuevaAreaId) {
                    $area = Area::find($nuevaAreaId);
                    $deptoId = $area?->id_departamento ?? $record->departamento_id;

                    if ($esAdmin) {
                        $record->cambiarAreaDirecto($deptoId, $nuevaAreaId, $record->salon_id, null, $motivo, $userId);
                    } else {
                        $record->solicitarCambioArea($deptoId, $nuevaAreaId, $record->salon_id, null, $motivo, $userId);
                    }
                }

                if ($nuevoEstado && $record->estado !== EstadoInventario::CambioDeArea->value) {
                    $estadoAnt = $record->estado ?? EstadoInventario::Disponible->value;
                    $record->update(['estado' => $nuevoEstado]);

                    HistorialEstado::create([
                        'id_inventario' => $record->id_inventario,
                        'id_usuario' => $userId,
                        'estado_anterior' => $estadoAnt,
                        'estado_nuevo' => $nuevoEstado,
                        'motivo_cambio' => "Cambio masivo de estado a {$nuevoEstado}: {$motivo}",
                        'ubicacion_fisica' => $record->ubicacion_fisica,
                        'fecha_cambio' => now(),
                    ]);
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
        $userId = auth()->id() ?? 1;
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
