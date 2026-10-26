<?php

namespace App\Filament\Concerns;

use Filament\Actions\CreateAction;
use Filament\Tables;

/**
 * Hace que crear/editar desde el listado abra un slide-over lateral en lugar de
 * navegar a /create o /edit. Las páginas nativas siguen existiendo como fallback
 * al entrar directo por URL.
 *
 * Si la página define mutateSlideOverData(array $data): array, se aplica a los
 * datos del formulario (equivalente a mutateFormDataBefore{Create,Save} de las
 * páginas Create/Edit, que no se ejecutan dentro de un modal).
 */
trait ConSlideOver
{
    /**
     * Se activa solo en la carga inicial (mount) cuando la URL trae ?abrir=...
     * Es protected, así que no persiste entre requests de Livewire.
     */
    protected bool $abrirSlideOverPendiente = false;

    /**
     * Hook de trait de Livewire (mount{NombreTrait}). Solo marca la bandera: en este
     * punto $this->table todavía no existe, así que NO se monta ninguna acción aquí.
     *
     * Permite abrir el slide-over desde un enlace:
     *   ?abrir=create            -> abre "Crear"
     *   ?abrir=edit&registro=ID  -> abre "Editar" del registro
     */
    public function mountConSlideOver(): void
    {
        $this->abrirSlideOverPendiente = in_array(request()->query('abrir'), ['create', 'edit', 'view'], true);
    }

    /**
     * Hook de trait booted{NombreTrait}. Livewire lo ejecuta DESPUÉS de
     * bootedInteractsWithTable (los traits del padre van primero), así que la tabla
     * ya está inicializada y se pueden montar acciones de tabla.
     */
    public function bootedConSlideOver(): void
    {
        if (! $this->abrirSlideOverPendiente) {
            return;
        }

        $this->abrirSlideOverPendiente = false;

        if (request()->query('abrir') === 'create') {
            $this->mountAction('create');
        } elseif (filled($registro = request()->query('registro'))) {
            $action = request()->query('abrir') === 'view' ? 'view' : 'edit';
            $this->mountTableAction($action, (string) $registro);
        }
    }

    protected function configureCreateAction(CreateAction | Tables\Actions\CreateAction $action): void
    {
        parent::configureCreateAction($action);

        $action->url(null)->slideOver()->createAnother(false);

        if (method_exists($this, 'mutateSlideOverData')) {
            $action->mutateFormDataUsing(fn (array $data): array => $this->mutateSlideOverData($data));
        }
    }

    protected function configureEditAction(Tables\Actions\EditAction $action): void
    {
        parent::configureEditAction($action);

        $action->url(null)->slideOver();

        if (method_exists($this, 'mutateSlideOverData')) {
            $action->mutateFormDataUsing(fn (array $data): array => $this->mutateSlideOverData($data));
        }
    }
}
