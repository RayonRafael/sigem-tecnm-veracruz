<?php

namespace App\Filament\Concerns;

use App\Models\Area;
use Filament\Notifications\Notification;

trait ConSlideOverCambio
{
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
     * Limpia la selección activa y regresa la vista del slide-over al paso principal.
     */
    public function deseleccionarTodos(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->showSidePanel = false;
        $this->slideOverStep = 'principal';
        $this->resetFormularioCambio();
        $this->resetFormularioBaja();
        $this->resetFormularioModificar();
    }

    /**
     * Cambia la vista interna del slide-over.
     */
    public function setSlideOverStep(string $step): void
    {
        $this->slideOverStep = $step;
        if ($step === 'cambio') {
            $this->resetFormularioCambio();
        } elseif ($step === 'baja') {
            $this->resetFormularioBaja();
        } elseif ($step === 'modificar') {
            $this->resetFormularioModificar();
        }
    }

    protected function resetFormularioCambio(): void
    {
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
    }

    protected function resetFormularioBaja(): void
    {
        $this->bajaMotivo = '';
        $this->bajaObservaciones = '';
        $this->bajaFolioSibsep = '';
        $this->bajaPosibleAreaUtilizacion = '';
    }

    protected function resetFormularioModificar(): void
    {
        $this->modificarCampo = '';
        $this->modificarNuevoValor = '';
    }

    /**
     * Hook reactivo: Al cambiar departamento destino, limpia el área y el aula.
     */
    public function updatedCambioDepartamentoId($value): void
    {
        $this->cambioNuevaAreaId = '';
        $this->cambioAula = '';
    }

    /**
     * Hook reactivo: Al cambiar área destino, limpia el aula y autocompleta depto si está vacío.
     */
    public function updatedCambioNuevaAreaId($value): void
    {
        $this->cambioAula = '';
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
}
