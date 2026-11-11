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
    $formatosPdf = $livewire->getFormatosInstitucionales();
    $deptosFiltros = $livewire->getDepartamentosParaFiltros();
    $solicitudesEntrega = $livewire->getSolicitudesParaFormato();
    $mantenimientosOrden = $livewire->getMantenimientosParaOrdenFormato();
    $mantenimientosVerif = $livewire->getMantenimientosParaVerificacionFormato();
    $inventariosBaja = $livewire->getInventariosBajaParaFormato();
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
        <div class="sigem-rep-view" id="sigem-reporte-visor" x-on:scroll-to-report.window="$el.scrollIntoView({behavior:'smooth'})">
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

            @if (count($reporteActual['rows']) > 0)
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
                            @foreach ($reporteActual['rows'] as $row)
                                <tr>
                                    @foreach ($row as $celda)
                                        <td>{!! $celda !!}</td>
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

    {{-- Separador y sección Formatos Institucionales (PDF) --}}
    <hr class="sigem-fmt-divider">

    <section class="sigem-fmt-section" aria-labelledby="sigem-fmt-heading">
        <div class="sigem-fmt-header">
            <h2 id="sigem-fmt-heading">📄 Formatos Institucionales (PDF)</h2>
            <p>Genera los formatos oficiales del TecNM Veracruz</p>
        </div>

        <div class="sigem-fmt-grid">
            @foreach ($formatosPdf as $fmt)
                @php
                    $fmtId = $fmt['id'];
                @endphp
                <div class="sigem-fmt-card">
                    <div class="sigem-fmt-card-top">
                        <div class="sigem-fmt-ico">{{ $fmt['emoji'] }}</div>
                        <span class="sigem-fmt-badge">{{ $fmt['subtitulo'] }}</span>
                    </div>
                    <div class="sigem-fmt-title">{{ $fmt['titulo'] }}</div>
                    <div class="sigem-fmt-desc">{{ $fmt['descripcion'] }}</div>

                    <div class="sigem-fmt-picker">
                        @if ($fmtId === 'entrega')
                            <label class="sigem-fmt-label">Solicitud de entrega ({{ $solicitudesEntrega->count() }})</label>
                            <select wire:model="formatoSeleccionadoEntrega" class="sigem-fmt-control">
                                <option value="">
                                    {{ $solicitudesEntrega->isNotEmpty() ? 'Más reciente (' . $solicitudesEntrega->count() . ' disp.)' : 'Sin solicitudes en el filtro' }}
                                </option>
                                @foreach ($solicitudesEntrega as $sol)
                                    @php
                                        $folioSol = 'FOLIO-' . str_pad((string) $sol->id_solicitud, 5, '0', STR_PAD_LEFT);
                                        $receptorSol = $sol->receptor ? trim(($sol->receptor->nombre ?? '') . ' ' . ($sol->receptor->apellido_paterno ?? '')) : 'Sin receptor';
                                        $fechaSol = $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y') : '—';
                                    @endphp
                                    <option value="{{ $sol->id_solicitud }}">
                                        {{ $folioSol }} · {{ $receptorSol }} · {{ $fechaSol }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif ($fmtId === 'orden_trabajo')
                            <label class="sigem-fmt-label">Mantenimiento ({{ $mantenimientosOrden->count() }})</label>
                            <select wire:model="formatoSeleccionadoOrden" class="sigem-fmt-control">
                                <option value="">
                                    {{ $mantenimientosOrden->isNotEmpty() ? 'Más reciente (' . $mantenimientosOrden->count() . ' disp.)' : 'Sin órdenes en el filtro' }}
                                </option>
                                @foreach ($mantenimientosOrden as $mant)
                                    @php
                                        $folioMant = '#' . str_pad((string) $mant->id_mantenimiento, 4, '0', STR_PAD_LEFT);
                                        $eqNom = $mant->inventario?->codigo ?: ($mant->inventario?->material?->nombre ?? 'Equipo');
                                        $fechaMant = $mant->fecha_solicitud ? $mant->fecha_solicitud->format('d/m/Y') : '—';
                                    @endphp
                                    <option value="{{ $mant->id_mantenimiento }}">
                                        {{ $folioMant }} · {{ $eqNom }} · {{ $fechaMant }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif ($fmtId === 'lista_verificacion')
                            <label class="sigem-fmt-label">Mantenimiento atendido ({{ $mantenimientosVerif->count() }})</label>
                            <select wire:model="formatoSeleccionadoVerificacion" class="sigem-fmt-control">
                                <option value="">
                                    {{ $mantenimientosVerif->isNotEmpty() ? 'Más reciente (' . $mantenimientosVerif->count() . ' disp.)' : 'Sin registros en el filtro' }}
                                </option>
                                @foreach ($mantenimientosVerif as $mantV)
                                    @php
                                        $folioV = '#' . str_pad((string) $mantV->id_mantenimiento, 4, '0', STR_PAD_LEFT);
                                        $estadoTag = $mantV->estado === 'Completado' ? '✓ Terminado' : $mantV->estado;
                                        $eqV = $mantV->inventario?->codigo ?: ($mantV->inventario?->material?->nombre ?? 'Equipo');
                                    @endphp
                                    <option value="{{ $mantV->id_mantenimiento }}">
                                        {{ $folioV }} · {{ $eqV }} ({{ $estadoTag }})
                                    </option>
                                @endforeach
                            </select>
                        @elseif ($fmtId === 'baja')
                            <label class="sigem-fmt-label">Equipos en baja ({{ $inventariosBaja->count() }})</label>
                            @if ($inventariosBaja->isNotEmpty())
                                <div class="sigem-fmt-checklist">
                                    @foreach ($inventariosBaja as $invBaja)
                                        @php
                                            $codBaja = $invBaja->codigo ?: ($invBaja->num_serie ?: ('#' . $invBaja->id_inventario));
                                            $nomBaja = $invBaja->material?->nombre ?? $invBaja->subtipo ?? 'Equipo';
                                        @endphp
                                        <label class="sigem-fmt-check-item">
                                            <input type="checkbox"
                                                   wire:model="formatosSeleccionadosBaja"
                                                   value="{{ $invBaja->id_inventario }}">
                                            <span><strong>{{ $codBaja }}</strong> · {{ $nomBaja }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="sigem-fmt-empty-note">Sin equipos en baja para el filtro actual.</div>
                            @endif
                        @endif
                    </div>

                    <div class="sigem-fmt-foot">
                        <button type="button"
                                wire:click="descargarFormatoInstitucional('{{ $fmtId }}')"
                                class="sigem-pdf-btn"
                                title="Descargar {{ $fmt['titulo'] }} en PDF">
                            ↓ Descargar
                        </button>
                    </div>
                </div>
            @endforeach

            {{-- Tarjeta 5: Filtros históricos --}}
            <div class="sigem-fmt-card sigem-fmt-filters-card">
                <div class="sigem-fmt-card-top">
                    <div class="sigem-fmt-ico sigem-fmt-ico-filter">🔍</div>
                    <span class="sigem-fmt-badge">Histórico</span>
                </div>
                <div class="sigem-fmt-title">Filtros de regeneración</div>
                <div class="sigem-fmt-desc">Filtra los registros por rango de fechas y departamento.</div>

                <div class="sigem-fmt-filters-form">
                    <div class="sigem-fmt-filter-row">
                        <label class="sigem-fmt-label" for="sigem-fmt-desde">Desde:</label>
                        <input id="sigem-fmt-desde"
                               type="date"
                               wire:model.live="formatoFechaDesde"
                               class="sigem-fmt-control">
                    </div>
                    <div class="sigem-fmt-filter-row">
                        <label class="sigem-fmt-label" for="sigem-fmt-hasta">Hasta:</label>
                        <input id="sigem-fmt-hasta"
                               type="date"
                               wire:model.live="formatoFechaHasta"
                               class="sigem-fmt-control">
                    </div>
                    <div class="sigem-fmt-filter-row">
                        <label class="sigem-fmt-label" for="sigem-fmt-depto">Depto:</label>
                        <select id="sigem-fmt-depto"
                                wire:model.live="formatoDepartamentoId"
                                class="sigem-fmt-control">
                            <option value="">Todos</option>
                            @foreach ($deptosFiltros as $idDepto => $nombreDepto)
                                <option value="{{ $idDepto }}">{{ $nombreDepto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sigem-fmt-filter-row">
                        <label class="sigem-fmt-label" for="sigem-fmt-tipo-lote">Formato:</label>
                        <select id="sigem-fmt-tipo-lote"
                                wire:model="formatoFiltroTipo"
                                class="sigem-fmt-control">
                            <option value="baja">Trámite de Baja ({{ $inventariosBaja->count() }})</option>
                            <option value="entrega">Entrega de Equipo ({{ $solicitudesEntrega->count() }})</option>
                            <option value="orden_trabajo">Orden de Trabajo ({{ $mantenimientosOrden->count() }})</option>
                            <option value="lista_verificacion">Lista Verificación ({{ $mantenimientosVerif->count() }})</option>
                        </select>
                    </div>
                </div>

                <div class="sigem-fmt-foot" style="justify-content: space-between;">
                    <button type="button"
                            wire:click="limpiarFiltrosFormatos"
                            class="sigem-rep-btn-sec"
                            title="Limpiar filtros">
                        Limpiar
                    </button>
                    <button type="button"
                            wire:click="generarFormatosFiltrados"
                            class="sigem-pdf-btn"
                            title="Generar PDFs de los registros filtrados">
                        📄 Generar seleccionados
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
