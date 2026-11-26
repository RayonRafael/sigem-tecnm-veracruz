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

    $chartType = $rep['chart']['type'] ?? 'bar';
    $isPieOrDonut = in_array($chartType, ['pie', 'doughnut'], true);
    $isHorizontalBar = ($rep['chart']['options']['indexAxis'] ?? 'x') === 'y';

    $rawChartData = $rep['chart']['datasets'][0]['data'] ?? [];
    $esVacio = $totalFilas === 0 || (count($rawChartData) === 1 && ($rawChartData[0] === 0 || ($rep['chart']['labels'][0] ?? '') === 'Sin registros'));
    $datosGrafica = $esVacio ? [] : $rawChartData;
    $numPuntos = count($datosGrafica);
    $chartHash = md5(json_encode($rep['chart'] ?? []));

    if ($isPieOrDonut) {
        $canvasHeight = 400;
    } elseif ($isHorizontalBar) {
        // Altura dinámica sin scroll interno: max(350, cantidad_items × 40 + 120)
        $canvasHeight = max(350, $numPuntos * 40 + 120);
    } else {
        $canvasHeight = 360;
    }

    $tituloUi = $rep['titulo_ui'] ?? $rep['titulo'];
    $tituloLegacy = $rep['titulo'] ?? $tituloUi;
    $resumenInline = $rep['resumen_inline'] ?? ("· {$totalFilas} registros");
    $descCorta = $rep['descripcion_corta'] ?? ($rep['descripcion'] ?? '');
    $notaGrafica = $rep['nota_grafica'] ?? null;
    $chartTitleDisplay = $rep['chart_title'] ?? ($rep['chart']['chart_title'] ?? $tituloUi);

    $esColNum = fn (string $h): bool => (bool) preg_match('/^(cantidad|total|propios|rentados|solicitudes|completadas|pendientes|porcentaje|%|días|dias|mín|min|máx|max|equipos|deptos|áreas|areas|usuarios|acciones|costo|año|frecuencia)/u', mb_strtolower(trim($h), 'UTF-8'))
        || str_contains(mb_strtolower($h, 'UTF-8'), 'cantidad')
        || str_contains(mb_strtolower($h, 'UTF-8'), 'porcentaje')
        || str_contains(mb_strtolower($h, 'UTF-8'), 'días');
@endphp

<div class="sigem-rep-card sigem-rep-acc-item sigem-chart-card"
     :class="{ 'is-open': openId === '{{ $repId }}' }"
     wire:key="sigem-new-chart-{{ $repId }}">
    {{-- Fila clickeable del acordeón (descriptiva en 2 líneas: título + resumen inline + descripción visible) --}}
    <button type="button"
            class="sigem-rep-acc-trigger sigem-rep-acc-trigger-rich"
            :class="{ 'is-open': openId === '{{ $repId }}' }"
            :aria-expanded="(openId === '{{ $repId }}').toString()"
            @click="openId = (openId === '{{ $repId }}' ? null : '{{ $repId }}')">
        <span class="sigem-rep-acc-arrow"
              :class="{ 'is-open': openId === '{{ $repId }}' }"
              x-text="openId === '{{ $repId }}' ? '▼' : '▶'">{{ $esInicialmenteAbierto ? '▼' : '▶' }}</span>
        <span class="sigem-rep-ico sigem-rep-acc-ico">{{ $rep['emoji'] }}</span>
        <span class="sigem-rep-acc-meta-col">
            <span class="sigem-rep-acc-line1">
                <span class="sigem-rep-t sigem-rep-acc-title-text">{{ $tituloUi }}</span>
                @if ($tituloLegacy !== $tituloUi)
                    <span class="sr-only">{{ $tituloLegacy }}</span>
                @endif
                @if (! empty($resumenInline))
                    <span class="sigem-rep-acc-summary">{{ $resumenInline }}</span>
                @endif
            </span>
            @if (! empty($descCorta))
                <span class="sigem-rep-acc-subdesc">{{ $descCorta }}</span>
            @endif
        </span>
        <span class="sigem-rep-acc-badge" title="{{ $totalFilas }} registros">{{ $totalFilas }}</span>
    </button>

    {{-- Panel colapsable: Chart.js ancho completo sin scroll interno + nota + controles + tabla paginada --}}
    <div class="sigem-rep-acc-panel accordion-content"
         x-show="openId === '{{ $repId }}'"
         @if (! $esInicialmenteAbierto) x-cloak @endif
         x-transition:enter="sigem-acc-enter"
         x-transition:enter-start="sigem-acc-enter-start"
         x-transition:enter-end="sigem-acc-enter-end"
         x-transition:leave="sigem-acc-leave"
         x-transition:leave-start="sigem-acc-leave-start"
         x-transition:leave-end="sigem-acc-leave-end">
        {{-- CONTENEDOR CANVAS DE ANCHO COMPLETO SIN SCROLL INTERNO (crece según los datos) --}}
        <div class="sigem-hybrid-chart-body"
             wire:ignore
             wire:key="sigem-chart-box-{{ $repId }}-{{ $chartHash }}"
             x-data="sigemChartComponent(@js($rep['chart'] ?? null))"
             x-init="initChart()"
             x-effect="if (openId === '{{ $repId }}') { $nextTick(() => ensureRendered()); }">
            @if ($numPuntos > 0)
                <div class="sigem-chart-canvas-wrap {{ $isPieOrDonut ? 'sigem-chart-radial-wrap' : 'sigem-chart-wide-wrap' }}"
                     style="min-height: {{ $canvasHeight }}px; height: {{ $canvasHeight }}px; position: relative; overflow: visible;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="sin-grafica" style="padding: 24px; text-align: center; color: #94a3b8; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; font-size: 13.5px;">
                    📊 Datos insuficientes para gráfica
                </div>
            @endif

            @if (! empty($notaGrafica))
                <div class="sigem-chart-note">
                    <span>📌</span>
                    <span><strong>Nota:</strong> {{ $notaGrafica }}</span>
                </div>
            @endif
        </div>

        {{-- Controles arriba de la tabla: Toggle SS + Excel + CSV --}}
        <div class="sigem-hybrid-chart-controls">
            @if ($esAdmin)
                <label class="sigem-sw" title="Activar o desactivar visibilidad para Servicio Social">
                    <input type="checkbox"
                           wire:click="toggleVisibilidad('{{ $repId }}')"
                           @checked($esVisible)>
                    <span class="sigem-sw-ui"></span>
                    <span class="sigem-sw-l">Visible para Servicio Social</span>
                </label>
            @else
                <span class="sigem-pill ok" style="font-size: 12.5px;">✓ Autorizado</span>
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

        {{-- TABLA COMPLETA (TODAS LAS COLUMNAS, ZEBRA, HOVER, BADGES, PAGINADA 10 FILAS) --}}
        <div class="sigem-rep-table-wrap sigem-hybrid-chart-table-wrap">
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
                                <x-reportes.tabla-celda :header="$headers[$cIdx] ?? ''" :valor="$celda" />
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($headers)) }}" style="text-align: center; color: #94a3b8; padding: 18px;">
                                Sin registros disponibles para los filtros actuales
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pie con conteo y paginación (10 por página) --}}
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
