@props([
    'catalogos' => [],
    'catalogoActivo' => 'departamentos',
    'catActivo' => [],
    'placeholder' => 'Buscar en este catálogo...',
    'tieneFiltrosActivos' => false,
    'opcionesFiltros' => [],
])

{{-- Franja informativa celeste --}}
<div class="sigem-note-strip">
    <span style="font-size: 20px; line-height: 1;">💡</span>
    <div>
        Los catálogos alimentan los formularios del sistema (altas de equipos, solicitudes, mantenimientos).
        Cárgalos al inicio del ciclo; después solo necesitarás ediciones puntuales.
    </div>
</div>

{{-- Fila de chips de catálogo con conteos --}}
<div class="sigem-cat-tabs">
    @foreach ($catalogos as $cat)
        <button type="button"
                wire:click="setCatalogo('{{ $cat['id'] }}')"
                class="sigem-cat-tab {{ $catalogoActivo === $cat['id'] ? 'active' : '' }}"
                title="Ver catálogo de {{ $cat['nombre'] }}">
            {{ $cat['emoji'] }} {{ $cat['nombre'] }}
            <b>{{ $cat['total'] }}</b>
        </button>
    @endforeach
</div>

{{-- Tabla del catálogo seleccionado --}}
<div class="sigem-rep-view">
    <div class="sigem-rep-vh">
        <div>
            <h3>{{ $catActivo['emoji'] }} Catálogo de {{ $catActivo['nombre'] }}</h3>
            <div class="sigem-rep-meta">
                {{ $catActivo['total'] }} registros registrados en la base de datos
            </div>
        </div>
        <div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button"
                    wire:click="mountAction('{{ $catActivo['crearAccion'] }}')"
                    class="sigem-rep-btn-primary"
                    title="Crear nuevo registro en este catálogo">
                ＋ Nuevo {{ $catActivo['singular'] }}
            </button>
            <a href="{{ $catActivo['indexUrl'] }}"
               class="sigem-rep-btn-sec"
               title="Abrir la gestión completa con filtros y paginado">
                Gestionar en catálogo completo →
            </a>
        </div>
    </div>

    {{-- Barra de búsqueda y filtros contextuales del catálogo activo --}}
    <div class="sigem-bitacora-filters" style="border-bottom: 1px solid var(--sig-border); margin-bottom: 0; padding: 10px 16px;">
        <input type="text"
               wire:model.live.debounce.300ms="catalogoSearch"
               placeholder="{{ $placeholder }}"
               style="min-width: 240px; flex: 1;">

        @if ($catActivo['id'] === 'areas' || $catActivo['id'] === 'receptores')
            <select wire:model.live="filtroDepartamento" style="min-width: 180px;">
                <option value="">Todos los departamentos</option>
                @foreach (($opcionesFiltros['departamentos'] ?? []) as $depId => $depNom)
                    <option value="{{ $depId }}">{{ $depNom }}</option>
                @endforeach
            </select>
        @elseif ($catActivo['id'] === 'materiales')
            <select wire:model.live="filtroTipoMaterial" style="min-width: 170px;">
                <option value="">Todos los tipos</option>
                @foreach (($opcionesFiltros['tipos'] ?? []) as $tipoId => $tipoNom)
                    <option value="{{ $tipoId }}">{{ $tipoNom }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroMarca" style="min-width: 150px;">
                <option value="">Todas las marcas</option>
                @foreach (($opcionesFiltros['marcas'] ?? []) as $marcaId => $marcaNom)
                    <option value="{{ $marcaId }}">{{ $marcaNom }}</option>
                @endforeach
            </select>
        @elseif ($catActivo['id'] === 'usuarios')
            <select wire:model.live="filtroRol" style="min-width: 150px;">
                <option value="">Todos los roles</option>
                @foreach (($opcionesFiltros['roles'] ?? []) as $rolName)
                    <option value="{{ $rolName }}">{{ $rolName }}</option>
                @endforeach
            </select>
            <select wire:model.live="filtroEstadoUsuario" style="min-width: 140px;">
                <option value="">Todos los estados</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        @endif

        @if ($tieneFiltrosActivos)
            <button type="button"
                    wire:click="limpiarFiltrosCatalogo"
                    class="sigem-rep-btn-sec"
                    title="Restablecer filtros">
                ✕ Quitar filtros
            </button>
        @endif
    </div>

    @if (count($catActivo['rows'] ?? []) > 0)
        <div class="sigem-rep-table-wrap sigem-scroll-top">
            <table class="fi-ta-table sigem-cat-table sigem-cat-{{ $catActivo['id'] }}">
                <thead>
                    <tr>
                        @php $headersCount = count($catActivo['headers']); @endphp
                        @foreach ($catActivo['headers'] as $idx => $header)
                            <th class="{{ $idx === 0 ? 'sigem-col-id' : ($idx === $headersCount - 1 ? 'sigem-col-actions' : 'sigem-col-content') }}">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($catActivo['rows'] as $rowIdx => $row)
                        @php $cellsCount = count($row); @endphp
                        <tr wire:key="cat-{{ $catActivo['id'] }}-{{ $rowIdx }}-{{ md5((string) ($row[0] ?? $rowIdx)) }}">
                            @foreach ($row as $idx => $cell)
                                <td class="{{ $idx === 0 ? 'sigem-col-id' : ($idx === $cellsCount - 1 ? 'sigem-col-actions fi-ta-actions-cell' : 'sigem-col-content') }}">{!! $cell !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="sigem-rep-empty">
            Sin registros en este catálogo por ahora.
        </div>
    @endif
</div>
