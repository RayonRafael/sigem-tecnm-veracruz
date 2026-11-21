@props([
    'panel' => 'admin',
    'livewire',
])

@php
    $esAdmin = $panel === 'admin';
    $pestanaActual = $livewire->pestanaPrincipal ?? 'principales';
    $esPestanaPrincipales = in_array($pestanaActual, ['principales', 'reportes'], true);
    $esPestanaFormatos = $pestanaActual === 'formatos';
    $esPestanaNuevos = in_array($pestanaActual, ['nuevos', 'nuevos_reportes'], true);
@endphp

@once
    <link rel="stylesheet" href="{{ asset('css/sigem-reportes.css') }}?v={{ file_exists(public_path('css/sigem-reportes.css')) ? filemtime(public_path('css/sigem-reportes.css')) : 1 }}">
@endonce

<div class="sigem-professional">
    {{-- Encabezado institucional SIGEM --}}
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

    {{-- Pestañas Principales (3): Reportes Principales, Formatos PDF, Nuevos Reportes --}}
    <div class="sigem-main-nav-tabs">
        <button type="button"
                wire:click="cambiarPestanaPrincipal('principales')"
                class="sigem-nav-tab {{ $esPestanaPrincipales ? 'is-active' : '' }}"
                title="6 Reportes Principales del Inventario">
            <span>📋</span>
            <span>Reportes Principales</span>
            <span class="sigem-nav-tab-badge">6</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaPrincipal('formatos')"
                class="sigem-nav-tab {{ $esPestanaFormatos ? 'is-active' : '' }}"
                title="5 Formatos Institucionales Oficiales en PDF">
            <span>📄</span>
            <span>Formatos PDF</span>
            <span class="sr-only">Formatos Institucionales (PDF) — Entrega de Equipo, Orden de Trabajo, Lista Verificación, Trámite de Baja, Descargar seleccionados</span>
            <span class="sigem-nav-tab-badge">5</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaPrincipal('nuevos')"
                class="sigem-nav-tab {{ $esPestanaNuevos ? 'is-active' : '' }}"
                title="28 Nuevos Reportes con gráficas">
            <span>📈</span>
            <span>Nuevos Reportes</span>
            <span class="sigem-nav-tab-badge is-highlight">28 con gráficas</span>
        </button>
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 1: REPORTES PRINCIPALES (6)                      --}}
    {{-- ======================================================== --}}
    @if ($esPestanaPrincipales)
        @php
            $reportes = $livewire->getReportes();
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

        <x-reportes.tab-principales
            :livewire="$livewire"
            :esAdmin="$esAdmin"
            :listaReportes="$listaReportes"
            :notaVisibilidad="$notaVisibilidad"
            :reporteActual="$reporteActual"
        />
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 2: FORMATOS INSTITUCIONALES (PDF)                --}}
    {{-- ======================================================== --}}
    @if ($esPestanaFormatos)
        <x-reportes.tab-formatos
            :livewire="$livewire"
            :pestanaFormatoActiva="$livewire->pestanaFormatoActiva ?? 'entrega'"
        />
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 3: NUEVOS REPORTES (12 CON GRÁFICA + 16 TABLA)   --}}
    {{-- ======================================================== --}}
    @if ($esPestanaNuevos)
        @php
            $catalogoNuevos = \App\Support\SigemReportExporter::getCatalogo();
            $subpestanas = \App\Support\SigemReportExporter::getSubpestanas();
            $subpestanaActual = $livewire->subpestanaReportes ?? 'inventario';
            $deptosFiltros = $livewire->departamentosParaFiltros;
            $reportesSubpestana = $livewire->getReportesSubpestanaActual();

            $repsConGrafica = collect($reportesSubpestana)->filter(fn ($r) => ! empty($r['has_chart']))->values();
            $repsSoloTabla = collect($reportesSubpestana)->filter(fn ($r) => empty($r['has_chart']))->values();

            $datosPreviaNuevo = null;
            if ($livewire->nuevoReporteActivo) {
                $datosPreviaNuevo = collect($reportesSubpestana)->firstWhere('id', $livewire->nuevoReporteActivo)
                    ?? $livewire->getDatosNuevoReporte($livewire->nuevoReporteActivo);
            }
        @endphp

        {{-- Barra de 5 Sub-pestañas --}}
        <div class="sigem-subtabs-bar">
            @foreach ($subpestanas as $claveSub => $nombreSub)
                <button type="button"
                        wire:key="sigem-subtab-{{ $claveSub }}"
                        wire:click="cambiarSubpestanaReportes('{{ $claveSub }}')"
                        class="sigem-subtab-btn {{ $subpestanaActual === $claveSub ? 'is-active' : '' }}">
                    <span>{{ $nombreSub }}</span>
                    <span style="font-size: 11px; opacity: 0.85;">({{ count($catalogoNuevos[$claveSub] ?? []) }})</span>
                </button>
            @endforeach
        </div>

        {{-- Barra de Filtros para los 28 reportes --}}
        <x-reportes.filtros :livewire="$livewire" :deptosFiltros="$deptosFiltros" />

        {{-- SECCIÓN 1: Tarjetas CON Gráfica (2 por fila, 50% cada una) --}}
        @if ($repsConGrafica->isNotEmpty())
            <div class="sigem-new-rep-grid-charts sigem-new-rep-grid" wire:key="sigem-charts-grid-{{ $subpestanaActual }}">
                @foreach ($repsConGrafica as $rep)
                    <x-reportes.card-grafica :rep="$rep" />
                @endforeach
            </div>
        @endif

        {{-- SECCIÓN 2: Tarjetas SOLO Tabla (3 por fila, 33% cada una) --}}
        @if ($repsSoloTabla->isNotEmpty())
            <div class="sigem-new-rep-grid-tables sigem-new-rep-grid" wire:key="sigem-tables-grid-{{ $subpestanaActual }}">
                @foreach ($repsSoloTabla as $rep)
                    <x-reportes.card-tabla :rep="$rep" />
                @endforeach
            </div>
        @endif

        @if ($repsConGrafica->isEmpty() && $repsSoloTabla->isEmpty())
            <div style="padding: 24px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b; margin-bottom: 32px;">
                No hay reportes configurados para esta sección.
            </div>
        @endif

        {{-- Vista previa del nuevo reporte seleccionado (abajo del grid) --}}
        @if ($datosPreviaNuevo)
            <div class="sigem-rep-view" id="sigem-reporte-visor" wire:key="sigem-reporte-visor-detalle" x-on:scroll-to-report.window="$el.scrollIntoView({behavior:'smooth'})" style="margin-top: 28px;">
                <div class="sigem-rep-vh">
                    <div>
                        <h3>📊 {{ $datosPreviaNuevo['titulo'] }}</h3>
                        <div class="sigem-rep-meta">
                            Vista previa (primeros 5 registros de {{ $datosPreviaNuevo['total'] ?? $datosPreviaNuevo['total_registros'] ?? count($datosPreviaNuevo['rows']) }} totales)
                        </div>
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                        <button type="button"
                                wire:click="exportarNuevoReporte('{{ $livewire->nuevoReporteActivo }}', 'xlsx')"
                                class="sigem-btn-primary">
                            ↓ Exportar Excel (.xlsx)
                        </button>
                        <button type="button"
                                wire:click="exportarNuevoReporte('{{ $livewire->nuevoReporteActivo }}', 'csv')"
                                class="sigem-rep-btn-sec">
                            ↓ Exportar CSV
                        </button>
                        <button type="button"
                                wire:click="cerrarNuevoReporte"
                                class="sigem-rep-btn-sec">
                            ✕ Cerrar
                        </button>
                    </div>
                </div>

                @if (count($datosPreviaNuevo['rows']) > 0)
                    <div class="sigem-rep-table-wrap sigem-scroll-top">
                        <table class="sigem-rep-table">
                            <thead>
                                <tr>
                                    @foreach ($datosPreviaNuevo['headers'] as $header)
                                        <th>{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($datosPreviaNuevo['rows'], 0, 5) as $row)
                                    <tr>
                                        @foreach ((array) $row as $celda)
                                            <td>{!! $celda !!}</td>
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
    @endif
</div>

{{-- Carga diferida segura de Chart.js y componente Alpine optimizado --}}
@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="{{ asset('js/sigem-reportes-charts.js') }}?v={{ file_exists(public_path('js/sigem-reportes-charts.js')) ? filemtime(public_path('js/sigem-reportes-charts.js')) : 1 }}" defer></script>
@endonce
