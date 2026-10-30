<x-filament-panels::page>
    <div>
        {{-- Encabezado principal --}}
        <div class="sigem-rep-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1>📁 Catálogos</h1>
                <span class="sigem-pill ro">👁 Solo lectura</span>
            </div>
            <p>Consulta los catálogos del instituto en modo solo lectura.</p>
        </div>

        @php
            $catalogos = $this->getCatalogosList();
            $catActivo = $this->getDatosCatalogoActivo();
        @endphp

        {{-- Franja informativa celeste --}}
        <div class="sigem-note-strip">
            <span style="font-size: 20px; line-height: 1;">💡</span>
            <div>
                Esta información es de referencia para consultar los activos, áreas y materiales registrados en el instituto.
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
            <div class="sigem-rep-vh" style="flex-wrap: wrap; gap: 14px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3>{{ $catActivo['emoji'] }} Catálogo de {{ $catActivo['nombre'] }}</h3>
                        <span class="sigem-pill ro">👁 Solo lectura</span>
                    </div>
                    <div class="sigem-rep-meta">
                        {{ $catActivo['total'] }} registros encontrados
                    </div>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="position: relative;">
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ $this->getPlaceholder() }}"
                               style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--sig-border); font-size: 13.5px; min-width: 260px; outline: none;">
                    </div>
                    @if (filled($search))
                        <button type="button"
                                wire:click="$set('search', '')"
                                class="sigem-rep-btn-sec"
                                title="Quitar búsqueda">
                            ✕ Quitar filtros
                        </button>
                    @endif
                    @php
                        $routeIndex = match ($catalogoActivo) {
                            'areas' => url('/servicio-social/areas'),
                            'materiales' => url('/servicio-social/materials'),
                            'marcas' => url('/servicio-social/marca-materials'),
                            'tipos' => url('/servicio-social/tipo-materials'),
                            'unidades' => url('/servicio-social/unidad-medidas'),
                            'receptores' => url('/servicio-social/receptors'),
                            default => null,
                        };
                    @endphp
                    @if ($routeIndex)
                        <a href="{{ $routeIndex }}"
                           class="sigem-rep-btn-sec"
                           title="Abrir la consulta completa de este catálogo">
                            Ver listado completo →
                        </a>
                    @endif
                </div>
            </div>

            @if (count($catActivo['rows']) > 0)
                <div class="sigem-rep-table-wrap">
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
                            @foreach ($catActivo['rows'] as $row)
                                @php $cellsCount = count($row); @endphp
                                <tr>
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
                    Sin registros coincidentes en este catálogo por ahora.
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
