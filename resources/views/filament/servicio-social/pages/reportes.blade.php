<x-filament-panels::page>
    <div x-data
         x-on:scroll-to-report.window="$nextTick(() => document.getElementById('sigem-reporte-visor')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
        {{-- Encabezado de la sección --}}
        <div class="sigem-rep-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1>📊 Reportes</h1>
                <span class="sigem-pill ok">✓ Autorizado</span>
            </div>
            <p>Consulta métricas, estadísticas y reportes autorizados del Centro de Cómputo.</p>
        </div>

        @php
            $reportes = $this->getReportes();
            // Filtrar únicamente los reportes autorizados por el Administrador
            $reportesVisibles = collect($reportes)->filter(fn ($r) => $this->visibilidadServicioSocial[$r['id']] ?? false)->values();
            $reporteActual = $this->reporteActivo ? $this->getDatosReporte($this->reporteActivo) : null;
        @endphp

        <div class="sigem-rep-note">
            {{ $reportesVisibles->count() }} de {{ count($reportes) }} reportes autorizados para Servicio Social
        </div>

        {{-- Rejilla de tarjetas de reporte --}}
        <div class="sigem-rep-grid">
            @forelse ($reportesVisibles as $rep)
                <div class="sigem-rep-card">
                    <div class="sigem-rep-ico">{{ $rep['emoji'] }}</div>
                    <div class="sigem-rep-t">{{ $rep['titulo'] }}</div>
                    <div class="sigem-rep-d">{{ $rep['descripcion'] }}</div>
                    <div class="sigem-rep-foot">
                        <span class="sigem-pill ok" style="font-size: 13px;">✓ Autorizado</span>
                        <button type="button"
                                wire:click="verReporte('{{ $rep['id'] }}')"
                                class="sigem-rep-btn-primary">
                            Ver reporte
                        </button>
                    </div>
                </div>
            @empty
                <div class="sigem-rep-empty" style="grid-column: 1 / -1; padding: 32px; text-align: center; background: white; border-radius: 12px; border: 1px solid var(--sig-border);">
                    No hay reportes autorizados actualmente por el Administrador.
                </div>
            @endforelse
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
                                wire:click="exportarReporte('{{ $this->reporteActivo }}')"
                                class="sigem-rep-btn-sec"
                                title="Descargar como archivo CSV compatible con Excel">
                            ⬇ Exportar
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
                        Sin registros por ahora.
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
