@props([
    'panel' => 'admin',
    'livewire',
])

@php
    $reportes = $livewire->getReportes();
    $esAdmin = $panel === 'admin';

    if ($esAdmin) {
        $listaReportes = $reportes;
        $totalVisibles = collect($reportes)->filter(fn ($r) => $livewire->visibilidadServicioSocial[$r['id']] ?? false)->count();
        $notaVisibilidad = "{$totalVisibles} de " . count($reportes) . " reportes visibles para Servicio Social";
    } else {
        $listaReportes = collect($reportes)->filter(fn ($r) => $livewire->visibilidadServicioSocial[$r['id']] ?? false)->values();
        $notaVisibilidad = $listaReportes->count() . " de " . count($reportes) . " reportes autorizados para Servicio Social";
    }

    $reporteActual = $livewire->reporteActivo ? $livewire->getDatosReporte($livewire->reporteActivo) : null;
@endphp

<div class="sigem-professional">
    {{-- Encabezado de la sección --}}
    <div class="sigem-rep-header">
        @if($esAdmin)
            <h1>📊 Reportes</h1>
            <p>Genera reportes del inventario y decide cuáles puede consultar Servicio Social.</p>
        @else
            <div style="display: flex; align-items: center; gap: 10px;">
                <h1>📊 Reportes</h1>
                <span class="sigem-pill ok">✓ Autorizado</span>
            </div>
            <p>Consulta métricas, estadísticas y reportes autorizados del Centro de Cómputo.</p>
        @endif
    </div>

    <div class="sigem-rep-note">
        {{ $notaVisibilidad }}
    </div>

    {{-- Rejilla de tarjetas de reporte --}}
    <div class="sigem-rep-grid">
        @forelse ($listaReportes as $rep)
            @php
                $esVisible = $livewire->visibilidadServicioSocial[$rep['id']] ?? false;
            @endphp
            <div class="sigem-rep-card">
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

    {{-- Vista previa del reporte seleccionado --}}
    @if ($reporteActual)
        <div class="sigem-rep-view">
            <div class="sigem-rep-view-head">
                <div>
                    <h2>{{ $reporteActual['titulo'] }}</h2>
                    <span class="sigem-rep-view-count">{{ count($reporteActual['rows']) }} registros</span>
                </div>
                <div class="sigem-rep-view-actions">
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
                            wire:click="cerrarVistaPrevia"
                            class="sigem-rep-btn-sec">
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
                                    @foreach ($row as $celda)
                                        <td>{{ $celda }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="sigem-rep-empty">
                    {{ $esAdmin ? 'Sin registros disponibles para los filtros actuales de este reporte.' : 'Sin registros disponibles para este reporte.' }}
                </div>
            @endif
        </div>
    @endif
</div>
