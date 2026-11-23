@props([
    'livewire',
    'esAdmin' => true,
    'listaReportes' => [],
    'notaVisibilidad' => '',
    'datosReportes' => [],
])

@php
    $porPagina = \App\Filament\Pages\ReportesPage::REPORTES_PRINCIPALES_POR_PAGINA;
    $listaColeccion = collect($listaReportes)->values();
    $primerReporteId = $listaColeccion->first()['id'] ?? null;
    $esColNum = fn (string $h): bool => (bool) preg_match('/^(cantidad|total|propio|rentado|disponible|en mantto|asignado|cambio|solicitud|pendiente|autorizado|entregado|rechazado)/u', mb_strtolower(trim($h), 'UTF-8'));
@endphp

<section class="sigem-rep-original-section" style="margin-bottom: 36px;">
    <div class="sigem-rep-header" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div>
                <h2 style="font-size: 19px; font-weight: 700; color: #1b396a; margin: 0 0 4px 0;">
                    📊 Reportes Principales del Inventario
                </h2>
                <p style="font-size: 13px; color: #64748b; margin: 0;">
                    Genera reportes del inventario y decide cuáles puede consultar Servicio Social.
                </p>
            </div>
            <div class="sigem-rep-note" style="margin: 0; font-size: 12.5px; font-weight: 600; color: #1b396a; background: #eff6ff; padding: 6px 12px; border-radius: 8px; border: 1px solid #bfdbfe;">
                {{ $notaVisibilidad }}
            </div>
        </div>
    </div>

    {{-- Acordeón de Reportes Principales (1 abierto a la vez, primero abierto inicialmente) --}}
    @if ($listaColeccion->isNotEmpty())
        <div class="sigem-rep-accordion"
             x-data="{ openId: @js($primerReporteId) }">
            @foreach ($listaColeccion as $idx => $rep)
                @php
                    $repId = $rep['id'];
                    $esPrimero = $idx === 0;
                    $esVisible = $livewire->visibilidadServicioSocial[$repId] ?? false;
                    $datos = $datosReportes[$repId] ?? ['headers' => [], 'rows' => []];
                    $headers = $datos['headers'] ?? [];
                    $todasFilas = $datos['rows'] ?? [];
                    $totalFilas = count($todasFilas);
                    $totalPaginas = max(1, (int) ceil($totalFilas / $porPagina));
                    $paginaActual = min($totalPaginas, max(1, $livewire->getPaginaReportePrincipal($repId)));
                    $offset = ($paginaActual - 1) * $porPagina;
                    $filasPagina = array_slice($todasFilas, $offset, $porPagina);
                    $desde = $totalFilas > 0 ? $offset + 1 : 0;
                    $hasta = min($totalFilas, $offset + count($filasPagina));
                @endphp

                <div class="sigem-rep-card sigem-rep-acc-item"
                     :class="{ 'is-open': openId === '{{ $repId }}' }"
                     wire:key="sigem-rep-card-{{ $repId }}">
                    {{-- Fila clickeable del acordeón --}}
                    <button type="button"
                            class="sigem-rep-acc-trigger"
                            :class="{ 'is-open': openId === '{{ $repId }}' }"
                            :aria-expanded="(openId === '{{ $repId }}').toString()"
                            @click="openId = (openId === '{{ $repId }}' ? null : '{{ $repId }}')">
                        <span class="sigem-rep-acc-arrow"
                              :class="{ 'is-open': openId === '{{ $repId }}' }"
                              x-text="openId === '{{ $repId }}' ? '▼' : '▶'">{{ $esPrimero ? '▼' : '▶' }}</span>
                        <span class="sigem-rep-ico sigem-rep-acc-ico">{{ $rep['emoji'] }}</span>
                        <span class="sigem-rep-t sigem-rep-acc-title">{{ $rep['titulo'] }}</span>
                        <span class="sigem-rep-acc-badge" title="{{ $totalFilas }} registros">{{ $totalFilas }}</span>
                    </button>

                    {{-- Contenido colapsable: descripción + toggle SS + Excel/CSV + tabla + paginación --}}
                    <div class="sigem-rep-acc-panel accordion-content"
                         x-show="openId === '{{ $repId }}'"
                         @if (! $esPrimero) x-cloak @endif
                         x-transition:enter="sigem-acc-enter"
                         x-transition:enter-start="sigem-acc-enter-start"
                         x-transition:enter-end="sigem-acc-enter-end"
                         x-transition:leave="sigem-acc-leave"
                         x-transition:leave-start="sigem-acc-leave-start"
                         x-transition:leave-end="sigem-acc-leave-end">
                        <div class="sigem-rep-acc-toolbar">
                            <div class="sigem-rep-d" style="margin-bottom: 10px;">{{ $rep['descripcion'] }}</div>

                            <div class="sigem-rep-foot sigem-rep-principal-controls">
                                @if($esAdmin)
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
                                            wire:click="exportarReporte('{{ $repId }}', 'xlsx')"
                                            class="sigem-btn-primary"
                                            title="Descargar como archivo XLSX de Excel">
                                        ↓ Excel
                                    </button>
                                    <button type="button"
                                            wire:click="exportarReporte('{{ $repId }}', 'csv')"
                                            class="sigem-rep-btn-sec"
                                            title="Descargar como archivo CSV">
                                        ↓ CSV
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Tabla del reporte --}}
                        <div class="sigem-rep-table-wrap sigem-rep-principal-table-wrap">
                            <table class="sigem-rep-table sigem-rep-principal-table">
                                <thead>
                                    <tr>
                                        @foreach ($headers as $header)
                                            <th class="{{ $esColNum($header) ? 'sigem-col-num' : '' }}">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($filasPagina as $row)
                                        <tr>
                                            @foreach (array_values((array) $row) as $cIdx => $celda)
                                                @php $hdr = $headers[$cIdx] ?? ''; @endphp
                                                <td class="{{ $esColNum($hdr) ? 'sigem-col-num' : '' }}">{!! $celda !!}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ max(1, count($headers)) }}" style="text-align: center; color: #94a3b8; padding: 20px;">
                                                Sin registros disponibles para este reporte
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pie del reporte: conteo y paginación (10 por tabla) --}}
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
                                            wire:click="cambiarPaginaReportePrincipal('{{ $repId }}', {{ $paginaActual - 1 }})"
                                            @disabled($paginaActual <= 1)
                                            class="sigem-rep-page-btn">
                                        ← Anterior
                                    </button>
                                    <span class="sigem-rep-page-indicator">
                                        Página {{ $paginaActual }} de {{ $totalPaginas }}
                                    </span>
                                    <button type="button"
                                            wire:click="cambiarPaginaReportePrincipal('{{ $repId }}', {{ $paginaActual + 1 }})"
                                            @disabled($paginaActual >= $totalPaginas)
                                            class="sigem-rep-page-btn">
                                        Siguiente →
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="sigem-rep-empty" style="padding: 32px; text-align: center; background: white; border-radius: 12px; border: 1px solid var(--sig-border);">
            No hay reportes autorizados actualmente por el Administrador.
        </div>
    @endif
</section>
