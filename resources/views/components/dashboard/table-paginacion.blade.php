@props([
    'inventariosPaginados' => null,
])

@if($inventariosPaginados instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-top: 1px solid var(--sig-border); flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--sig-muted);">
            <span>Mostrar</span>
            <select wire:model.live="perPage" class="fi-table-individual-search-select" style="width: auto; padding: 3px 8px; font-size: 13px;">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            @if($inventariosPaginados->total() > 0)
                <span>por página · Mostrando {{ $inventariosPaginados->firstItem() }} a {{ $inventariosPaginados->lastItem() }} de {{ $inventariosPaginados->total() }} registros</span>
            @else
                <span>por página · 0 registros</span>
            @endif
        </div>

        @if($inventariosPaginados->hasPages())
            <div style="display: flex; align-items: center; gap: 4px;">
                @if($inventariosPaginados->onFirstPage())
                    <button type="button" disabled style="opacity: 0.5; padding: 5px 10px; font-size: 13px; border: 1px solid var(--sig-border); border-radius: 6px; background: white; cursor: not-allowed;">Anterior</button>
                @else
                    <button type="button" wire:click="previousPage" style="padding: 5px 10px; font-size: 13px; border: 1px solid var(--sig-border); border-radius: 6px; background: white; cursor: pointer;">Anterior</button>
                @endif

                <span style="padding: 5px 10px; font-size: 13px; font-weight: 600; color: var(--sig-text);">
                    Página {{ $inventariosPaginados->currentPage() }} de {{ $inventariosPaginados->lastPage() }}
                </span>

                @if($inventariosPaginados->hasMorePages())
                    <button type="button" wire:click="nextPage" style="padding: 5px 10px; font-size: 13px; border: 1px solid var(--sig-border); border-radius: 6px; background: white; cursor: pointer;">Siguiente</button>
                @else
                    <button type="button" disabled style="opacity: 0.5; padding: 5px 10px; font-size: 13px; border: 1px solid var(--sig-border); border-radius: 6px; background: white; cursor: not-allowed;">Siguiente</button>
                @endif
            </div>
        @endif
    </div>
@endif
