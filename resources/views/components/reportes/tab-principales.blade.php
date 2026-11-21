@props([
    'livewire',
    'esAdmin' => true,
    'listaReportes' => [],
    'notaVisibilidad' => '',
    'reporteActual' => null,
])

<section class="sigem-rep-original-section" style="margin-bottom: 36px;">
    <div class="sigem-rep-header" style="margin-bottom: 12px;">
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

    {{-- Rejilla de los 6 reportes originales --}}
    <div class="sigem-rep-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
        @forelse ($listaReportes as $rep)
            @php
                $esVisible = $livewire->visibilidadServicioSocial[$rep['id']] ?? false;
            @endphp
            <div class="sigem-rep-card" wire:key="sigem-rep-card-{{ $rep['id'] }}">
                <div class="sigem-rep-ico">{{ $rep['emoji'] }}</div>
                <div class="sigem-rep-t">{{ $rep['titulo'] }}</div>
                <div class="sigem-rep-d">{{ $rep['descripcion'] }}</div>
                <div class="sigem-rep-foot">
                    @if($esAdmin)
                        <label class="sigem-sw" title="Activar o desactivar visibilidad para Servicio Social">
                            <input type="checkbox"
                                   wire:click="toggleVisibilidad('{{ $rep['id'] }}')"
                                   @checked($esVisible)>
                            <span class="sigem-sw-ui"></span>
                            <span class="sigem-sw-l">Visible para Servicio Social</span>
                        </label>
                    @else
                        <span class="sigem-pill ok" style="font-size: 13px;">✓ Autorizado</span>
                    @endif
                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                        <button type="button"
                                wire:click="exportarReporte('{{ $rep['id'] }}', 'xlsx')"
                                class="sigem-btn-primary"
                                title="Descargar como archivo XLSX de Excel">
                            ↓ Excel
                        </button>
                        <button type="button"
                                wire:click="verReporte('{{ $rep['id'] }}')"
                                class="sigem-rep-btn-sec"
                                title="Ver vista previa de los datos">
                            Ver
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sigem-rep-empty" style="grid-column: 1 / -1; padding: 32px; text-align: center; background: white; border-radius: 12px; border: 1px solid var(--sig-border);">
                No hay reportes autorizados actualmente por el Administrador.
            </div>
        @endforelse
    </div>

    {{-- Vista previa del reporte legacy cuando se solicita (abajo de las tarjetas) --}}
    @if ($reporteActual)
        <div class="sigem-rep-view" id="sigem-reporte-visor-legacy" style="margin-top: 24px;">
            <div class="sigem-rep-vh">
                <div>
                    <h3>{{ $reporteActual['emoji'] ?? '📊' }} {{ $reporteActual['titulo'] }}</h3>
                    <div class="sigem-rep-meta">
                        {{ count($reporteActual['rows']) }} registros
                    </div>
                </div>
                <div style="display: inline-flex; align-items: center; gap: 8px;">
                    <button type="button"
                            wire:click="exportarReporte('{{ $livewire->reporteActivo }}', 'xlsx')"
                            class="sigem-btn-primary">
                        ↓ Exportar Excel (.xlsx)
                    </button>
                    <button type="button"
                            wire:click="exportarReporte('{{ $livewire->reporteActivo }}', 'csv')"
                            class="sigem-rep-btn-sec">
                        ↓ Exportar CSV
                    </button>
                    <button type="button"
                            wire:click="cerrarReporte"
                            class="sigem-rep-btn-sec">
                        ✕ Cerrar
                    </button>
                </div>
            </div>

            <div class="sigem-rep-table-wrap sigem-scroll-top">
                <table class="sigem-rep-table">
                    <thead>
                        <tr>
                            @foreach ($reporteActual['headers'] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reporteActual['rows'] as $row)
                            <tr>
                                @foreach ($row as $celda)
                                    <td>{!! $celda !!}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($reporteActual['headers']) }}" style="text-align: center; color: #94a3b8; padding: 20px;">
                                    Sin registros disponibles para este reporte
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
