@props([
    'panel' => 'admin',
    'livewire',
])

@php
    $esAdmin = $panel === 'admin';
    $reportes = $livewire->getReportes();
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
    $reportesSubpestana = $catalogoNuevos[$subpestanaActual] ?? [];

    $datosPreviaNuevo = $livewire->nuevoReporteActivo ? $livewire->getDatosNuevoReporte($livewire->nuevoReporteActivo) : null;
    $pestanaActual = $livewire->pestanaPrincipal ?? 'formatos';
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
        .sigem-filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 22px;
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
        .sigem-new-rep-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .sigem-new-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
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
        }
        .sigem-card-num-badge {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1b396a;
            border: 1px solid #bfdbfe;
        }
        .sigem-new-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }
        .sigem-new-card-desc {
            font-size: 12.5px;
            color: #64748b;
            line-height: 1.45;
            flex: 1;
        }
        .sigem-new-card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
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

    {{-- Pestañas Principales: Formatos PDF vs Nuevos Reportes --}}
    <div class="sigem-main-nav-tabs">
        <button type="button"
                wire:click="cambiarPestanaPrincipal('formatos')"
                class="sigem-nav-tab {{ $pestanaActual === 'formatos' ? 'is-active' : '' }}"
                title="Formatos Institucionales Oficiales en PDF">
            <span>📋</span>
            <span>Formatos PDF</span>
            <span class="sigem-nav-tab-badge">5 formatos oficiales</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaPrincipal('nuevos_reportes')"
                class="sigem-nav-tab {{ $pestanaActual === 'nuevos_reportes' ? 'is-active' : '' }}"
                title="28 Nuevos Reportes de Inventario, Estadísticas, Mantenimiento, Rentas e Histórico">
            <span>📈</span>
            <span>Nuevos Reportes</span>
            <span class="sigem-nav-tab-badge is-highlight">28 reportes Excel/CSV</span>
        </button>
    </div>

    {{-- Vista previa del reporte legacy cuando se solicita --}}
    @if ($reporteActual)
        <div class="sigem-rep-view" id="sigem-reporte-visor-legacy" style="margin-bottom: 24px;">
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
            @endif
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 1: FORMATOS INSTITUCIONALES (PDF)                --}}
    {{-- ======================================================== --}}
    @if ($pestanaActual === 'formatos')
        <section class="sigem-fmt-section" aria-labelledby="sigem-fmt-heading">
            <div class="sigem-fmt-header">
                <h2 id="sigem-fmt-heading">📄 Formatos Institucionales (PDF)</h2>
                <p>Genera los formatos oficiales del TecNM Veracruz con membrete institucional y firma electrónica</p>
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
                            @elseif ($fmtId === 'cambio_area')
                                <label class="sigem-fmt-label">Equipos con cambio de área ({{ $inventariosCambio->count() }})</label>
                                <select wire:model="formatoSeleccionadoCambioArea" class="sigem-fmt-control">
                                    <option value="">
                                        {{ $inventariosCambio->isNotEmpty() ? 'Más reciente (' . $inventariosCambio->count() . ' disp.)' : 'Sin cambios de área en el filtro' }}
                                    </option>
                                    @foreach ($inventariosCambio as $invC)
                                        @php
                                            $codC = $invC->codigo ?: ($invC->num_serie ?: ('#' . $invC->id_inventario));
                                            $nomC = $invC->material?->nombre ?? $invC->subtipo ?? 'Equipo';
                                            $deptoC = $invC->departamento?->nombre ?? $invC->area?->departamento?->nombre ?? 'Sin depto';
                                            $fechaC = $invC->fecha_cambio_area ? Carbon\Carbon::parse($invC->fecha_cambio_area)->format('d/m/Y') : '—';
                                        @endphp
                                        <option value="{{ $invC->id_inventario }}">
                                            {{ $codC }} · {{ $nomC }} · {{ $deptoC }} ({{ $fechaC }})
                                        </option>
                                    @endforeach
                                </select>
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

                {{-- Tarjeta de Filtros Históricos de Regeneración --}}
                <div class="sigem-fmt-card sigem-fmt-filters-card">
                    <div class="sigem-fmt-card-top">
                        <div class="sigem-fmt-ico sigem-fmt-ico-filter">🔍</div>
                        <span class="sigem-fmt-badge">Histórico</span>
                    </div>
                    <div class="sigem-fmt-title">Filtros de regeneración</div>
                    <div class="sigem-fmt-desc">Filtra los registros por rango de fechas y departamento para regenerar formatos.</div>

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
                                <option value="cambio_area">Cambio de Área ({{ $inventariosCambio->count() }})</option>
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
    @endif

    {{-- ======================================================== --}}
    {{-- PESTAÑA 2: NUEVOS REPORTES (28 REPORTES EN EXCEL / CSV)  --}}
    {{-- ======================================================== --}}
    @if ($pestanaActual === 'nuevos_reportes')
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
                <select wire:model.live="filtroDepartamentoId" class="sigem-filter-input" style="min-width: 180px;">
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

        {{-- Grid de Tarjetas de la Sub-pestaña seleccionada --}}
        <div class="sigem-new-rep-grid">
            @forelse ($reportesSubpestana as $rep)
                <div class="sigem-new-card">
                    <div class="sigem-new-card-top">
                        <span class="sigem-card-num-badge">#{{ $rep['num'] }}</span>
                        <div style="font-size: 20px;">{{ $rep['icono'] }}</div>
                    </div>
                    <div class="sigem-new-card-title">{{ $rep['titulo'] }}</div>
                    <div class="sigem-new-card-desc">{{ $rep['descripcion'] }}</div>

                    <div class="sigem-new-card-foot">
                        <button type="button"
                                wire:click="verNuevoReporte('{{ $rep['id'] }}')"
                                class="sigem-btn-preview"
                                title="Ver vista previa de los datos">
                            👁️ Previa
                        </button>

                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <button type="button"
                                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'xlsx')"
                                    class="sigem-btn-export-excel"
                                    title="Descargar como archivo XLSX de Excel">
                                ↓ Excel
                            </button>
                            <button type="button"
                                    wire:click="exportarNuevoReporte('{{ $rep['id'] }}', 'csv')"
                                    class="sigem-btn-export-csv"
                                    title="Descargar como archivo CSV">
                                ↓ CSV
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; padding: 24px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b;">
                    No hay reportes configurados para esta sección.
                </div>
            @endforelse
        </div>

        {{-- Vista previa del nuevo reporte seleccionado --}}
        @if ($datosPreviaNuevo)
            <div class="sigem-rep-view" id="sigem-reporte-visor" x-on:scroll-to-report.window="$el.scrollIntoView({behavior:'smooth'})" style="margin-top: 24px;">
                <div class="sigem-rep-vh">
                    <div>
                        <h3>📊 {{ $datosPreviaNuevo['titulo'] }}</h3>
                        <div class="sigem-rep-meta">
                            Vista previa (primeros 5 registros de {{ $datosPreviaNuevo['total'] }} totales)
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
