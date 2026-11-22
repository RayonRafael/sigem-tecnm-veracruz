@props([
    'livewire',
    'pestanaFormatoActiva' => 'entrega',
])

@php
    $conteos = $livewire->conteosFormatos;
@endphp

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
            <span class="sigem-subtab-count">({{ $conteos['entrega'] }})</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaFormato('orden_trabajo')"
                title="Orden de Trabajo"
                class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'orden_trabajo' ? 'is-active' : '' }}">
            <span>🔧 Orden</span>
            <span class="sr-only">de Trabajo</span>
            <span class="sigem-subtab-count">({{ $conteos['orden_trabajo'] }})</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaFormato('lista_verificacion')"
                title="Lista Verificación"
                class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'lista_verificacion' ? 'is-active' : '' }}">
            <span>✅ Lista</span>
            <span class="sr-only">Verificación</span>
            <span class="sigem-subtab-count">({{ $conteos['lista_verificacion'] }})</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaFormato('baja')"
                title="Trámite de Baja"
                class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'baja' ? 'is-active' : '' }}">
            <span>⚠️ Baja</span>
            <span class="sr-only">Trámite de Baja</span>
            <span class="sigem-subtab-count">({{ $conteos['baja'] }})</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaFormato('cambio_area')"
                title="Formato de Cambio de Área"
                class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'cambio_area' ? 'is-active' : '' }}">
            <span>🔄 Cambio</span>
            <span class="sr-only">de Área</span>
            <span class="sigem-subtab-count">({{ $conteos['cambio_area'] }})</span>
        </button>

        <button type="button"
                wire:click="cambiarPestanaFormato('filtrar')"
                title="Filtrar formatos existentes"
                class="sigem-subtab-btn {{ $pestanaFormatoActiva === 'filtrar' ? 'is-active' : '' }}">
            <span>🔍 Filtrar existentes</span>
            <span class="sr-only">Filtrar formatos existentes — Descargar seleccionados</span>
        </button>
    </div>

    {{-- Tablas Desglosadas (carga perezosa: SOLO consulta la sub-pestaña activa) --}}
    <div class="sigem-fmt-tab-content">
        {{-- TABLA 1: ENTREGA DE EQUIPO --}}
        @if ($pestanaFormatoActiva === 'entrega')
            @php $solicitudesEntrega = $livewire->getSolicitudesParaFormato(); @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-entrega">
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
                            <tr wire:key="fmt-ent-{{ $sol->id_solicitud }}">
                                <td><strong>{{ $folioSol }}</strong></td>
                                <td>{{ $nomEq }}</td>
                                <td>{{ $receptorSol }}</td>
                                <td>{{ $deptoSol }}</td>
                                <td>{{ $fechaSol }}</td>
                                <td style="text-align: center;">
                                    <x-reportes.pdf-actions tipo="entrega" :id="$sol->id_solicitud" />
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
            @php $mantenimientosOrden = $livewire->getMantenimientosParaOrdenFormato(); @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-orden">
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
                                $tipoServ = $mant->tipo_servicio ?: ($mant->tipo_mantenimiento ?: 'Preventivo');
                                $tecnico = $mant->nombre_tecnico ?: ($mant->usuarioSolicita?->name ?? 'Centro de Cómputo');
                                $fechaMant = $mant->fecha_solicitud ? $mant->fecha_solicitud->format('d/m/Y') : '—';
                            @endphp
                            <tr wire:key="fmt-ord-{{ $mant->id_mantenimiento }}">
                                <td><strong>{{ $folioMant }}</strong></td>
                                <td>{{ $eqNom }}</td>
                                <td>{{ $tipoServ }}</td>
                                <td>{{ $tecnico }}</td>
                                <td>{{ $fechaMant }}</td>
                                <td style="text-align: center;">
                                    <x-reportes.pdf-actions tipo="orden_trabajo" :id="$mant->id_mantenimiento" />
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
            @php $mantenimientosVerif = $livewire->getMantenimientosParaVerificacionFormato(); @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-verif">
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
                                $atendidoPor = $mantV->nombre_tecnico ?: ($mantV->usuarioSolicita?->name ?? 'Centro de Cómputo');
                                $fechaV = $mantV->fecha_fin ? $mantV->fecha_fin->format('d/m/Y') : ($mantV->fecha_solicitud ? $mantV->fecha_solicitud->format('d/m/Y') : '—');
                            @endphp
                            <tr wire:key="fmt-ver-{{ $mantV->id_mantenimiento }}">
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
                                    <x-reportes.pdf-actions tipo="lista_verificacion" :id="$mantV->id_mantenimiento" />
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
            @php $inventariosBaja = $livewire->getInventariosBajaParaFormato(); @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-baja">
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
                            <tr wire:key="fmt-baja-{{ $invBaja->id_inventario }}">
                                <td><strong>{{ $codBaja }}</strong></td>
                                <td>{{ $nomBaja }}</td>
                                <td><span class="sigem-pill warn" style="font-size: 11.5px;">{{ $motivoBaja }}</span></td>
                                <td>{{ $deptoBaja }}</td>
                                <td>{{ $fechaBaja }}</td>
                                <td style="text-align: center;">
                                    <x-reportes.pdf-actions tipo="baja" :id="$invBaja->id_inventario" />
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
            @php $inventariosCambio = $livewire->getInventariosCambioAreaParaFormato(); @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-cambio">
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
                            <tr wire:key="fmt-camb-{{ $invC->id_inventario }}">
                                <td><strong>{{ $codC }}</strong></td>
                                <td>{{ $nomC }}</td>
                                <td>{{ $origenC }}</td>
                                <td>{{ $destinoC }}</td>
                                <td>{{ $fechaC }}</td>
                                <td style="text-align: center;">
                                    <x-reportes.pdf-actions tipo="cambio_area" :id="$invC->id_inventario" />
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
            @php
                $deptosFiltros = $livewire->departamentosParaFiltros;
                $filasFiltradas = $livewire->getFilasFiltradasFormatos();
            @endphp
            <div class="sigem-rep-table-wrap" wire:key="sigem-fmt-tab-filtrar">
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
                            <option value="baja">Trámite de Baja ({{ $conteos['baja'] }})</option>
                            <option value="entrega">Entrega de Equipo ({{ $conteos['entrega'] }})</option>
                            <option value="orden_trabajo">Orden de Trabajo ({{ $conteos['orden_trabajo'] }})</option>
                            <option value="lista_verificacion">Lista Verificación ({{ $conteos['lista_verificacion'] }})</option>
                            <option value="cambio_area">Cambio de Área ({{ $conteos['cambio_area'] }})</option>
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
                        @forelse ($filasFiltradas as $fila)
                            <tr wire:key="fmt-flt-{{ $fila['tipo_clave'] }}-{{ $fila['id'] }}">
                                <td>
                                    <span class="sigem-pill {{ in_array($fila['tipo_clave'], ['baja', 'lista_verificacion']) ? 'warn' : 'ok' }}" style="font-size: 11.5px;">
                                        {{ $fila['tipo_nombre'] }}
                                    </span>
                                </td>
                                <td><strong>{{ $fila['codigo'] }}</strong></td>
                                <td>{{ $fila['depto'] }}</td>
                                <td>{{ $fila['fecha'] }}</td>
                                <td style="text-align: center;">
                                    <x-reportes.pdf-actions :tipo="$fila['tipo_clave']" :id="$fila['id']" />
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
