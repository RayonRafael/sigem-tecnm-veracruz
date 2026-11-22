@props([
    'rep',
])

@php
    $datosFilas = $rep['rows'] ?? [];
    $tieneFilas = count($datosFilas) > 0;
    $rawChartData = $rep['chart']['datasets'][0]['data'] ?? [];
    $esVacio = ! $tieneFilas || (count($rawChartData) === 1 && ($rawChartData[0] === 0 || ($rep['chart']['labels'][0] ?? '') === 'Sin registros'));
    $datosGrafica = $esVacio ? [] : $rawChartData;
    $numPuntos = count($datosGrafica);
@endphp

<div class="sigem-new-card sigem-chart-card"
     wire:key="sigem-new-chart-{{ $rep['id'] }}"
     x-data="sigemChartComponent(@js($rep['chart'] ?? null))"
     x-init="initChart()">
    <div class="sigem-new-card-top">
        <div class="sigem-new-card-title">{{ $rep['emoji'] }} {{ $rep['titulo'] }}</div>
        @if (! empty($rep['totales_label']))
            <span class="sigem-badge-totales">{{ $rep['totales_label'] }}</span>
        @endif
    </div>

    {{-- CONTENEDOR CANVAS RESPONSIVE DE CHART.JS O MENSAJE SIN GRÁFICA --}}
    @if ($numPuntos > 0)
        <div class="sigem-chart-canvas-wrap" style="min-height: {{ $numPuntos >= 4 ? '220px' : '180px' }}; position: relative;">
            <canvas x-ref="canvas"></canvas>
        </div>
    @else
        <div class="sin-grafica" style="padding: 16px; text-align: center; color: #94a3b8; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; margin: 10px 0; font-size: 13px;">
            📊 Datos insuficientes para gráfica
        </div>
    @endif

    {{-- TABLA TOP 5 REGISTROS --}}
    <div class="sigem-mini-table-box">
        <div class="sigem-mini-table-title">📋 Top 5 registros:</div>
        <table class="sigem-mini-table">
            <thead>
                <tr>
                    @foreach (($rep['top5_headers'] ?? []) as $th)
                        <th>{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse (($rep['top5_rows'] ?? []) as $tr)
                    <tr>
                        @foreach ($tr as $td)
                            <td class="{{ preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim((string) $td)) ? 'sigem-td-nowrap' : '' }}">{{ $td }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($rep['top5_headers'] ?? [1]) }}" style="text-align: center; color: #94a3b8; padding: 6px;">
                            Sin registros disponibles
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PIE CON ACCIONES --}}
    <div class="sigem-new-card-foot">
        <button type="button"
                wire:click="verNuevoReporte('{{ $rep['id'] }}')"
                class="sigem-btn-preview"
                title="Ver vista previa detallada">
            👁️ Detalle
        </button>

        <div style="display: inline-flex; align-items: center; gap: 6px;">
            <button type="button"
                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'xlsx')"
                    class="sigem-btn-export-excel"
                    title="Descargar como archivo XLSX de Excel">
                📥 Excel
            </button>
            <button type="button"
                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'csv')"
                    class="sigem-btn-export-csv"
                    title="Descargar como archivo CSV">
                📥 CSV
            </button>
        </div>
    </div>
</div>
