<x-filament-panels::page>
    <div x-data
         x-on:scroll-to-report.window="$nextTick(() => document.getElementById('sigem-reporte-visor')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
        {{-- Encabezado de la sección --}}
        <div class="sigem-rep-header">
            <h1>📊 Reportes</h1>
            <p>Genera reportes del inventario y decide cuáles puede consultar Servicio Social.</p>
        </div>

        @php
            $reportes = $this->getReportes();
            $totalVisibles = collect($reportes)->filter(fn ($r) => $this->visibilidadServicioSocial[$r['id']] ?? false)->count();
            $reporteActual = $this->reporteActivo ? $this->getDatosReporte($this->reporteActivo) : null;
        @endphp

        <div class="sigem-rep-note">
            {{ $totalVisibles }} de {{ count($reportes) }} reportes visibles para Servicio Social
        </div>

        {{-- Rejilla de tarjetas de reporte --}}
        <div class="sigem-rep-grid">
            @foreach ($reportes as $rep)
                @php
                    $esVisible = $this->visibilidadServicioSocial[$rep['id']] ?? false;
                @endphp
                <div class="sigem-rep-card">
                    <div class="sigem-rep-ico">{{ $rep['emoji'] }}</div>
                    <div class="sigem-rep-t">{{ $rep['titulo'] }}</div>
                    <div class="sigem-rep-d">{{ $rep['descripcion'] }}</div>
                    <div class="sigem-rep-foot">
                        <label class="sigem-sw" title="Activar o desactivar visibilidad para Servicio Social">
                            <input type="checkbox"
                                   wire:click="toggleVisibilidad('{{ $rep['id'] }}')"
                                   @checked($esVisible)>
                            <span class="sigem-sw-ui"></span>
                            <span class="sigem-sw-l">Visible para Servicio Social</span>
                        </label>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <button type="button"
                                    wire:click="exportarExcel('{{ $rep['id'] }}')"
                                    class="sigem-rep-btn-sec"
                                    title="Descargar este reporte en formato Excel (.xlsx)">
                                ⬇ Exportar
                            </button>
                            <button type="button"
                                    wire:click="verReporte('{{ $rep['id'] }}')"
                                    class="sigem-rep-btn-primary">
                                Ver reporte
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Visor de reporte activo (debajo de la rejilla) --}}
        @if ($reporteActual)
            <div class="sigem-rep-view" id="sigem-reporte-visor" style="margin-top: 20px; scroll-margin-top: 80px;">
                <div class="sigem-rep-vh">
                    <div>
                        <h3>{{ $reporteActual['emoji'] }} {{ $reporteActual['titulo'] }}</h3>
                        <div class="sigem-rep-meta">
                            Generado el {{ $reporteActual['fecha'] }} · {{ count($reporteActual['rows']) }} registros
                        </div>
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                        <button type="button"
                                wire:click="exportarExcel('{{ $this->reporteActivo }}')"
                                wire:loading.attr="disabled"
                                class="sigem-rep-btn-sec"
                                title="Descargar como archivo Excel (.xlsx)">
                            <span wire:loading.remove wire:target="exportarExcel">⬇ Exportar Excel</span>
                            <span wire:loading wire:target="exportarExcel" style="display: none;" class="sigem-btn-loading-content">
                                <svg class="sigem-btn-spinner" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity="0.25"></circle><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg>
                                Exportando...
                            </span>
                        </button>
                        <button type="button"
                                wire:click="cerrarReporte"
                                class="sigem-rep-btn-sec"
                                title="Cerrar vista de reporte">
                            ✕ Cerrar
                        </button>
                    </div>
                </div>

                @if (count($reporteActual['rows']) > 0)
                    <div class="sigem-rep-table-wrap">
                        <table class="sigem-rep-table">
                            <thead>
                                <tr>
                                    @foreach ($reporteActual['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reporteActual['rows'] as $row)
                                    <tr>
                                        @foreach ($row as $cell)
                                            <td>{!! $cell !!}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="sigem-rep-empty">
                        Sin registros disponibles para los filtros actuales de este reporte.
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
