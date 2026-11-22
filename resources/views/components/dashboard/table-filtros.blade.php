@props([
    'departamentosFiltro' => [],
    'categoriasFiltro' => [],
    'areasFiltro' => [],
    'aulasFiltro' => [],
    'paginaActualIds' => [],
    'selectedRows' => [],
    'sortColumn' => 'created_at',
    'sortDirection' => 'desc',
    'mostrarColumnaRenta' => false,
])

@php
    $renderSort = function (string $col) use ($sortColumn, $sortDirection) {
        if (($sortColumn ?? '') === $col) {
            return '<span style="display: inline-block; margin-left: 3px; font-size: 10px; line-height: 1;">' . (($sortDirection ?? 'asc') === 'asc' ? '▲' : '▼') . '</span>';
        }

        return '';
    };

    $idsPagStr = array_map('strval', $paginaActualIds ?? []);
    $selStr = array_map('strval', $selectedRows ?? []);
    $todosEnPaginaSeleccionados = count($idsPagStr) > 0
        && count(array_intersect($idsPagStr, $selStr)) === count($idsPagStr);
@endphp

{{-- TOOLBAR: [Buscador general] + [Desde] + [Hasta] + [Quitar filtros] + [Pill Selección] --}}
<div class="fi-ta-header-toolbar sigem-dash-toolbar">
    <div class="sigem-table-toolbar-group">
        <div class="fi-ta-search-field sigem-toolbar-search">
            <label for="sigem-dash-busqueda" class="sr-only">Buscar</label>
            <div class="fi-input-wrp">
                <div class="fi-input-wrp-prefix"><span class="sigem-search-emoji">🔍</span></div>
                <input
                    id="sigem-dash-busqueda"
                    type="search"
                    autocomplete="off"
                    maxlength="1000"
                    wire:model.live.debounce.500ms="tableColumnSearches.busqueda"
                    placeholder="Buscar por equipo, código, IP, MAC, marca..."
                    class="fi-input sigem-dash-search-input"
                />
            </div>
        </div>

        <div class="sigem-toolbar-date">
            <span class="sigem-toolbar-date-label">Desde</span>
            <input type="date" wire:model.live="tableColumnSearches.fecha_desde" class="sigem-toolbar-date-input" title="Filtrar desde fecha" />
        </div>
        <div class="sigem-toolbar-date">
            <span class="sigem-toolbar-date-label">Hasta</span>
            <input type="date" wire:model.live="tableColumnSearches.fecha_hasta" class="sigem-toolbar-date-input" title="Filtrar hasta fecha" />
        </div>

        <button
            type="button"
            x-data
            x-show="Object.values($wire.tableColumnSearches || {}).some(v => Boolean(v))"
            x-cloak
            x-on:click="$wire.resetTableColumnSearches()"
            class="sigem-toolbar-reset-btn"
            title="Quitar todos los filtros"
        >
            ✕ Quitar filtros
        </button>

        @if(count($selectedRows ?? []) > 0)
            <div class="sigem-selection-pill" title="Equipos seleccionados actualmente">
                <span>✓ {{ count($selectedRows) }} seleccionado{{ count($selectedRows) > 1 ? 's' : '' }}</span>
            </div>
            <button
                type="button"
                wire:click="deseleccionarTodos"
                class="sigem-toolbar-reset-btn"
                title="Limpiar la selección"
            >
                ✕ Deseleccionar
            </button>
        @endif
    </div>
</div>

{{-- TABLA COMPLETA CON SCROLL HORIZONTAL SUPERIOR --}}
<div class="sigem-rep-table-wrap sigem-scroll-top">
    <table class="fi-ta-table">
        <thead>
            <tr>
                <th style="width: 44px; min-width: 44px; text-align: center; padding: 6px;">
                    <input
                        type="checkbox"
                        wire:click="toggleSeleccionarPagina"
                        @checked($todosEnPaginaSeleccionados)
                        class="sigem-checkbox"
                        title="Seleccionar solo esta página"
                    />
                </th>
                <th style="min-width: 170px; cursor: pointer; user-select: none;" wire:click="sortBy('departamento')" title="Ordenar por departamento">
                    DEPARTAMENTO {!! $renderSort('departamento') !!}
                </th>
                <th style="min-width: 220px; cursor: pointer; user-select: none;" wire:click="sortBy('equipo')" title="Ordenar por equipo">
                    EQUIPO {!! $renderSort('equipo') !!}
                </th>
                <th style="min-width: 120px; cursor: pointer; user-select: none;" wire:click="sortBy('categoria')" title="Ordenar por categoría">
                    CATEGORÍA {!! $renderSort('categoria') !!}
                </th>
                <th style="min-width: 130px; cursor: pointer; user-select: none;" wire:click="sortBy('estado')" title="Ordenar por estado">
                    ESTADO {!! $renderSort('estado') !!}
                </th>
                @if($mostrarColumnaRenta)
                    <th style="min-width: 125px; user-select: none;" title="Estado de vigencia de equipos rentados (máximo 3 años)">
                        ESTADO RENTA
                    </th>
                @endif
                <th style="min-width: 160px; cursor: pointer; user-select: none;" wire:click="sortBy('area')" title="Ordenar por área">
                    ÁREA {!! $renderSort('area') !!}
                </th>
                <th style="min-width: 80px; cursor: pointer; user-select: none;" wire:click="sortBy('aula')" title="Ordenar por aula">
                    AULA {!! $renderSort('aula') !!}
                </th>
                <th style="min-width: 140px; cursor: pointer; user-select: none;" wire:click="sortBy('num_serie')" title="Ordenar por número de serie">
                    NO. DE SERIE {!! $renderSort('num_serie') !!}
                </th>
                <th style="min-width: 120px; cursor: pointer; user-select: none;" wire:click="sortBy('ip_address')" title="Ordenar por IP">
                    IP {!! $renderSort('ip_address') !!}
                </th>
                <th style="min-width: 140px; cursor: pointer; user-select: none;" wire:click="sortBy('mac_address')" title="Ordenar por MAC">
                    MAC {!! $renderSort('mac_address') !!}
                </th>
                <th style="min-width: 104px; width: 104px; text-align: center;">ACCIONES</th>
            </tr>
            {{-- FILA DE FILTROS INDIVIDUALES ENCADENADOS --}}
            <tr class="sigem-dash-filter-row">
                <th class="fi-table-individual-search-cell" style="width: 44px; min-width: 44px; text-align: center; padding: 4px;">
                    @if(count($selectedRows ?? []) > 0)
                        <button
                            type="button"
                            wire:click="deseleccionarTodos"
                            class="sigem-dash-filter-clear"
                            title="Limpiar selección"
                            style="width: 24px; height: 24px; border: 1px solid var(--sig-border); border-radius: 4px; background: white; cursor: pointer; color: var(--sig-muted); display: inline-flex; align-items: center; justify-content: center; font-size: 11px;"
                        >
                            ✕
                        </button>
                    @endif
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <select wire:model.live="tableColumnSearches.departamento" class="fi-table-individual-search-select" title="Filtrar por departamento">
                        <option value="">Todos</option>
                        @foreach (($departamentosFiltro ?? []) as $optVal => $optLbl)
                            @if($optVal !== '')
                                <option value="{{ $optVal }}">{{ $optLbl }}</option>
                            @endif
                        @endforeach
                    </select>
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="tableColumnSearches.equipo"
                        placeholder="Filtrar..."
                        class="sigem-dash-filter-input"
                        title="Filtrar por equipo o código"
                    />
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <select wire:model.live="tableColumnSearches.categoria" class="fi-table-individual-search-select" title="Filtrar por categoría">
                        <option value="">Todas</option>
                        @foreach (($categoriasFiltro ?? []) as $optVal => $optLbl)
                            @if($optVal !== '')
                                <option value="{{ $optVal }}">{{ $optLbl }}</option>
                            @endif
                        @endforeach
                    </select>
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <select wire:model.live="tableColumnSearches.estado" class="fi-table-individual-search-select" title="Filtrar por estado">
                        <option value="">Todos</option>
                        <option value="Disponible">Disponible</option>
                        <option value="Asignado">Asignado</option>
                        <option value="Reasignado">Reasignado</option>
                        <option value="En Mantenimiento">En Mantenimiento</option>
                        <option value="Dañado">Dañado</option>
                        <option value="Cambio de área">Cambio de área</option>
                        <option value="Solicitud de baja">Solicitud de baja</option>
                        <option value="Dado de baja">Dado de baja</option>
                        <option value="Baja">Baja</option>
                    </select>
                </th>
                @if($mostrarColumnaRenta)
                    <th class="fi-table-individual-search-cell" style="padding: 4px 6px;"></th>
                @endif
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <select wire:model.live="tableColumnSearches.area" class="fi-table-individual-search-select" title="Filtrar por área">
                        <option value="">Todas</option>
                        @foreach (($areasFiltro ?? []) as $optVal => $optLbl)
                            @if($optVal !== '')
                                <option value="{{ $optVal }}">{{ $optLbl }}</option>
                            @endif
                        @endforeach
                    </select>
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <select wire:model.live="tableColumnSearches.aula" class="fi-table-individual-search-select" title="Filtrar por aula">
                        <option value="">Todas</option>
                        @foreach (($aulasFiltro ?? []) as $optVal => $optLbl)
                            @if($optVal !== '')
                                <option value="{{ $optVal }}">{{ $optLbl }}</option>
                            @endif
                        @endforeach
                    </select>
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="tableColumnSearches.num_serie"
                        placeholder="Filtrar..."
                        class="sigem-dash-filter-input"
                        title="Filtrar por número de serie"
                    />
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="tableColumnSearches.ip_address"
                        placeholder="Filtrar..."
                        class="sigem-dash-filter-input"
                        title="Filtrar por dirección IP"
                    />
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                    <input
                        type="search"
                        wire:model.live.debounce.500ms="tableColumnSearches.mac_address"
                        placeholder="Filtrar..."
                        class="sigem-dash-filter-input"
                        title="Filtrar por dirección MAC"
                    />
                </th>
                <th class="fi-table-individual-search-cell" style="padding: 4px 6px; text-align: center;">
                    <button
                        type="button"
                        x-data
                        x-show="Object.values($wire.tableColumnSearches || {}).some(v => Boolean(v))"
                        x-cloak
                        x-on:click="$wire.resetTableColumnSearches()"
                        class="sigem-dash-filter-clear"
                        title="Limpiar filtros"
                        style="width: 26px; height: 26px; border: 1px solid var(--sig-border); border-radius: 4px; background: white; cursor: pointer; color: var(--sig-muted); display: inline-flex; align-items: center; justify-content: center;"
                    >
                        ✕
                    </button>
                </th>
            </tr>
        </thead>

        {{ $slot }}
    </table>
</div>
