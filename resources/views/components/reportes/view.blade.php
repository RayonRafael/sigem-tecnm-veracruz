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
                title="29 Nuevos Reportes con gráficas">
            <span>📈</span>
            <span>Nuevos Reportes</span>
            <span class="sigem-nav-tab-badge is-highlight">29 con gráficas</span>
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
            $datosReportes = [];
            foreach ($listaReportes as $repItem) {
                $datosReportes[$repItem['id']] = $livewire->getDatosReporte($repItem['id']);
            }
        @endphp

        <x-reportes.tab-principales
            :livewire="$livewire"
            :esAdmin="$esAdmin"
            :listaReportes="$listaReportes"
            :notaVisibilidad="$notaVisibilidad"
            :datosReportes="$datosReportes"
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
    {{-- PESTAÑA 3: NUEVOS REPORTES — VISTA HÍBRIDA (12 GRÁFICAS + 17 TABLAS EN ACORDEÓN) --}}
    {{-- ======================================================== --}}
    @if ($esPestanaNuevos)
        @php
            $catalogoNuevos = \App\Support\SigemReportExporter::getCatalogo();
            $subpestanas = \App\Support\SigemReportExporter::getSubpestanas();
            $subpestanaActual = $livewire->subpestanaReportes ?? 'inventario';
            $deptosFiltros = $livewire->departamentosParaFiltros;
            $reportesSubpestana = collect($livewire->getReportesSubpestanaActual());

            if (! $esAdmin) {
                $reportesSubpestana = $reportesSubpestana
                    ->filter(fn ($r) => $livewire->visibilidadServicioSocial[$r['id']] ?? true)
                    ->values();
            }

            $repsConGrafica = $reportesSubpestana->filter(fn ($r) => ! empty($r['has_chart']))->values();
            $repsSoloTabla = $reportesSubpestana->filter(fn ($r) => empty($r['has_chart']))->values();

            $primerTablaId = $repsSoloTabla->first()['id'] ?? null;
            $inicialTablaId = ($livewire->nuevoReporteActivo && $repsSoloTabla->contains('id', $livewire->nuevoReporteActivo))
                ? $livewire->nuevoReporteActivo
                : $primerTablaId;
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

        {{-- Barra de Filtros para los 29 reportes --}}
        <x-reportes.filtros :livewire="$livewire" :deptosFiltros="$deptosFiltros" />

        {{-- SECCIÓN 1: GRÁFICAS (ancho completo, 1 por fila, gráfica + tabla completa paginada) --}}
        @if ($repsConGrafica->isNotEmpty())
            <div class="sigem-new-section-head">
                <h3>📊 Gráficas</h3>
                <span class="sigem-new-section-count">{{ $repsConGrafica->count() }} {{ $repsConGrafica->count() === 1 ? 'reporte visual' : 'reportes visuales' }}</span>
            </div>
            <div class="sigem-new-rep-charts-stack" wire:key="sigem-charts-grid-{{ $subpestanaActual }}">
                @foreach ($repsConGrafica as $rep)
                    <x-reportes.card-grafica :rep="$rep" :livewire="$livewire" :esAdmin="$esAdmin" />
                @endforeach
            </div>
        @endif

        {{-- SECCIÓN 2: TABLAS (acordeón colapsable, primero abierto por defecto) --}}
        @if ($repsSoloTabla->isNotEmpty())
            <div class="sigem-new-section-head">
                <h3>📋 Tablas</h3>
                <span class="sigem-new-section-count">{{ $repsSoloTabla->count() }} {{ $repsSoloTabla->count() === 1 ? 'reporte' : 'reportes' }}</span>
            </div>
            <div class="sigem-rep-accordion"
                 wire:key="sigem-tables-accordion-{{ $subpestanaActual }}"
                 x-data="{ openId: @js($inicialTablaId) }">
                @foreach ($repsSoloTabla as $rep)
                    <x-reportes.card-tabla
                        :rep="$rep"
                        :livewire="$livewire"
                        :esAdmin="$esAdmin"
                        :esInicialmenteAbierto="$rep['id'] === $inicialTablaId"
                    />
                @endforeach
            </div>
        @endif

        @if ($repsConGrafica->isEmpty() && $repsSoloTabla->isEmpty())
            <div style="padding: 24px; text-align: center; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b; margin-bottom: 32px;">
                No hay reportes disponibles para esta sección.
            </div>
        @endif
    @endif
</div>

{{-- Carga diferida segura de Chart.js y componente Alpine optimizado --}}
@once
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="{{ asset('js/sigem-reportes-charts.js') }}?v={{ file_exists(public_path('js/sigem-reportes-charts.js')) ? filemtime(public_path('js/sigem-reportes-charts.js')) : 1 }}" defer></script>
@endonce
