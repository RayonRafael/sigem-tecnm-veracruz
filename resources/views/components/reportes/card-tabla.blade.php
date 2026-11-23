@props([
    'rep',
    'livewire',
    'esAdmin' => true,
    'esInicialmenteAbierto' => false,
])

@php
    $repId = $rep['id'];
    $porPagina = \App\Filament\Pages\ReportesPage::NUEVOS_REPORTES_POR_PAGINA;
    $esVisible = $livewire->visibilidadServicioSocial[$repId] ?? true;

    $headers = $rep['headers'] ?? [];
    $todasFilas = $rep['rows'] ?? [];
    $totalFilas = count($todasFilas);
    $totalPaginas = max(1, (int) ceil($totalFilas / $porPagina));
    $paginaActual = min($totalPaginas, max(1, $livewire->getPaginaNuevoReporte($repId)));
    $offset = ($paginaActual - 1) * $porPagina;
    $filasPagina = array_slice($todasFilas, $offset, $porPagina);
    $desde = $totalFilas > 0 ? $offset + 1 : 0;
    $hasta = min($totalFilas, $offset + count($filasPagina));
@endphp

<div class="sigem-rep-card sigem-rep-acc-item"
     :class="{ 'is-open': openId === '{{ $repId }}' }"
     wire:key="sigem-new-table-{{ $repId }}">
    {{-- Fila clickeable del acordeón --}}
    <button type="button"
            class="sigem-rep-acc-trigger"
            :class="{ 'is-open': openId === '{{ $repId }}' }"
            :aria-expanded="(openId === '{{ $repId }}').toString()"
            @click="openId = (openId === '{{ $repId }}' ? null : '{{ $repId }}')">
        <span class="sigem-rep-acc-arrow"
              :class="{ 'is-open': openId === '{{ $repId }}' }"
              x-text="openId === '{{ $repId }}' ? '▼' : '▶'">{{ $esInicialmenteAbierto ? '▼' : '▶' }}</span>
        <span class="sigem-rep-ico sigem-rep-acc-ico">{{ $rep['emoji'] }}</span>
        <span class="sigem-rep-t sigem-rep-acc-title">{{ $rep['titulo'] }}</span>
        <span class="sigem-rep-acc-badge" title="{{ $totalFilas }} registros">{{ $totalFilas }}</span>
    </button>

    {{-- Panel colapsable: descripción + toggle SS + Excel/CSV + tabla completa + paginación --}}
    <div class="sigem-rep-acc-panel accordion-content"
         x-show="openId === '{{ $repId }}'"
         @if (! $esInicialmenteAbierto) x-cloak @endif
         x-transition:enter="sigem-acc-enter"
         x-transition:enter-start="sigem-acc-enter-start"
         x-transition:enter-end="sigem-acc-enter-end"
         x-transition:leave="sigem-acc-leave"
         x-transition:leave-start="sigem-acc-leave-start"
         x-transition:leave-end="sigem-acc-leave-end">
        <div class="sigem-rep-acc-toolbar">
            @if (! empty($rep['descripcion']))
                <div class="sigem-rep-d" style="margin-bottom: 10px;">{{ $rep['descripcion'] }}</div>
            @endif

            <div class="sigem-rep-foot sigem-rep-principal-controls">
                @if ($esAdmin)
                    <label class="sigem-sw" title="Activar o desactivar visibilidad para Servicio Social">
                        <input type="checkbox"
                               wire:click="toggleVisibilidad('{{ $repId }}')"
                               @checked($esVisible)>
                        <span class="sigem-sw-ui"></span>
                        <span class="sigem-sw-l">Visible para Servicio Social</span>
                    </label>
                @else
                    <span class="sigem-pill ok" style="font-size: 13px;">✓ Autorizado</span>
                @endif

                <div style="display: inline-flex; align-items: center; gap: 8px;">
                    <button type="button"
                            wire:click="exportarNuevoReporte('{{ $repId }}', 'xlsx')"
                            class="sigem-btn-primary"
                            title="Descargar como archivo XLSX de Excel">
                        ↓ Excel
                    </button>
                    <button type="button"
                            wire:click="exportarNuevoReporte('{{ $repId }}', 'csv')"
                            class="sigem-rep-btn-sec"
                            title="Descargar como archivo CSV">
                        ↓ CSV
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla completa con TODAS las columnas y scroll horizontal si supera ancho --}}
        <div class="sigem-rep-table-wrap sigem-rep-principal-table-wrap">
            <table class="sigem-rep-table sigem-rep-principal-table">
                <thead>
                    <tr>
                        @foreach ($headers as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filasPagina as $row)
                        <tr>
                            @foreach ((array) $row as $celda)
                                <td class="{{ preg_match('/^(\d{2}\/\d{2}\/\d{4}|\d{2}:\d{2})$/', trim(strip_tags((string) $celda))) ? 'sigem-td-nowrap' : '' }}">{!! $celda !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($headers)) }}" style="text-align: center; color: #94a3b8; padding: 20px;">
                                Sin registros disponibles para los filtros actuales
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie de la tabla: conteo y paginación (10 por página) --}}
        <div class="sigem-rep-principal-footer">
            <div class="sigem-rep-meta">
                @if ($totalFilas === 0)
                    Mostrando 0 de 0 registros
                @elseif ($totalFilas <= $porPagina)
                    Mostrando {{ $totalFilas }} de {{ $totalFilas }} {{ $totalFilas === 1 ? 'registro' : 'registros' }}
                @else
                    Mostrando {{ $desde }}–{{ $hasta }} de {{ $totalFilas }} registros
                @endif
            </div>

            @if ($totalPaginas > 1)
                <div class="sigem-rep-principal-pagination">
                    <button type="button"
                            wire:click="cambiarPaginaNuevoReporte('{{ $repId }}', {{ $paginaActual - 1 }})"
                            @disabled($paginaActual <= 1)
                            class="sigem-rep-page-btn">
                        ← Anterior
                    </button>
                    <span class="sigem-rep-page-indicator">
                        Página {{ $paginaActual }} de {{ $totalPaginas }}
                    </span>
                    <button type="button"
                            wire:click="cambiarPaginaNuevoReporte('{{ $repId }}', {{ $paginaActual + 1 }})"
                            @disabled($paginaActual >= $totalPaginas)
                            class="sigem-rep-page-btn">
                        Siguiente →
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
