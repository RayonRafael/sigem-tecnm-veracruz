@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'categoriasFiltro' => [],
    'areasFiltro' => [],
    'inventariosCompletos' => [],
    'totalActivos' => 0,
    'inventarioDisponibles' => 0,
    'inventarioEnMantenimiento' => 0,
    'inventarioAsignados' => 0,
    'inventarioDanados' => 0,
    'categoriasTop' => [],
])

@php
    $baseRoute = $panel === 'admin' ? '/admin/inventarios' : '/servicio-social/inventarios';
    $emptyMessage = $panel === 'admin' ? 'No hay equipos registrados en el inventario.' : 'No hay registros de inventario disponibles.';
    $sectionDesc = $panel === 'admin' ? 'Busca, filtra y gestiona los equipos registrados en el sistema.' : 'Consulta los equipos y activos registrados en el sistema.';
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
                {{-- TOOLBAR: [Buscador] + [Desde] + [Hasta] + [Quitar filtros] --}}
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
                    </div>
                </div>

                <div class="sigem-rep-table-wrap">
                    <table class="fi-ta-table">
                        <thead>
                            <tr>
                                <th style="min-width: 250px; width: 250px;">CÓDIGO</th>
                                <th style="min-width: 220px;">EQUIPO</th>
                                <th style="min-width: 120px; width: 120px;">CATEGORÍA</th>
                                <th style="min-width: 130px; width: 130px;">ESTADO</th>
                                <th style="min-width: 180px; width: 180px;">ÁREA</th>
                                <th style="min-width: 104px; width: 104px; text-align: center;">ACCIONES</th>
                            </tr>
                            <tr class="sigem-dash-filter-row">
                                <th class="fi-table-individual-search-cell" style="min-width: 250px; width: 250px; padding: 4px 6px;">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.500ms="tableColumnSearches.num_serie"
                                        placeholder="ej. CISCO-..."
                                        class="sigem-dash-filter-input"
                                        style="width: 100%; box-sizing: border-box;"
                                    />
                                </th>
                                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                                    <input
                                        type="search"
                                        wire:model.live.debounce.500ms="tableColumnSearches.material_nombre"
                                        placeholder="Filtrar..."
                                        class="sigem-dash-filter-input"
                                    />
                                </th>
                                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                                    <select
                                        wire:model.live="tableColumnSearches.material_tipo_nombre"
                                        class="fi-table-individual-search-select"
                                    >
                                        @foreach (($categoriasFiltro ?? []) as $optVal => $optLbl)
                                            <option value="{{ $optVal }}">{{ $optLbl }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                                    <select
                                        wire:model.live="tableColumnSearches.estado"
                                        class="fi-table-individual-search-select"
                                    >
                                        <option value="">Todos</option>
                                        <option value="Disponible">Disponible</option>
                                        <option value="Asignado">Asignado</option>
                                        <option value="Reasignado">Reasignado</option>
                                        <option value="En Mantenimiento">En Mantenimiento</option>
                                        <option value="Dañado">Dañado</option>
                                        <option value="Solicitud de baja">Solicitud de baja</option>
                                        <option value="Dado de baja">Dado de baja</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </th>
                                <th class="fi-table-individual-search-cell" style="padding: 4px 6px;">
                                    <select
                                        wire:model.live="tableColumnSearches.ubicacion_fisica"
                                        class="fi-table-individual-search-select"
                                    >
                                        @foreach (($areasFiltro ?? []) as $optVal => $optLbl)
                                            <option value="{{ $optVal }}">{{ $optLbl }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th class="fi-table-individual-search-cell" style="padding: 4px 6px; text-align: center;">
                                    <button
                                        type="button"
                                        x-data
                                        x-show="Object.values($wire.tableColumnSearches || {}).some(v => Boolean(v))"
                                        x-cloak
                                        x-on:click="$wire.set('tableColumnSearches', {})"
                                        class="sigem-dash-filter-clear"
                                        title="Limpiar filtros"
                                    >
                                        ✕
                                    </button>
                                </th>
                            </tr>
                        </thead>
                        @if($isSkeletonPreview)
                            {{-- SKELETON: TABLA INVENTARIO (5 FILAS × 6 CELDAS) --}}
                            <tbody>
                                @for($i = 0; $i < 5; $i++)
                                    <tr class="sigem-skeleton-tr">
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span>
                                        </td>
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
                            <tbody wire:loading.remove wire:target="tableColumnSearches, resetTableColumnSearches">
                                @php
                                    $inventariosTabla = collect($inventariosCompletos ?? [])->take(10);
                                @endphp
                                @forelse($inventariosTabla as $inv)
                                    @php
                                        $codigo = ! empty($inv->codigo) ? $inv->codigo : ($inv->num_serie ?: ('#' . $inv->id_inventario));
                                        $nombreEquipo = $inv->material?->nombre ?? $inv->subtipo ?? 'Equipo general';
                                        $serieSub = 'ID: ' . $codigo;
                                        $categoria = $inv->material?->tipo?->nombre ?? $inv->subtipo ?? 'General';
                                        $areaUbicacion = $inv->area?->nombre;
                                        if (! $areaUbicacion && ! empty($inv->ubicacion_fisica)) {
                                            $parts = explode(' > ', $inv->ubicacion_fisica);
                                            $areaUbicacion = $parts[1] ?? $parts[0] ?? null;
                                        }
                                        $areaUbicacion = $areaUbicacion ?: 'Sin área asignada';
                                        $estado = $inv->estado ?? 'Sin estado';

                                        $pillCls = match ($estado) {
                                            'Disponible' => 'ok',
                                            'Asignado' => 'info',
                                            'Reasignado' => 'reassign',
                                            'En Mantenimiento' => 'warn',
                                            'Solicitud de baja' => 'warn',
                                            'Dado de baja', 'Baja', 'Dañado' => 'danger',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <tr>
                                        <td style="min-width: 250px; width: 250px; white-space: nowrap;">
                                            <span class="sigem-td-folio" style="font-weight: 700; white-space: nowrap; display: inline-block;" title="{{ $codigo }}">{{ $codigo }}</span>
                                        </td>
                                        <td style="min-width: 220px;">
                                            <div style="display: flex; flex-direction: column;">
                                                <strong style="color: var(--sig-text);" title="{{ $nombreEquipo }}">{{ $nombreEquipo }}</strong>
                                                <span style="font-size: 13px; color: var(--sig-muted); white-space: nowrap;" title="{{ $serieSub }}">{{ $serieSub }}</span>
                                            </div>
                                        </td>
                                        <td style="min-width: 120px; white-space: nowrap;">
                                            <span class="sigem-pill neutral" style="font-weight: 500; white-space: nowrap;" title="{{ $categoria }}">{{ $categoria }}</span>
                                        </td>
                                        <td style="min-width: 130px; white-space: nowrap;">
                                            <span class="sigem-pill {{ $pillCls }}" style="white-space: nowrap;">
                                                <span class="sigem-dot"></span>{{ $estado }}
                                            </span>
                                        </td>
                                        <td style="min-width: 180px; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $areaUbicacion }}">
                                            <span style="font-size: 14px; color: var(--sig-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $areaUbicacion }}">{{ $areaUbicacion }}</span>
                                        </td>
                                        <td class="fi-ta-actions-cell" style="min-width: 104px; width: 104px; text-align: center; white-space: nowrap;">
                                            <div class="fi-ta-actions" style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                                                <button type="button"
                                                        wire:click="verEquipo({{ $inv->id_inventario }})"
                                                        class="fi-icon-btn sigem-dash-btn"
                                                        title="Ver detalle"
                                                        aria-label="Ver detalle">
                                                </button>
                                                <button type="button"
                                                        wire:click="editarEquipo({{ $inv->id_inventario }})"
                                                        class="fi-icon-btn sigem-dash-btn"
                                                        title="Modificar"
                                                        aria-label="Modificar">
                                                </button>
                                                @if($panel === 'admin')
                                                    <button type="button"
                                                            wire:click="eliminarEquipo({{ $inv->id_inventario }})"
                                                            class="fi-icon-btn sigem-dash-btn"
                                                            title="Eliminar"
                                                            aria-label="Eliminar">
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 28px; color: var(--sig-muted);">
                                            {{ $emptyMessage }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            {{-- SKELETON: TABLA INVENTARIO (5 FILAS × 6 CELDAS) --}}
                            <tbody wire:loading wire:target="tableColumnSearches, resetTableColumnSearches" style="display: none;">
                                @for($i = 0; $i < 5; $i++)
                                    <tr class="sigem-skeleton-tr">
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 80px;"></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 140px; height: 16px;"></span>
                                                <span class="sigem-skeleton sigem-skeleton-cell" style="width: 90px; height: 12px;"></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 75px; height: 22px; border-radius: 9999px;"></span>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 95px; height: 22px; border-radius: 9999px;"></span>
                                        </td>
                                        <td>
                                            <span class="sigem-skeleton sigem-skeleton-cell" style="width: 105px;"></span>
                                        </td>
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

                <div class="sigem-dash-more">
                    <a href="{{ url($baseRoute) }}">
                        Ver todo el inventario →
                    </a>
                </div>
            </div>
        </div>

        <!-- Panel lateral derecho: Gráfica del inventario -->
        <aside class="sigem-inv-side" x-show="showGrafica">
            <div class="sigem-inv-side-title">Resumen del inventario</div>

            @php
                $dashTotal = $totalActivos ?? 0;
                $circunf = 263.89;
                $donutSegs = [
                    ['label' => 'Disponible', 'cant' => $inventarioDisponibles ?? 0, 'color' => '#059669'],
                    ['label' => 'En mantenimiento', 'cant' => $inventarioEnMantenimiento ?? 0, 'color' => '#d97706'],
                    ['label' => 'Asignado', 'cant' => $inventarioAsignados ?? 0, 'color' => '#0891b2'],
                    ['label' => 'Dañado', 'cant' => $inventarioDanados ?? 0, 'color' => '#dc2626'],
                ];
                $dashOffset = 0;
            @endphp

            <!-- 1. Dona SVG -->
            <div class="sigem-donut">
                <svg viewBox="0 0 120 120" width="120" height="120">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="#f1f5f9" stroke-width="16" />
                    @if($dashTotal > 0)
                        @foreach($donutSegs as $seg)
                            @if($seg['cant'] > 0)
                                @php
                                    $dLen = round(($seg['cant'] / $dashTotal) * $circunf, 2);
                                    $dGap = round($circunf - $dLen, 2);
                                    $curOff = $dashOffset;
                                    $dashOffset += $dLen;
                                @endphp
                                <circle
                                    cx="60"
                                    cy="60"
                                    r="42"
                                    fill="none"
                                    stroke="{{ $seg['color'] }}"
                                    stroke-width="16"
                                    stroke-dasharray="{{ $dLen }} {{ $dGap }}"
                                    stroke-dashoffset="{{ -$curOff }}"
                                    transform="rotate(-90 60 60)"
                                />
                            @endif
                        @endforeach
                    @else
                        <circle cx="60" cy="60" r="42" fill="none" stroke="#e5e7eb" stroke-width="16" />
                    @endif
                </svg>
                <div class="sigem-donut-center">
                    <b>{{ $dashTotal }}</b>
                    <span>equipos</span>
                </div>
            </div>

            <!-- 2. Leyenda de estados -->
            <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 3px;">
                @foreach($donutSegs as $seg)
                    <div class="sigem-lg" style="cursor: default;">
                        <i style="background: {{ $seg['color'] }};"></i>
                        <span>{{ $seg['label'] }}</span>
                        <b>{{ $seg['cant'] }}</b>
                    </div>
                @endforeach
            </div>

            <!-- 3. Barras por categoría -->
            <div style="margin-top: 18px; border-top: 1px solid var(--sig-border); padding-top: 14px;">
                <div style="font-size: 13.5px; font-weight: 700; color: var(--sig-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px;">
                    Por categoría
                </div>
                @forelse(($categoriasTop ?? []) as $catNom => $catCant)
                    @php
                        $pct = ($dashTotal ?? 0) > 0 ? round(($catCant / $dashTotal) * 100) : 0;
                    @endphp
                    <a href="{{ url($baseRoute . '?tableSearch=' . urlencode($catNom)) }}"
                       class="sigem-cb"
                       style="text-decoration: none; color: inherit;"
                       title="Ver {{ $catNom }} en inventario">
                        <span title="{{ $catNom }}">{{ $catNom }}</span>
                        <div><span style="width: {{ $pct }}%;"></span></div>
                        <span style="text-align: right; font-weight: 600;">{{ $catCant }}</span>
                    </a>
                @empty
                    <div style="font-size: 13px; color: var(--sig-muted);">Sin categorías registradas</div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
