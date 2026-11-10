@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'departamentosFiltro' => [],
    'categoriasFiltro' => [],
    'areasFiltro' => [],
    'inventariosPaginados' => null,
    'inventariosCompletos' => [],
    'sortColumn' => 'created_at',
    'sortDirection' => 'desc',
    'perPage' => 10,
    'totalActivos' => 0,
    'inventarioDisponibles' => 0,
    'inventarioEnMantenimiento' => 0,
    'inventarioAsignados' => 0,
    'inventarioDanados' => 0,
    'categoriasTop' => [],
    'selectedRows' => [],
    'selectAll' => false,
    'showSidePanel' => false,
    'slideOverStep' => 'principal',
    'modificarCampo' => '',
])

@php
    $baseRoute = $panel === 'admin' ? '/admin/inventarios' : '/servicio-social/inventarios';
    $emptyMessage = $panel === 'admin' ? 'No hay equipos registrados en el inventario.' : 'No hay registros de inventario disponibles.';
    $sectionDesc = $panel === 'admin' ? 'Busca, filtra y gestiona los equipos registrados en el sistema.' : 'Consulta los equipos y activos registrados en el sistema.';
    
    $items = $inventariosPaginados ?? $inventariosCompletos ?? [];
    $isPaginator = $inventariosPaginados instanceof \Illuminate\Pagination\LengthAwarePaginator;

    $renderSort = function (string $col) use ($sortColumn, $sortDirection) {
        if (($sortColumn ?? '') === $col) {
            return '<span style="display: inline-block; margin-left: 3px; font-size: 10px; line-height: 1;">' . (($sortDirection ?? 'asc') === 'asc' ? '▲' : '▼') . '</span>';
        }
        return '';
    };
@endphp

<div x-data="{ showGrafica: false }" style="margin-top: 14px;">
    <div class="sigem-section-head">
        <div>
            <h2>💻 Inventario general</h2>
            <p>{{ $sectionDesc }}</p>
        </div>
        <div class="sigem-page-actions">
            <button type="button"
                    class="sigem-rep-btn-sec"
                    x-on:click="showGrafica = !showGrafica"
                    :style="showGrafica ? 'background: var(--sig-border);' : ''"
                    title="Alternar vista de gráfica de dona del inventario">
                📊 Gráfica
            </button>
            <button type="button"
                    wire:click="exportarExcel"
                    class="sigem-rep-btn-sec"
                    title="Exportar inventario a Excel (.xlsx)">
                ↓ Exportar
            </button>
            <button type="button"
                    wire:click="mountAction('registrarEquipo')"
                    class="sigem-btn-primary"
                    title="Registrar un nuevo equipo en el inventario">
                ＋ Registrar equipo
            </button>
        </div>
    </div>

    <div class="sigem-inv-layout" :class="showGrafica ? '' : 'sigem-inv-no-chart'" style="margin-top: 14px;">
        <div class="sigem-inv-main" style="min-width: 0;">
            <div class="sigem-rep-view sigem-dash-table" style="margin-bottom: 0;">
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
                                        wire:model.live="selectAll"
                                        class="sigem-checkbox"
                                        title="Seleccionar todos los registros visibles"
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
                            {{-- FILA DE FILTROS INDIVIDUALES (11 CELDAS) --}}
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
                                    <input
                                        type="search"
                                        wire:model.live.debounce.500ms="tableColumnSearches.aula"
                                        placeholder="Filtrar..."
                                        class="sigem-dash-filter-input"
                                        title="Filtrar por aula"
                                    />
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

                        @if($isSkeletonPreview)
                            {{-- SKELETON: TABLA INVENTARIO (5 FILAS × 11 CELDAS) --}}
                            <tbody>
                                @for($i = 0; $i < 5; $i++)
                                    <tr class="sigem-skeleton-tr">
                                        <td style="width: 44px; text-align: center;"><span class="sigem-skeleton" style="width: 16px; height: 16px; border-radius: 4px; display: inline-block;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 120px;"></span></td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                                            </div>
                                        </td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 50px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px;"></span></td>
                                        <td class="fi-ta-actions-cell" style="width: 104px; text-align: center; white-space: nowrap;">
                                            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        @else
                            <tbody wire:loading.remove wire:target="tableColumnSearches, resetTableColumnSearches, sortBy, setPage, previousPage, nextPage, perPage">
                                @forelse($items as $inv)
                                    @php
                                        $deptoNombre = $inv->departamento?->nombre
                                            ?? $inv->area?->departamento?->nombre
                                            ?? ($inv->ubicacion_fisica ? (explode(' > ', $inv->ubicacion_fisica)[0] ?? null) : null)
                                            ?? '—';

                                        $codigo = ! empty($inv->codigo) ? $inv->codigo : ($inv->num_serie ?: ('#' . ($inv->id_inventario ?? $inv->id)));
                                        $nombreEquipo = $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo general';
                                        $categoria = $inv->material?->tipo?->nombre ?? $inv->subtipo ?? 'General';

                                        $areaUbicacion = $inv->area?->nombre;
                                        if (! $areaUbicacion && ! empty($inv->ubicacion_fisica)) {
                                            $parts = explode(' > ', $inv->ubicacion_fisica);
                                            $areaUbicacion = $parts[1] ?? $parts[0] ?? null;
                                        }
                                        $areaUbicacion = $areaUbicacion ?: '—';

                                        $aula = $inv->salon?->nombre
                                            ?? ($inv->especificaciones['aula'] ?? null)
                                            ?? (preg_match('/Aula\s+([A-Za-z0-9_-]+)/i', $inv->ubicacion_fisica ?? '', $m) ? $m[1] : null)
                                            ?? '—';

                                        $numSerie = $inv->num_serie ?: '—';
                                        $ipAddress = $inv->ip_address ?? ($inv->especificaciones['ip_address'] ?? null) ?? '—';
                                        $macAddress = $inv->mac_address ?? ($inv->especificaciones['mac_address'] ?? null) ?? '—';

                                        $estado = $inv->estado ?? 'Sin estado';
                                        $pillCls = match ($estado) {
                                            'Disponible' => 'ok',
                                            'Asignado' => 'info',
                                            'Reasignado' => 'reassign',
                                            'En Mantenimiento', 'En mantenimiento' => 'warn',
                                            'Solicitud de baja' => 'warn',
                                            'Cambio de área' => 'info',
                                            'Dado de baja', 'Baja', 'Dañado' => 'danger',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <tr>
                                        {{-- 0. CHECKBOX SELECCIÓN --}}
                                        <td style="width: 44px; min-width: 44px; text-align: center; padding: 6px;">
                                            <input
                                                type="checkbox"
                                                wire:model.live="selectedRows"
                                                value="{{ (string) ($inv->id_inventario ?? $inv->id) }}"
                                                class="sigem-checkbox"
                                                title="Seleccionar equipo #{{ $inv->codigo ?: ($inv->id_inventario ?? $inv->id) }}"
                                            />
                                        </td>

                                        {{-- 1. DEPARTAMENTO --}}
                                        <td style="min-width: 170px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $deptoNombre }}">
                                            <span style="font-size: 13.5px; color: var(--sig-text);">{{ $deptoNombre }}</span>
                                        </td>

                                        {{-- 2. EQUIPO --}}
                                        <td style="min-width: 220px; max-width: 280px;">
                                            <div style="display: flex; flex-direction: column;">
                                                <strong style="color: var(--sig-text); line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $nombreEquipo }}">{{ $nombreEquipo }}</strong>
                                                <span style="font-size: 12px; color: var(--sig-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="ID: {{ $codigo }}">ID: {{ $codigo }}</span>
                                            </div>
                                        </td>

                                        {{-- 3. CATEGORÍA --}}
                                        <td style="min-width: 120px; white-space: nowrap;">
                                            <span class="sigem-pill neutral" style="font-weight: 500; white-space: nowrap;" title="{{ $categoria }}">{{ $categoria }}</span>
                                        </td>

                                        {{-- 4. ESTADO --}}
                                        <td style="min-width: 130px; white-space: nowrap;">
                                            <span class="sigem-pill {{ $pillCls }}" style="white-space: nowrap;">
                                                <span class="sigem-dot"></span>{{ $estado }}
                                            </span>
                                        </td>

                                        {{-- 5. ÁREA --}}
                                        <td style="min-width: 160px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $areaUbicacion }}">
                                            <span style="font-size: 13.5px; color: var(--sig-muted);">{{ $areaUbicacion }}</span>
                                        </td>

                                        {{-- 6. AULA --}}
                                        <td style="min-width: 80px; white-space: nowrap;">
                                            <span style="font-size: 13.5px; color: var(--sig-muted);">{{ $aula }}</span>
                                        </td>

                                        {{-- 7. NO. DE SERIE --}}
                                        <td style="min-width: 140px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $numSerie }}">
                                            <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-text);">{{ $numSerie }}</span>
                                        </td>

                                        {{-- 8. IP --}}
                                        <td style="min-width: 120px; white-space: nowrap;" title="{{ $ipAddress }}">
                                            <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-muted);">{{ $ipAddress }}</span>
                                        </td>

                                        {{-- 9. MAC --}}
                                        <td style="min-width: 140px; white-space: nowrap;" title="{{ $macAddress }}">
                                            <span style="font-size: 12.5px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; color: var(--sig-muted);">{{ $macAddress }}</span>
                                        </td>

                                        {{-- 10. ACCIONES --}}
                                        <td class="fi-ta-actions-cell" style="min-width: 104px; width: 104px; text-align: center; white-space: nowrap;">
                                            <div class="fi-ta-actions" style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                                                <button type="button"
                                                        wire:click="verEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                                        class="fi-icon-btn sigem-dash-btn"
                                                        title="Ver detalle"
                                                        aria-label="Ver detalle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                                        <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" />
                                                        <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        wire:click="editarEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                                        class="fi-icon-btn sigem-dash-btn"
                                                        title="Modificar"
                                                        aria-label="Modificar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                                    </svg>
                                                </button>
                                                @if($panel === 'admin')
                                                    <button type="button"
                                                            wire:click="eliminarEquipo({{ $inv->id_inventario ?? $inv->id }})"
                                                            class="fi-icon-btn sigem-dash-btn fi-icon-btn-danger"
                                                            title="Eliminar"
                                                            aria-label="Eliminar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" style="text-align: center; padding: 28px; color: var(--sig-muted);">
                                            {{ $emptyMessage }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            {{-- SKELETON EN CARGA LIVEWIRE --}}
                            <tbody wire:loading wire:target="tableColumnSearches, resetTableColumnSearches, sortBy, setPage, previousPage, nextPage, perPage" style="display: none;">
                                @for($i = 0; $i < 5; $i++)
                                    <tr class="sigem-skeleton-tr">
                                        <td style="width: 44px; text-align: center;"><span class="sigem-skeleton" style="width: 16px; height: 16px; border-radius: 4px; display: inline-block;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 120px;"></span></td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                                            </div>
                                        </td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 50px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span></td>
                                        <td><span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px;"></span></td>
                                        <td class="fi-ta-actions-cell" style="width: 104px; text-align: center; white-space: nowrap;">
                                            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                                <span class="sigem-skeleton" style="width: 26px; height: 26px; border-radius: 6px;"></span>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        @endif
                    </table>
                </div>

                {{-- PAGINACIÓN AL PIE DE LA TABLA --}}
                @if($isPaginator)
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
            </div>
        </div>

        {{-- Panel lateral derecho: Gráfica del inventario --}}
        <aside class="sigem-inv-side" x-show="showGrafica" x-cloak>
            <div class="sigem-inv-side-title">Resumen del inventario</div>

            @php
                $dashTotal = $totalActivos ?? 0;
                $circunf = 263.89;
                $donutSegs = [
                    ['label' => 'Disponible', 'cant' => $inventarioDisponibles ?? 0, 'color' => '#059669'],
                    ['label' => 'En Mantenimiento', 'cant' => $inventarioEnMantenimiento ?? 0, 'color' => '#d97706'],
                    ['label' => 'Asignado', 'cant' => $inventarioAsignados ?? 0, 'color' => '#0891b2'],
                    ['label' => 'Dañado', 'cant' => $inventarioDanados ?? 0, 'color' => '#dc2626'],
                ];
                $dashOffset = 0;
            @endphp

            {{-- 1. Dona SVG --}}
            <div class="sigem-donut">
                <svg viewBox="0 0 120 120" width="120" height="120">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="var(--sig-border)" stroke-width="16" />
                    @if($dashTotal > 0)
                        @foreach($donutSegs as $seg)
                            @php
                                $segLen = ($seg['cant'] / $dashTotal) * $circunf;
                                $dashArray = $segLen . ' ' . ($circunf - $segLen);
                            @endphp
                            @if($seg['cant'] > 0)
                                <circle cx="60" cy="60" r="42" fill="none"
                                        stroke="{{ $seg['color'] }}" stroke-width="16"
                                        stroke-dasharray="{{ $dashArray }}"
                                        stroke-dashoffset="{{ -$dashOffset }}"
                                        transform="rotate(-90 60 60)" />
                                @php $dashOffset += $segLen; @endphp
                            @endif
                        @endforeach
                    @endif
                </svg>
                <div class="sigem-donut-center">
                    <b>{{ $dashTotal }}</b>
                    <span>Total</span>
                </div>
            </div>

            {{-- 2. Leyenda interactiva (filtra por estado al dar clic) --}}
            <div class="sigem-leg" style="margin-top: 10px;">
                @foreach($donutSegs as $seg)
                    <button type="button"
                            wire:click="$set('tableColumnSearches.estado', '{{ $seg['label'] }}')"
                            class="sigem-leg-item"
                            style="background: none; border: none; cursor: pointer; text-align: left; width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 4px 0;"
                            title="Filtrar por {{ $seg['label'] }}">
                        <span style="display: inline-flex; align-items: center; gap: 6px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background-color: {{ $seg['color'] }}; display: inline-block;"></span>
                            <span>{{ $seg['label'] }}</span>
                        </span>
                        <b>{{ $seg['cant'] }}</b>
                    </button>
                @endforeach
            </div>

            {{-- 3. Barras por categoría interactivas (filtra por categoría al dar clic) --}}
            <div style="margin-top: 18px; border-top: 1px solid var(--sig-border); padding-top: 14px;">
                <div style="font-size: 13.5px; font-weight: 700; color: var(--sig-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px;">
                    Por categoría
                </div>
                @forelse(($categoriasTop ?? []) as $catNom => $catCant)
                    @php
                        $pct = ($dashTotal ?? 0) > 0 ? round(($catCant / $dashTotal) * 100) : 0;
                    @endphp
                    <button type="button"
                            wire:click="$set('tableColumnSearches.categoria', '{{ $catNom }}')"
                            class="sigem-cb"
                            style="text-decoration: none; color: inherit; width: 100%; border: none; background: none; cursor: pointer; text-align: left; padding: 4px 0;"
                            title="Filtrar por {{ $catNom }}">
                        <span title="{{ $catNom }}">{{ $catNom }}</span>
                        <div><span style="width: {{ $pct }}%;"></span></div>
                        <span style="text-align: right; font-weight: 600;">{{ $catCant }}</span>
                    </button>
                @empty
                    <div style="font-size: 13px; color: var(--sig-muted);">Sin categorías registradas</div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
