@props([
    'livewire',
    'deptosFiltros' => [],
])

<div class="sigem-filter-toolbar">
    <div class="sigem-filter-group">
        <span class="sigem-filter-label">📅 Desde:</span>
        <input type="date"
               wire:model.live="filtroFechaDesde"
               class="sigem-filter-input">
    </div>

    <div class="sigem-filter-group">
        <span class="sigem-filter-label">📅 Hasta:</span>
        <input type="date"
               wire:model.live="filtroFechaHasta"
               class="sigem-filter-input">
    </div>

    <div class="sigem-filter-group">
        <span class="sigem-filter-label">🏢 Depto:</span>
        <select wire:model.live="filtroDepartamentoId" class="sigem-filter-input" style="min-width: 170px; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
            <option value="">Todos los departamentos</option>
            @foreach ($deptosFiltros as $idDepto => $nombreDepto)
                <option value="{{ $idDepto }}">{{ $nombreDepto }}</option>
            @endforeach
        </select>
    </div>

    <div class="sigem-filter-group">
        <span class="sigem-filter-label">🗂️ Área:</span>
        <select wire:model.live="filtroAreaId" class="sigem-filter-input" style="min-width: 160px; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
            <option value="">Todas las áreas</option>
            @foreach ($livewire->areasParaFiltros as $idArea => $nombreArea)
                <option value="{{ $idArea }}">{{ $nombreArea }}</option>
            @endforeach
        </select>
    </div>

    @if ($livewire->filtroFechaDesde || $livewire->filtroFechaHasta || $livewire->filtroDepartamentoId || $livewire->filtroAreaId)
        <button type="button"
                wire:click="limpiarFiltrosNuevosReportes"
                class="sigem-btn-export-csv"
                style="color: #dc2626; border-color: #fca5a5; background: #fef2f2;">
            ✕ Limpiar filtros
        </button>
    @endif
</div>
