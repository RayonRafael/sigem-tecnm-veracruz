@props([
    'panel' => 'admin',
    'isSkeletonPreview' => false,
    'departamentosFiltro' => [],
    'categoriasFiltro' => [],
    'areasFiltro' => [],
    'aulasFiltro' => [],
    'inventariosPaginados' => null,
    'inventariosCompletos' => [],
    'paginaActualIds' => [],
    'sortColumn' => 'created_at',
    'sortDirection' => 'desc',
    'perPage' => 10,
    'totalActivos' => 0,
    'inventarioDisponibles' => 0,
    'inventarioEnMantenimiento' => 0,
    'inventarioAsignados' => 0,
    'inventarioDanados' => 0,
    'categoriasTop' => [],
    'resumenRentas' => ['total' => 0, 'vigentes' => 0, 'por_vencer' => 0, 'vencidas' => 0],
    'selectedRows' => [],
    'selectAll' => false,
    'showSidePanel' => false,
    'slideOverStep' => 'principal',
    'modificarCampo' => '',
])

@php
    $emptyMessage = $panel === 'admin' ? 'No hay equipos registrados en el inventario.' : 'No hay registros de inventario disponibles.';
    $sectionDesc = $panel === 'admin' ? 'Busca, filtra y gestiona los equipos registrados en el sistema.' : 'Consulta los equipos y activos registrados en el sistema.';

    $items = $inventariosPaginados ?? $inventariosCompletos ?? [];
    $isPaginator = $inventariosPaginados instanceof \Illuminate\Pagination\LengthAwarePaginator;
    $itemsColeccion = collect($isPaginator ? $items->items() : $items);
    $mostrarColumnaRenta = (($resumenRentas['total'] ?? 0) > 0)
        || $itemsColeccion->contains(fn ($r) => ($r->tipo_propiedad ?? '') === 'Rentado');
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
                <x-dashboard.table-filtros
                    :departamentos-filtro="$departamentosFiltro"
                    :categorias-filtro="$categoriasFiltro"
                    :areas-filtro="$areasFiltro"
                    :aulas-filtro="$aulasFiltro"
                    :pagina-actual-ids="$paginaActualIds"
                    :selected-rows="$selectedRows"
                    :sort-column="$sortColumn"
                    :sort-direction="$sortDirection"
                    :mostrar-columna-renta="$mostrarColumnaRenta"
                >
                    <x-dashboard.table-filas
                        :panel="$panel"
                        :is-skeleton-preview="$isSkeletonPreview"
                        :items="$items"
                        :mostrar-columna-renta="$mostrarColumnaRenta"
                        :empty-message="$emptyMessage"
                    />
                </x-dashboard.table-filtros>

                <x-dashboard.table-paginacion :inventarios-paginados="$inventariosPaginados" />
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
