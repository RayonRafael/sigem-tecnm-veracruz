@props([
    'panel' => 'admin',
    'livewire',
])

@php
    $esAdmin = $panel === 'admin';
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

    $formatosPdf = $livewire->getFormatosInstitucionales(true);
    $deptosFiltros = $livewire->getDepartamentosParaFiltros();
    $solicitudesEntrega = $livewire->getSolicitudesParaFormato();
    $mantenimientosOrden = $livewire->getMantenimientosParaOrdenFormato();
    $mantenimientosVerif = $livewire->getMantenimientosParaVerificacionFormato();
    $inventariosBaja = $livewire->getInventariosBajaParaFormato();
    $inventariosCambio = $livewire->getInventariosCambioAreaParaFormato();

    $catalogoNuevos = \App\Support\SigemReportExporter::getCatalogo();
    $subpestanas = \App\Support\SigemReportExporter::getSubpestanas();
    $subpestanaActual = $livewire->subpestanaReportes ?? 'inventario';
    $reportesSubpestana = $livewire->getReportesSubpestanaActual();

    $datosPreviaNuevo = $livewire->nuevoReporteActivo ? $livewire->getDatosNuevoReporte($livewire->nuevoReporteActivo) : null;
    $pestanaActual = $livewire->pestanaPrincipal ?? 'formatos';
    $pestanaFormatoActiva = $livewire->pestanaFormatoActiva ?? 'entrega';
@endphp

<div class="sigem-professional">
    <style>
        .sigem-main-nav-tabs {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 24px;
            padding-bottom: 4px;
            flex-wrap: wrap;
        }
        .sigem-nav-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px 8px 0 0;
            border: none;
            background: transparent;
            font-size: 14px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .sigem-nav-tab:hover {
            color: #1b396a;
            background: #f8fafc;
        }
        .sigem-nav-tab.is-active {
            color: #1b396a;
            font-weight: 700;
            border-bottom: 3px solid #1b396a;
            background: #eff6ff;
        }
        .sigem-nav-tab-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
            font-weight: 700;
        }
        .sigem-nav-tab.is-active .sigem-nav-tab-badge {
            background: #1b396a;
            color: #ffffff;
        }
        .sigem-nav-tab-badge.is-highlight {
            background: #dbeafe;
            color: #1e40af;
        }
        .sigem-subtabs-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px;
            margin-bottom: 20px;
        }
        .sigem-subtab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .sigem-subtab-btn:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #1b396a;
        }
        .sigem-subtab-btn.is-active {
            background: #1b396a;
            color: #ffffff;
            border-color: #1b396a;
            box-shadow: 0 2px 6px rgba(27, 57, 106, 0.25);
        }
        .sigem-subtab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
            background: rgba(15, 23, 42, 0.08);
            color: inherit;
        }
        .sigem-subtab-btn.is-active .sigem-subtab-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .sigem-fmt-tab-card {
            max-width: 760px;
            width: 100%;
            padding: 22px 24px;
        }
        .sigem-fmt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        .sigem-fmt-table thead th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            padding: 10px 14px;
            border-bottom: 2px solid #cbd5e1;
            white-space: nowrap;
        }
        .sigem-fmt-table tbody tr {
            transition: background 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .sigem-fmt-table tbody tr:hover {
            background: #eff6ff;
        }
        .sigem-fmt-table tbody td {
            padding: 10px 14px;
            color: #334155;
            vertical-align: middle;
        }
        .sigem-pdf-actions {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .sigem-btn-pdf-view {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .sigem-btn-pdf-view:hover {
            background: #dbeafe;
            transform: scale(1.08);
            color: #1e40af;
        }
        .sigem-btn-pdf-download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .sigem-btn-pdf-download:hover {
            background: #d1fae5;
            transform: scale(1.08);
            color: #047857;
        }
        .sigem-filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }
        .sigem-filter-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sigem-filter-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }
        .sigem-filter-input {
            padding: 6px 10px;
            font-size: 12.5px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .sigem-filter-input:focus {
            border-color: #1b396a;
            background: #ffffff;
        }
        /* Selects unificados y sin flechas duplicadas (Problema 2) */
        select.sigem-filter-input,
        .sigem-filter-toolbar select,
        .sigem-filter-group select,
        .sigem-rep-filter-select,
        .sigem-rep-filter select,
        .sigem-fmt-section select {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-color: #ffffff !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            background-size: 12px 12px !important;
            padding-right: 36px !important;
            cursor: pointer;
        }
        select.sigem-filter-input::-ms-expand,
        .sigem-filter-toolbar select::-ms-expand,
        .sigem-filter-group select::-ms-expand,
        .sigem-fmt-section select::-ms-expand {
            display: none !important;
        }
        .sigem-subtabs-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
        .sigem-new-rep-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 24px;
            margin-bottom: 32px;
            align-items: start;
        }
        .sigem-new-rep-grid-charts {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
            align-items: start;
        }
        .sigem-new-rep-grid-tables {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 32px;
            align-items: start;
        }
        /* Tarjetas CON gráfica: ocupan 3 de 6 columnas (medio ancho = 2 por fila) */
        .sigem-new-card.sigem-chart-card {
            grid-column: span 3;
        }
        /* Tarjetas SOLO tabla: ocupan 2 de 6 columnas (3 por fila) */
        .sigem-new-card.sigem-table-card {
            grid-column: span 2;
        }
        .sigem-new-rep-grid-charts .sigem-new-card.sigem-chart-card,
        .sigem-new-rep-grid-tables .sigem-new-card.sigem-table-card {
            grid-column: auto;
        }
        @media (max-width: 1024px) {
            .sigem-new-rep-grid-charts {
                grid-template-columns: 1fr;
            }
            .sigem-new-rep-grid-tables {
                grid-template-columns: repeat(2, 1fr);
            }
            .sigem-new-card.sigem-chart-card {
                grid-column: span 6;
            }
            .sigem-new-card.sigem-table-card {
                grid-column: span 3;
            }
        }
        @media (max-width: 768px) {
            .sigem-new-rep-grid,
            .sigem-new-rep-grid-charts,
            .sigem-new-rep-grid-tables {
                grid-template-columns: 1fr;
            }
            .sigem-new-card.sigem-chart-card,
            .sigem-new-card.sigem-table-card {
                grid-column: span 1;
            }
        }
        .sigem-new-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            height: auto;
            align-self: start;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .sigem-new-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        .sigem-new-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }
        .sigem-badge-totales {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 9px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
            margin-left: 12px;
        }
        .sigem-new-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.35;
            min-height: 48px;
            display: flex;
            align-items: center;
            flex: 1;
        }
        .sigem-new-card-desc {
            font-size: 13px;
            color: #64748b;
            line-height: 1.45;
            margin-bottom: 12px;
        }
        .sigem-chart-canvas-wrap {
            position: relative;
            min-height: 220px;
            width: 100%;
            margin: 8px 0;
            background: #fafafa;
            border-radius: 8px;
            padding: 8px;
            box-sizing: border-box;
            overflow: hidden;
        }
        .sigem-mini-table-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 16px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        .sigem-mini-table-title {
            font-size: 11.5px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .sigem-mini-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
        }
        .sigem-mini-table th {
            text-align: left;
            font-weight: 600;
            color: #64748b;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
            background: #f1f5f9;
            white-space: nowrap;
        }
        .sigem-mini-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            word-break: break-word;
        }
        .sigem-mini-table td.sigem-td-nowrap {
            white-space: nowrap !important;
        }
        .sigem-mini-table tr:last-child td {
            border-bottom: none;
        }
        .sigem-new-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
            margin-top: auto;
        }
        .sigem-btn-export-excel {
            background: #1b396a;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .sigem-btn-export-excel:hover {
            background: #002e5f;
        }
        .sigem-btn-export-csv {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .sigem-btn-export-csv:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .sigem-btn-preview {
            background: transparent;
            color: #1b396a;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .sigem-btn-preview:hover {
            background: #eff6ff;
        }
    </style>

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
                class="sigem-nav-tab {{ in_array($pestanaActual, ['principales', 'reportes'], true) ? 'is-active' : '' }}"
                title="6 Reportes Principales del Inventario">
            <span>📋</span>
            <span>Reportes Principales</span>
            <span class="sigem-nav-tab-badge">6</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaPrincipal('formatos')"
                class="sigem-nav-tab {{ $pestanaActual === 'formatos' ? 'is-active' : '' }}"
                title="5 Formatos Institucionales Oficiales en PDF">
            <span>📄</span>
            <span>Formatos PDF</span>
            <span class="sr-only">Formatos Institucionales (PDF) — Entrega de Equipo, Orden de Trabajo, Lista Verificación, Trámite de Baja, Descargar seleccionados</span>
            <span class="sigem-nav-tab-badge">5</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaPrincipal('nuevos')"
                class="sigem-nav-tab {{ in_array($pestanaActual, ['nuevos', 'nuevos_reportes'], true) ? 'is-active' : '' }}"
                title="28 Nuevos Reportes con gráficas">
            <span>📈</span>
            <span>Nuevos Reportes</span>
            <span class="sigem-nav-tab-badge is-highlight">28 con gráficas</span>
        </button>
    </div>

    {{-- ======================================================== --}}
    {{-- PESTAÑA 1: REPORTES PRINCIPALES (6)                      --}}
    {{-- ======================================================== --}}
    @if (in_array($pestanaActual, ['principales', 'reportes'], true))
        {{-- SECCIÓN 1: LOS 6 REPORTES ORIGINALES --}}
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

            {{-- Vista previa del reporte legacy cuando se solicita (ABAJO DE LAS TARJETAS - Problema 1) --}}
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
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 2: FORMATOS INSTITUCIONALES (PDF)                --}}
    {{-- ======================================================== --}}
    @if ($pestanaActual === 'formatos')
        <section class="sigem-fmt-section" aria-labelledby="sigem-fmt-heading" style="margin-bottom: 32px;">
            <div class="sigem-fmt-header" style="margin-bottom: 16px;">
                <h2 id="sigem-fmt-heading">📄 Formatos Institucionales (PDF)</h2>
                <p>Genera los formatos oficiales del TecNM Veracruz con membrete institucional y firma electrónica</p>
            </div>

            {{-- Sistema de Pestañas de Formatos --}}
            <div class="sigem-subtabs-bar" role="tablist" style="margin-bottom: 20px;">
                <button type="button"
                        wire:click="cambiarPestanaFormato('entrega')"
                        title="Entrega de Equipo"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'entrega' ? 'is-active' : '' }}">
                    <span>📋 Entrega</span>
                    <span class="sr-only">de Equipo</span>
                    <span class="sigem-subtab-count">({{ $solicitudesEntrega->count() }})</span>
                </button>

                <button type="button"
                        wire:click="cambiarPestanaFormato('orden_trabajo')"
                        title="Orden de Trabajo"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'orden_trabajo' ? 'is-active' : '' }}">
                    <span>🔧 Orden</span>
                    <span class="sr-only">de Trabajo</span>
                    <span class="sigem-subtab-count">({{ $mantenimientosOrden->count() }})</span>
                </button>

                <button type="button"
                        wire:click="cambiarPestanaFormato('lista_verificacion')"
                        title="Lista Verificación"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'lista_verificacion' ? 'is-active' : '' }}">
                    <span>✅ Lista</span>
                    <span class="sr-only">Verificación</span>
                    <span class="sigem-subtab-count">({{ $mantenimientosVerif->count() }})</span>
                </button>

                <button type="button"
                        wire:click="cambiarPestanaFormato('baja')"
                        title="Trámite de Baja"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'baja' ? 'is-active' : '' }}">
                    <span>⚠️ Baja</span>
                    <span class="sr-only">Trámite de Baja</span>
                    <span class="sigem-subtab-count">({{ $inventariosBaja->count() }})</span>
                </button>

                <button type="button"
                        wire:click="cambiarPestanaFormato('cambio_area')"
                        title="Formato de Cambio de Área"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'cambio_area' ? 'is-active' : '' }}">
                    <span>🔄 Cambio</span>
                    <span class="sr-only">de Área</span>
                    <span class="sigem-subtab-count">({{ $inventariosCambio->count() }})</span>
                </button>

                <button type="button"
                        wire:click="cambiarPestanaFormato('filtrar')"
                        title="Filtrar formatos existentes"
                        class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'filtrar' ? 'is-active' : '' }}">
                    <span>🔍 Filtrar existentes</span>
                    <span class="sr-only">Filtrar formatos existentes — Descargar seleccionados</span>
                </button>
            </div>

            {{-- Tablas Desglosadas por Sub-pestaña --}}
            <div class="sigem-fmt-tab-content">
                {{-- TABLA 1: ENTREGA DE EQUIPO --}}
                @if ($pestanaFormatoActiva === 'entrega')
                    <div class="sigem-rep-table-wrap">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">📋 Entrega de Equipo</h3>
                                <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Actas oficiales de entrega de equipo con receptor y unidad orgánica ({{ $solicitudesEntrega->count() }} registros)</p>
                            </div>
                            @if ($solicitudesEntrega->isNotEmpty())
                                <button type="button"
                                        wire:click="descargarFormatoInstitucional('entrega')"
                                        class="sigem-btn-export-excel"
                                        title="Descargar la solicitud más reciente">
                                    ↓ Descargar más reciente
                                </button>
                            @endif
                        </div>
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Equipo</th>
                                    <th>Receptor</th>
                                    <th>Departamento</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($solicitudesEntrega as $sol)
                                    @php
                                        $folioSol = 'FOLIO-' . str_pad((string) $sol->id_solicitud, 5, '0', STR_PAD_LEFT);
                                        $primerDetalle = $sol->detalles->first();
                                        $nomEq = $primerDetalle?->material?->nombre ?? ($primerDetalle?->material?->subtipo ?? 'Equipo asignado');
                                        $receptorSol = $sol->receptor ? trim(($sol->receptor->nombre ?? '') . ' ' . ($sol->receptor->apellido_paterno ?? '')) : 'Sin receptor';
                                        $deptoSol = $sol->receptor?->area?->departamento?->nombre ?? ($sol->receptor?->area?->nombre ?? '—');
                                        $fechaSol = $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y') : '—';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $folioSol }}</strong></td>
                                        <td>{{ $nomEq }}</td>
                                        <td>{{ $receptorSol }}</td>
                                        <td>{{ $deptoSol }}</td>
                                        <td>{{ $fechaSol }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'entrega', 'id' => $sol->id_solicitud, 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'entrega', 'id' => $sol->id_solicitud, 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                {{-- TABLA 2: ORDEN DE TRABAJO --}}
                @elseif ($pestanaFormatoActiva === 'orden_trabajo')
                    <div class="sigem-rep-table-wrap">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">🔧 Orden de Trabajo</h3>
                                <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Órdenes de mantenimiento y servicio técnico del Centro de Cómputo ({{ $mantenimientosOrden->count() }} registros)</p>
                            </div>
                            @if ($mantenimientosOrden->isNotEmpty())
                                <button type="button"
                                        wire:click="descargarFormatoInstitucional('orden_trabajo')"
                                        class="sigem-btn-export-excel"
                                        title="Descargar la orden más reciente">
                                    ↓ Descargar más reciente
                                </button>
                            @endif
                        </div>
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Equipo</th>
                                    <th>Tipo servicio</th>
                                    <th>Técnico</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mantenimientosOrden as $mant)
                                    @php
                                        $folioMant = 'MANT-' . str_pad((string) $mant->id_mantenimiento, 4, '0', STR_PAD_LEFT);
                                        $eqNom = $mant->inventario?->codigo ?: ($mant->inventario?->material?->nombre ?? 'Equipo');
                                        $tipoServ = $mant->tipo_servicio ?: ($mant->tipo ?: 'Preventivo');
                                        $tecnico = $mant->tecnico ? trim(($mant->tecnico->nombre ?? '') . ' ' . ($mant->tecnico->apellido_paterno ?? '')) : ($mant->usuarioSolicita ? trim(($mant->usuarioSolicita->nombre ?? '') . ' ' . ($mant->usuarioSolicita->apellido_paterno ?? '')) : 'Centro de Cómputo');
                                        $fechaMant = $mant->fecha_solicitud ? $mant->fecha_solicitud->format('d/m/Y') : '—';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $folioMant }}</strong></td>
                                        <td>{{ $eqNom }}</td>
                                        <td>{{ $tipoServ }}</td>
                                        <td>{{ $tecnico }}</td>
                                        <td>{{ $fechaMant }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'orden_trabajo', 'id' => $mant->id_mantenimiento, 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'orden_trabajo', 'id' => $mant->id_mantenimiento, 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                {{-- TABLA 3: LISTA DE VERIFICACIÓN --}}
                @elseif ($pestanaFormatoActiva === 'lista_verificacion')
                    <div class="sigem-rep-table-wrap">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">✅ Lista Verificación</h3>
                                <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Listas de verificación con hallazgo, atención y validación técnica ({{ $mantenimientosVerif->count() }} registros)</p>
                            </div>
                            @if ($mantenimientosVerif->isNotEmpty())
                                <button type="button"
                                        wire:click="descargarFormatoInstitucional('lista_verificacion')"
                                        class="sigem-btn-export-excel"
                                        title="Descargar la lista más reciente">
                                    ↓ Descargar más reciente
                                </button>
                            @endif
                        </div>
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Equipo</th>
                                    <th>Estado</th>
                                    <th>Atendido por</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mantenimientosVerif as $mantV)
                                    @php
                                        $folioV = 'MANT-' . str_pad((string) $mantV->id_mantenimiento, 4, '0', STR_PAD_LEFT);
                                        $eqV = $mantV->inventario?->codigo ?: ($mantV->inventario?->material?->nombre ?? 'Equipo');
                                        $estadoTag = $mantV->estado;
                                        $atendidoPor = $mantV->tecnico ? trim(($mantV->tecnico->nombre ?? '') . ' ' . ($mantV->tecnico->apellido_paterno ?? '')) : ($mantV->usuarioSolicita ? trim(($mantV->usuarioSolicita->nombre ?? '') . ' ' . ($mantV->usuarioSolicita->apellido_paterno ?? '')) : 'Centro de Cómputo');
                                        $fechaV = $mantV->fecha_fin ? $mantV->fecha_fin->format('d/m/Y') : ($mantV->fecha_solicitud ? $mantV->fecha_solicitud->format('d/m/Y') : '—');
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $folioV }}</strong></td>
                                        <td>{{ $eqV }}</td>
                                        <td>
                                            <span class="sigem-pill {{ $estadoTag === 'Completado' ? 'ok' : 'warn' }}" style="font-size: 11.5px;">
                                                {{ $estadoTag === 'Completado' ? '✓ ' . $estadoTag : $estadoTag }}
                                            </span>
                                        </td>
                                        <td>{{ $atendidoPor }}</td>
                                        <td>{{ $fechaV }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'lista_verificacion', 'id' => $mantV->id_mantenimiento, 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'lista_verificacion', 'id' => $mantV->id_mantenimiento, 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                {{-- TABLA 4: TRÁMITE DE BAJA --}}
                @elseif ($pestanaFormatoActiva === 'baja')
                    <div class="sigem-rep-table-wrap">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">⚠️ Trámite de Baja</h3>
                                <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Equipos con trámite o dictamen de baja para desincorporación ({{ $inventariosBaja->count() }} registros)</p>
                            </div>
                            @if ($inventariosBaja->isNotEmpty())
                                <button type="button"
                                        wire:click="descargarFormatoInstitucional('baja')"
                                        class="sigem-pdf-btn"
                                        title="Descargar lote completo de bajas en PDF">
                                    ↓ Descargar lote completo en PDF
                                </button>
                            @endif
                        </div>
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Equipo</th>
                                    <th>Motivo</th>
                                    <th>Departamento</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventariosBaja as $invBaja)
                                    @php
                                        $codBaja = $invBaja->codigo ?: ($invBaja->num_serie ?: ('#' . $invBaja->id_inventario));
                                        $nomBaja = $invBaja->material?->nombre ?? ($invBaja->subtipo ?? 'Equipo');
                                        $motivoBaja = $invBaja->motivo_baja ?? 'Daño severo';
                                        $deptoBaja = $invBaja->departamento?->nombre ?? ($invBaja->area?->departamento?->nombre ?? '—');
                                        $fechaBaja = $invBaja->fecha_baja ? \Illuminate\Support\Carbon::parse($invBaja->fecha_baja)->format('d/m/Y') : '—';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $codBaja }}</strong></td>
                                        <td>{{ $nomBaja }}</td>
                                        <td><span class="sigem-pill warn" style="font-size: 11.5px;">{{ $motivoBaja }}</span></td>
                                        <td>{{ $deptoBaja }}</td>
                                        <td>{{ $fechaBaja }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'baja', 'id' => $invBaja->id_inventario, 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'baja', 'id' => $invBaja->id_inventario, 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                {{-- TABLA 5: CAMBIO DE ÁREA --}}
                @elseif ($pestanaFormatoActiva === 'cambio_area')
                    <div class="sigem-rep-table-wrap">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">🔄 Cambio de Área</h3>
                                <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Constancias oficiales de reubicación y traslado de bienes ({{ $inventariosCambio->count() }} registros)</p>
                            </div>
                            @if ($inventariosCambio->isNotEmpty())
                                <button type="button"
                                        wire:click="descargarFormatoInstitucional('cambio_area')"
                                        class="sigem-btn-export-excel"
                                        title="Descargar el cambio de área más reciente">
                                    ↓ Descargar más reciente
                                </button>
                            @endif
                        </div>
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Equipo</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($inventariosCambio as $invC)
                                    @php
                                        $codC = $invC->codigo ?: ($invC->num_serie ?: ('#' . $invC->id_inventario));
                                        $nomC = $invC->material?->nombre ?? ($invC->subtipo ?? 'Equipo');
                                        $origenC = $invC->especificaciones['cambio_origen_depto'] ?? ($invC->departamento?->nombre ?? '—');
                                        $destinoC = $invC->departamento?->nombre ?? ($invC->area?->departamento?->nombre ?? '—');
                                        $fechaC = $invC->fecha_cambio_area ? \Illuminate\Support\Carbon::parse($invC->fecha_cambio_area)->format('d/m/Y') : '—';
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $codC }}</strong></td>
                                        <td>{{ $nomC }}</td>
                                        <td>{{ $origenC }}</td>
                                        <td>{{ $destinoC }}</td>
                                        <td>{{ $fechaC }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'cambio_area', 'id' => $invC->id_inventario, 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => 'cambio_area', 'id' => $invC->id_inventario, 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                {{-- TABLA 6: FILTRAR FORMATOS EXISTENTES --}}
                @elseif ($pestanaFormatoActiva === 'filtrar')
                    <div class="sigem-rep-table-wrap">
                        <div style="margin-bottom: 14px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;">🔍 Filtrar formatos existentes</h3>
                            <p style="font-size: 12.5px; color: #64748b; margin: 2px 0 0 0;">Busca y descarga formatos ya generados</p>
                        </div>

                        {{-- Toolbar de filtros --}}
                        <div class="sigem-filter-toolbar" style="margin-bottom: 16px;">
                            <div class="sigem-filter-group">
                                <span class="sigem-filter-label">Desde:</span>
                                <input type="date"
                                       wire:model.live="formatoFechaDesde"
                                       class="sigem-filter-input">
                            </div>
                            <div class="sigem-filter-group">
                                <span class="sigem-filter-label">Hasta:</span>
                                <input type="date"
                                       wire:model.live="formatoFechaHasta"
                                       class="sigem-filter-input">
                            </div>
                            <div class="sigem-filter-group">
                                <span class="sigem-filter-label">Depto:</span>
                                <select wire:model.live="formatoDepartamentoId"
                                        class="sigem-filter-input">
                                    <option value="">Todos</option>
                                    @foreach ($deptosFiltros as $idDepto => $nombreDepto)
                                        <option value="{{ $idDepto }}">{{ $nombreDepto }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sigem-filter-group">
                                <span class="sigem-filter-label">Formato:</span>
                                <select wire:model.live="formatoFiltroTipo"
                                        class="sigem-filter-input">
                                    <option value="baja">Trámite de Baja ({{ $inventariosBaja->count() }})</option>
                                    <option value="entrega">Entrega de Equipo ({{ $solicitudesEntrega->count() }})</option>
                                    <option value="orden_trabajo">Orden de Trabajo ({{ $mantenimientosOrden->count() }})</option>
                                    <option value="lista_verificacion">Lista Verificación ({{ $mantenimientosVerif->count() }})</option>
                                    <option value="cambio_area">Cambio de Área ({{ $inventariosCambio->count() }})</option>
                                </select>
                            </div>

                            <div style="display: flex; gap: 8px; margin-left: auto;">
                                <button type="button"
                                        wire:click="limpiarFiltrosFormatos"
                                        class="sigem-rep-btn-sec"
                                        title="Limpiar filtros">
                                    Limpiar
                                </button>
                                <button type="button"
                                        wire:click="generarFormatosFiltrados"
                                        class="sigem-pdf-btn"
                                        title="Descargar formatos de los registros filtrados">
                                    📥 Descargar seleccionados
                                </button>
                            </div>
                        </div>

                        {{-- Tabla desglosada con resultados filtrados --}}
                        <table class="sigem-fmt-table">
                            <thead>
                                <tr>
                                    <th>Tipo formato</th>
                                    <th>Código</th>
                                    <th>Departamento</th>
                                    <th>Fecha</th>
                                    <th style="text-align: center; width: 90px;">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $filasFiltradas = match ($livewire->formatoFiltroTipo) {
                                        'entrega' => $solicitudesEntrega->map(fn ($s) => [
                                            'tipo_nombre' => 'Entrega',
                                            'tipo_clave' => 'entrega',
                                            'id' => $s->id_solicitud,
                                            'codigo' => 'FOLIO-' . str_pad((string) $s->id_solicitud, 5, '0', STR_PAD_LEFT),
                                            'depto' => $s->receptor?->area?->departamento?->nombre ?? ($s->receptor?->area?->nombre ?? '—'),
                                            'fecha' => $s->fecha_solicitud ? $s->fecha_solicitud->format('d/m/Y') : '—',
                                        ]),
                                        'orden_trabajo' => $mantenimientosOrden->map(fn ($m) => [
                                            'tipo_nombre' => 'Orden de Trabajo',
                                            'tipo_clave' => 'orden_trabajo',
                                            'id' => $m->id_mantenimiento,
                                            'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                                            'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                                            'fecha' => $m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—',
                                        ]),
                                        'lista_verificacion' => $mantenimientosVerif->map(fn ($m) => [
                                            'tipo_nombre' => 'Lista Verificación',
                                            'tipo_clave' => 'lista_verificacion',
                                            'id' => $m->id_mantenimiento,
                                            'codigo' => 'MANT-' . str_pad((string) $m->id_mantenimiento, 4, '0', STR_PAD_LEFT),
                                            'depto' => $m->inventario?->departamento?->nombre ?? ($m->inventario?->area?->departamento?->nombre ?? '—'),
                                            'fecha' => $m->fecha_fin ? $m->fecha_fin->format('d/m/Y') : ($m->fecha_solicitud ? $m->fecha_solicitud->format('d/m/Y') : '—'),
                                        ]),
                                        'cambio_area' => $inventariosCambio->map(fn ($c) => [
                                            'tipo_nombre' => 'Cambio de Área',
                                            'tipo_clave' => 'cambio_area',
                                            'id' => $c->id_inventario,
                                            'codigo' => $c->codigo ?: ($c->num_serie ?: ('#' . $c->id_inventario)),
                                            'depto' => $c->departamento?->nombre ?? ($c->area?->departamento?->nombre ?? '—'),
                                            'fecha' => $c->fecha_cambio_area ? \Illuminate\Support\Carbon::parse($c->fecha_cambio_area)->format('d/m/Y') : '—',
                                        ]),
                                        default => $inventariosBaja->map(fn ($b) => [
                                            'tipo_nombre' => 'Baja',
                                            'tipo_clave' => 'baja',
                                            'id' => $b->id_inventario,
                                            'codigo' => $b->codigo ?: ($b->num_serie ?: ('#' . $b->id_inventario)),
                                            'depto' => $b->departamento?->nombre ?? ($b->area?->departamento?->nombre ?? '—'),
                                            'fecha' => $b->fecha_baja ? \Illuminate\Support\Carbon::parse($b->fecha_baja)->format('d/m/Y') : '—',
                                        ]),
                                    };
                                @endphp

                                @forelse ($filasFiltradas as $fila)
                                    <tr>
                                        <td>
                                            <span class="sigem-pill {{ in_array($fila['tipo_clave'], ['baja', 'lista_verificacion']) ? 'warn' : 'ok' }}" style="font-size: 11.5px;">
                                                {{ $fila['tipo_nombre'] }}
                                            </span>
                                        </td>
                                        <td><strong>{{ $fila['codigo'] }}</strong></td>
                                        <td>{{ $fila['depto'] }}</td>
                                        <td>{{ $fila['fecha'] }}</td>
                                        <td style="text-align: center;">
                                            <div class="sigem-pdf-actions">
                                                <a href="{{ route('reportes.pdf', ['tipo' => $fila['tipo_clave'], 'id' => $fila['id'], 'preview' => 1]) }}"
                                                   target="_blank"
                                                   class="sigem-btn-pdf-view"
                                                   title="Abrir PDF en nueva pestaña">
                                                    📄
                                                </a>
                                                <a href="{{ route('reportes.pdf', ['tipo' => $fila['tipo_clave'], 'id' => $fila['id'], 'download' => 1]) }}"
                                                   class="sigem-btn-pdf-download"
                                                   title="Descargar PDF">
                                                    ⬇
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 24px;">
                                            No hay formatos disponibles
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 3: NUEVOS REPORTES (12 CON GRÁFICA + 16 TABLA)   --}}
    {{-- ======================================================== --}}
    @if (in_array($pestanaActual, ['nuevos', 'nuevos_reportes'], true))
        {{-- Barra de 5 Sub-pestañas --}}
        <div class="sigem-subtabs-bar">
            @foreach ($subpestanas as $claveSub => $nombreSub)
                <button type="button"
                        wire:click="cambiarSubpestanaReportes('{{ $claveSub }}')"
                        class="sigem-subtab-btn {{ $subpestanaActual === $claveSub ? 'is-active' : '' }}">
                    <span>{{ $nombreSub }}</span>
                    <span style="font-size: 11px; opacity: 0.85;">({{ count($catalogoNuevos[$claveSub] ?? []) }})</span>
                </button>
            @endforeach
        </div>

        {{-- Barra de Filtros para los 28 reportes --}}
        <div class="sigem-filter-toolbar">
            <div class="sigem-filter-group">
                <span class="sigem-filter-label">📅 Desde:</span>
                <input type="date"
                       wire:model.live="filtroFechaDesde"
                       class="sigem-filter-input">
            </div>

            <div class="sigem-filter-group">
                <span class="sigem-filter-label">📅 Hasta:</span>
                <input type="date"
                       wire:model.live="filtroFechaHasta"
                       class="sigem-filter-input">
            </div>

            <div class="sigem-filter-group">
                <span class="sigem-filter-label">🏢 Depto:</span>
                <select wire:model.live="filtroDepartamentoId" class="sigem-filter-input" style="min-width: 180px; appearance: none; -webkit-appearance: none; -moz-appearance: none;">
                    <option value="">Todos los departamentos</option>
                    @foreach ($deptosFiltros as $idDepto => $nombreDepto)
                        <option value="{{ $idDepto }}">{{ $nombreDepto }}</option>
                    @endforeach
                </select>
            </div>

            @if ($livewire->filtroFechaDesde || $livewire->filtroFechaHasta || $livewire->filtroDepartamentoId)
                <button type="button"
                        wire:click="limpiarFiltrosNuevosReportes"
                        class="sigem-btn-export-csv"
                        style="color: #dc2626; border-color: #fca5a5; background: #fef2f2;">
                    ✕ Limpiar filtros
                </button>
            @endif
        </div>

        {{-- Grid de Tarjetas: 12 CON GRÁFICA (2 por fila) + 16 SOLO TABLA (3 por fila) --}}
        @php
            $repsConGrafica = collect($reportesSubpestana)->filter(fn ($r) => ! empty($r['has_chart']))->values();
            $repsSoloTabla = collect($reportesSubpestana)->filter(fn ($r) => empty($r['has_chart']))->values();
        @endphp

        {{-- SECCIÓN 1: Tarjetas CON Gráfica (2 por fila, 50% cada una) --}}
        @if ($repsConGrafica->isNotEmpty())
            <div class="sigem-new-rep-grid-charts sigem-new-rep-grid">
                @foreach ($repsConGrafica as $rep)
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
                            <div class="sigem-chart-canvas-wrap" style="min-height: {{ $numPuntos >= 4 ? '220px' : '180px' }}; height: {{ $numPuntos >= 4 ? '220px' : '180px' }}; position: relative;">
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
                                                <td class="{{ preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim((string)$td)) ? 'sigem-td-nowrap' : '' }}">{{ $td }}</td>
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
                @endforeach
            </div>
        @endif

        {{-- SECCIÓN 2: Tarjetas SOLO Tabla (3 por fila, 33% cada una) --}}
        @if ($repsSoloTabla->isNotEmpty())
            <div class="sigem-new-rep-grid-tables sigem-new-rep-grid">
                @foreach ($repsSoloTabla as $rep)
                    <div class="sigem-new-card sigem-table-card" wire:key="sigem-new-table-{{ $rep['id'] }}">
                        <div class="sigem-new-card-top">
                            <div class="sigem-new-card-title">{{ $rep['emoji'] }} {{ $rep['titulo'] }}</div>
                            @if (! empty($rep['totales_label']))
                                <span class="sigem-badge-totales">{{ $rep['totales_label'] }}</span>
                            @endif
                        </div>

                        <div class="sigem-new-card-desc">{{ $rep['descripcion'] }}</div>

                        {{-- TABLA TOP 5 REGISTROS --}}
                        <div class="sigem-mini-table-box">
                            <div class="sigem-mini-table-title">📋 Vista previa (top 5):</div>
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
                                                <td class="{{ preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim((string)$td)) ? 'sigem-td-nowrap' : '' }}">{{ $td }}</td>
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
                @endforeach
            </div>
        @endif

        @if ($repsConGrafica->isEmpty() && $repsSoloTabla->isEmpty())
            <div style="padding: 24px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b; margin-bottom: 32px;">
                No hay reportes configurados para esta sección.
            </div>
        @endif

        {{-- Vista previa del nuevo reporte seleccionado (ABAJO DEL GRID - Problema 1) --}}
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

{{-- CARGA SEGURA DE CHART.JS Y COMPONENTE ALPINE --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    if (!window.sigemChartComponent) {
        window.sigemChartComponent = function (chartConfig) {
            return {
                chartInstance: null,
                initChart() {
                    if (!chartConfig || !this.$refs.canvas) return;
                    const ctx = this.$refs.canvas.getContext('2d');

                    const truncateLabel = (val, maxLen = 25) => {
                        if (typeof val === 'string' && val.length > maxLen) {
                            return val.slice(0, maxLen - 3) + '...';
                        }
                        return val;
                    };

                    const isHorizontal = chartConfig.options?.indexAxis === 'y';
                    const isPieOrDonut = ['pie', 'doughnut'].includes(chartConfig.type);

                    // Configurar escalas responsivas según orientación
                    const baseScales = chartConfig.options?.scales ? JSON.parse(JSON.stringify(chartConfig.options.scales)) : {};
                    let finalScales = {};

                    if (!isPieOrDonut) {
                        if (isHorizontal) {
                            // Barras horizontales: Y son categorías (labels de deptos/equipos), X son valores numéricos
                            finalScales.y = Object.assign({}, baseScales.y || {}, {
                                grid: { display: false },
                                ticks: {
                                    autoSkip: false,
                                    maxRotation: 0,
                                    font: { size: 11 },
                                    callback: function (val, index) {
                                        const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                        return truncateLabel(raw, 25);
                                    }
                                }
                            });
                            finalScales.x = Object.assign({}, baseScales.x || {}, {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    font: { size: 11 },
                                    precision: 0
                                }
                            });
                        } else {
                            // Barras verticales o líneas: X son categorías, Y son valores numéricos
                            finalScales.x = Object.assign({}, baseScales.x || {}, {
                                grid: { display: false },
                                ticks: {
                                    autoSkip: true,
                                    maxRotation: 45,
                                    minRotation: 0,
                                    font: { size: 11 },
                                    callback: function (val, index) {
                                        const raw = this.getLabelForValue ? this.getLabelForValue(val) : (chartConfig.labels?.[index] ?? val);
                                        return truncateLabel(raw, 25);
                                    }
                                }
                            });
                            finalScales.y = Object.assign({}, baseScales.y || {}, {
                                beginAtZero: true,
                                grid: { color: '#f1f5f9' },
                                ticks: {
                                    font: { size: 11 },
                                    precision: 0
                                }
                            });
                        }
                    }

                    const render = () => {
                        if (typeof Chart === 'undefined') {
                            setTimeout(render, 150);
                            return;
                        }
                        if (this.chartInstance) {
                            try { this.chartInstance.destroy(); } catch (e) {}
                        }
                        try {
                            this.chartInstance = new Chart(ctx, {
                                type: chartConfig.type,
                                data: {
                                    labels: chartConfig.labels,
                                    datasets: chartConfig.datasets
                                },
                                options: Object.assign({
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    layout: {
                                        padding: {
                                            left: 4,
                                            right: 8,
                                            top: 4,
                                            bottom: 4
                                        }
                                    },
                                    plugins: {
                                        legend: {
                                            display: isPieOrDonut,
                                            position: 'bottom',
                                            labels: {
                                                boxWidth: 10,
                                                font: { size: 11 },
                                                padding: 12
                                            }
                                        },
                                        tooltip: {
                                            enabled: true,
                                            callbacks: {
                                                title: function (items) {
                                                    if (!items.length) return '';
                                                    const idx = items[0].dataIndex;
                                                    return chartConfig.labels?.[idx] ?? items[0].label ?? '';
                                                }
                                            }
                                        }
                                    },
                                    scales: finalScales
                                }, chartConfig.options || {})
                            });
                        } catch (err) {
                            console.warn('Error inicializando gráfica SIGEM:', err);
                        }
                    };

                    this.$nextTick(() => render());
                },
                destroy() {
                    if (this.chartInstance) {
                        try { this.chartInstance.destroy(); } catch (e) {}
                    }
                }
            };
        };
    }
</script>
