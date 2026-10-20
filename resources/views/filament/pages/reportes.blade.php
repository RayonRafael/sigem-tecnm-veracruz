<x-filament-panels::page>
    <div>
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

        {{-- Visor de reporte activo --}}
        @if ($reporteActual)
            <div class="sigem-rep-view">
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
                        <table class="fi-ta-table">
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
                        <button type="button"
                                wire:click="verReporte('{{ $rep['id'] }}')"
                                class="sigem-rep-btn-primary">
                            Ver reporte
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
