<?php

namespace App\Filament\Concerns;

use App\Models\Inventario;
use Illuminate\Database\Eloquent\Collection;

trait ConSeleccionFilas
{
    /**
     * IDs de los equipos seleccionados actualmente en la tabla.
     */
    public array $selectedRows = [];

    /**
     * IDs visibles en la página actual de la tabla (para seleccionar solo la página actual).
     */
    public array $paginaActualIds = [];

    /**
     * Indicador de si el checkbox maestro de la página actual está activado.
     */
    public bool $selectAll = false;

    /**
     * Controla la visibilidad del panel lateral de acciones masivas.
     */
    public bool $showSidePanel = false;

    /**
     * Se dispara cuando se actualiza la lista de filas seleccionadas.
     */
    public function updatedSelectedRows(): void
    {
        $this->selectedRows = array_values(array_unique(array_map('strval', $this->selectedRows)));
        $idsPagina = array_map('strval', $this->paginaActualIds);
        $this->selectAll = ! empty($idsPagina)
            && count(array_intersect($idsPagina, $this->selectedRows)) === count($idsPagina);
        $this->showSidePanel = ! empty($this->selectedRows);
        if (empty($this->selectedRows)) {
            $this->slideOverStep = 'principal';
        }
    }

    /**
     * Checkbox cabecera: alterna la selección SOLO de los registros visibles en la página actual.
     */
    public function toggleSeleccionarPagina(): void
    {
        $idsPagina = $this->obtenerIdsPaginaActual();

        if (empty($idsPagina)) {
            $this->deseleccionarTodos();

            return;
        }

        $seleccionadosStr = array_map('strval', $this->selectedRows);
        $todosSeleccionados = count(array_intersect($idsPagina, $seleccionadosStr)) === count($idsPagina);

        if ($todosSeleccionados) {
            $this->selectedRows = array_values(array_diff($seleccionadosStr, $idsPagina));
            $this->selectAll = false;
        } else {
            $this->selectedRows = array_values(array_unique(array_merge($seleccionadosStr, $idsPagina)));
            $this->selectAll = true;
        }

        $this->showSidePanel = ! empty($this->selectedRows);
        if (empty($this->selectedRows)) {
            $this->slideOverStep = 'principal';
        }
    }

    /**
     * Alterna la selección SOLO de los registros visibles en la página actual (compatibilidad con wire:model="selectAll").
     */
    public function updatedSelectAll($value): void
    {
        $idsPagina = $this->obtenerIdsPaginaActual();

        if ($value) {
            $this->selectedRows = $idsPagina;
            $this->selectAll = ! empty($idsPagina);
        } else {
            $this->selectedRows = [];
            $this->selectAll = false;
        }

        $this->showSidePanel = ! empty($this->selectedRows);
        if (empty($this->selectedRows)) {
            $this->slideOverStep = 'principal';
        }
    }

    /**
     * Obtiene los IDs de la página actual (ya hidratados o consultando la página activa).
     *
     * @return list<string>
     */
    protected function obtenerIdsPaginaActual(): array
    {
        if (! empty($this->paginaActualIds)) {
            return array_values(array_map('strval', $this->paginaActualIds));
        }

        $perPage = property_exists($this, 'perPage') ? (int) $this->perPage : 10;

        return $this->getFilteredTableQuery()
            ->paginate($perPage)
            ->getCollection()
            ->pluck('id_inventario')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();
    }

    /**
     * Al cambiar de página en Livewire (WithPagination), limpiar selección activa.
     */
    public function updatedPaginators($page, $pageName): void
    {
        $this->limpiarSeleccionActiva();
    }

    /**
     * Alias para limpieza al cambiar página.
     */
    public function updatedPagina($value): void
    {
        $this->limpiarSeleccionActiva();
    }

    /**
     * Limpia únicamente el estado de selección (sin reiniciar formularios si no hace falta).
     */
    protected function limpiarSeleccionActiva(): void
    {
        $this->selectedRows = [];
        $this->selectAll = false;
        $this->showSidePanel = false;
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
}
