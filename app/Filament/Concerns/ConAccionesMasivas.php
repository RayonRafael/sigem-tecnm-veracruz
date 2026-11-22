<?php

namespace App\Filament\Concerns;

use App\Enums\EstadoInventario;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Proveedor;
use App\Models\Receptor;
use App\Services\InventarioCambioService;
use App\Support\SigemBajaFormatHelper;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

trait ConAccionesMasivas
{
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
        $resultado = InventarioCambioService::aplicarModificarTodos(
            $this->selectedRows,
            $data,
            $esAdmin,
            null,
            $this->modificarNuevoValor ?? null
        );

        $this->deseleccionarTodos();

        $aplicados = $resultado['aplicados'];
        $omitidos = $resultado['omitidos'];

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

                Select::make('nuevo_tipo_propiedad')
                    ->label('Nueva propiedad')
                    ->placeholder('— Seleccionar —')
                    ->options([
                        'Propio' => 'Propio',
                        'Rentado' => 'Rentado',
                    ])
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), ['Cambio de propiedad', null, ''], true)),

                Select::make('nuevo_receptor_id')
                    ->label('Nuevo receptor')
                    ->options(fn () => Receptor::orderBy('nombre')->get()->mapWithKeys(fn ($r) => [
                        $r->id_receptor => trim("{$r->nombre} {$r->apellido_paterno} {$r->apellido_materno}"),
                    ]))
                    ->searchable()
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::Asignado->value, EstadoInventario::Reasignado->value], true)),

                Select::make('motivo_baja')
                    ->label('Motivo de baja')
                    ->options(SigemBajaFormatHelper::motivosBajaOptions())
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::DadoDeBaja->value, EstadoInventario::SolicitudDeBaja->value], true)),

                TextInput::make('descripcion_baja')
                    ->label('Descripción de la baja')
                    ->placeholder('Descripción detallada de la baja...')
                    ->visible(fn (Get $get) => in_array($get('nuevo_estado'), [EstadoInventario::DadoDeBaja->value, EstadoInventario::SolicitudDeBaja->value], true)),

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
        $resultado = InventarioCambioService::aplicarCambioMasivo($this->selectedRows, $data, $esAdmin);

        $this->deseleccionarTodos();

        $aplicados = $resultado['aplicados'];
        $omitidos = $resultado['omitidos'];

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
        $resultado = InventarioCambioService::aplicarBajaMasiva($this->selectedRows, $data, $esAdmin);

        $this->deseleccionarTodos();

        $aplicados = $resultado['aplicados'];
        $omitidos = $resultado['omitidos'];

        Notification::make()
            ->title("Baja procesada para {$aplicados} equipos" . ($omitidos > 0 ? " ({$omitidos} omitidos por estar en baja o con solicitud)" : ''))
            ->success()
            ->send();
    }
}
